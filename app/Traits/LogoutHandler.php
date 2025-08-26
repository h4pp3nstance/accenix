<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

trait LogoutHandler
{
    /**
     * Call OIDC logout endpoint directly (POST) instead of redirect
     */
    protected function callOidcLogoutEndpoint(?string $idToken): void
    {
        if (!$idToken) {
            Log::warning('OIDC logout skipped: no id_token');
            return;
        }

        try {
            $client = new Client(['verify' => false]);
            $logoutUrl = env('IS_LOGOUT_URL');
            $response = $client->post($logoutUrl, [
                'form_params' => [
                    'id_token_hint' => $idToken,
                    'response_mode' => 'direct',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);
            Log::info('OIDC logout called at WSO2', [
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
            ]);
        } catch (\Exception $e) {
            Log::error('OIDC logout failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Perform logout with comprehensive cleanup
     */
    protected function performLogout(Request $request): array
    {
        // 1. Get user info before session flush
        $userInfo = $this->getUserInfoForLogout();
        
        // 2. Log logout activity
        $this->logLogoutActivity($userInfo['username'], $request);
        
        // 3. Revoke tokens at Identity Server (if supported)
        $this->revokeTokensAtIdentityServer($userInfo['access_token']);
        
        // 4. Clear session completely
        $this->clearUserSession($request);
        
        // 5. Build logout redirect URL
        $logoutUrl = $this->buildLogoutRedirectUrl($userInfo['id_token']);
        
        return [
            'username' => $userInfo['username'],
            'logout_url' => $logoutUrl,
            'success' => true
        ];
    }
    
    /**
     * Get user information before logout
     */
    private function getUserInfoForLogout(): array
    {
        return [
            'username' => Session::get('user_info.username', 'unknown'),
            'id_token' => Session::get('id_token'),
            'access_token' => Session::get('access_token'),
            'user_id' => Session::get('user_id'),
            'company' => Session::get('company'),
        ];
    }
    
    /**
     * Log logout activity
     */
    private function logLogoutActivity(string $username, Request $request): void
    {
        try {
            \App\Helpers\AuthLogger::logLogout($username, "User logged out", $request);
            Log::info('User logout successful', [
                'username' => $username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error logging logout activity', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Revoke tokens at Identity Server (if endpoint exists)
     */
    private function revokeTokensAtIdentityServer(?string $accessToken): void
    {
        if (!$accessToken || !env('IS_REVOKE_URL')) {
            return; // Skip if no token or revoke endpoint not configured
        }
        
        try {
            $client = new Client(['verify' => false]);
            $response = $client->post(env('IS_REVOKE_URL'), [
                'form_params' => [
                    'token' => $accessToken,
                    'token_type_hint' => 'access_token',
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(env('IS_CLIENT_ID') . ':' . env('IS_CLIENT_SECRET')),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'timeout' => 5, // Short timeout for logout process
            ]);
            if ($response->getStatusCode() === 200) {
                Log::info('Token revoked successfully at Identity Server');
            }
        } catch (\Exception $e) {
            // Don't fail logout if token revocation fails
            Log::warning('Failed to revoke token at Identity Server', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clear all session data
     */
    private function clearUserSession(Request $request): void
    {
        // Store session ID for logging before flush
        $sessionId = $request->session()->getId();
        
        // Clear all session data
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Log::debug('Session cleared for logout', [
            'old_session_id' => $sessionId,
            'new_session_id' => $request->session()->getId()
        ]);
    }
    
    /**
     * Build logout redirect URL for Identity Server
     */
    private function buildLogoutRedirectUrl(?string $idToken): string
    {
        $logoutUrl = config('auth-custom.oauth.logout_url', env('IS_LOGOUT_URL'));
        $redirectUri = route('oauth.callback');
        
        if (!$idToken || !$logoutUrl) {
            return $redirectUri; // Fallback to redirect URI
        }
        
        $logoutParams = [
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => $redirectUri,
        ];
        
        // Add client_id if required by Identity Server
        if (env('IS_LOGOUT_REQUIRES_CLIENT_ID', false)) {
            $logoutParams['client_id'] = config('auth-custom.oauth.client_id');
        }
        
        return $logoutUrl . '?' . http_build_query($logoutParams);
    }
    
    /**
     * Handle logout with optional redirect override
     */
    protected function handleLogoutWithRedirect(Request $request, ?string $customRedirect = null): \Illuminate\Http\RedirectResponse
    {
        $logoutResult = $this->performLogout($request);
        
        $redirectUrl = $customRedirect ?? $logoutResult['logout_url'];
        
        // Add flash message for user feedback
        $message = $this->getLogoutMessage($logoutResult);
        
        return Redirect::to($redirectUrl)->with('success', $message);
    }

    /**
     * Get the logout message
     */
    private function getLogoutMessage(array $logoutResult): string
    {
        if ($logoutResult['username'] !== 'unknown') {
            return "Goodbye {$logoutResult['username']}! You have been successfully logged out.";
        }
        return 'You have been successfully logged out.';
    }

    /**
     * Force logout (for security issues, token expiry, etc.)
     */
    protected function forceLogout(Request $request, string $reason = 'Security logout'): \Illuminate\Http\RedirectResponse
    {
        $username = Session::get('user_info.username', 'unknown');
        
        // Log the force logout
        Log::warning('Force logout triggered', [
            'username' => $username,
            'reason' => $reason,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Clear session immediately
        $this->clearUserSession($request);
        
        return redirect('/')->with('warning', 'Your session has been terminated for security reasons. Please log in again.');
    }
}
