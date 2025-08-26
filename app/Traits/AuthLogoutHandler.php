<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AuthLogoutHandler - Manages logout controller methods
 * 
 * This trait handles:
 * - Standard logout with redirect
 * - Custom redirect logout
 * - API logout (JSON response)
 * - Security logout
 * - Integration with existing LogoutHandler
 */
trait AuthLogoutHandler
{
    /**
     * Standard logout with default redirect
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleStandardLogout(Request $request)
    {
        $this->performOidcLogoutAndClearSession($request);
        return redirect('/')->with('success', 'You have been successfully logged out.');
    }

    /**
     * Logout with custom redirect URL
     *
     * @param Request $request
     * @param string|null $redirectUrl
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleLogoutWithCustomRedirect(Request $request, string $redirectUrl = null)
    {
        return $this->handleLogoutWithRedirect($request, $redirectUrl);
    }

    /**
     * API logout that returns JSON response
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiLogout(Request $request)
    {
        try {
            $logoutResult = $this->performLogout($request);
            
            Log::info('API logout successful', [
                'username' => $logoutResult['username']
            ]);
            
            return $this->successResponse([
                'username' => $logoutResult['username'],
                'logout_url' => $logoutResult['logout_url']
            ], 'Logout successful');
            
        } catch (\Exception $e) {
            Log::error('API logout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Logout failed', 500, $e->getMessage());
        }
    }

    /**
     * Security logout for expired tokens or suspicious activity
     *
     * @param Request $request
     * @param string $reason
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleSecurityLogout(Request $request, string $reason = 'Security logout')
    {
        return $this->forceLogout($request, $reason);
    }

    /**
     * Validate logout request
     *
     * @param Request $request
     * @return bool
     */
    protected function validateLogoutRequest(Request $request): bool
    {
        // Check if user has active session
        if (!session()->has('access_token')) {
            Log::warning('Logout attempt without active session', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Log logout attempt with context
     *
     * @param Request $request
     * @param string $logoutType
     * @param bool $success
     * @param string|null $error
     */
    protected function logLogoutAttempt(Request $request, string $logoutType, bool $success, ?string $error = null): void
    {
        $username = session('user_info.username', 'unknown');
        
        if ($success) {
            Log::info('Logout completed successfully', [
                'username' => $username,
                'logout_type' => $logoutType,
                'ip' => $request->ip()
            ]);
        } else {
            Log::error('Logout failed', [
                'username' => $username,
                'logout_type' => $logoutType,
                'error' => $error,
                'ip' => $request->ip()
            ]);
        }
    }
}
