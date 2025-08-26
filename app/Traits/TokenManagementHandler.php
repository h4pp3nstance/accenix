<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

trait TokenManagementHandler
{
    /**
     * Refresh token when expired
     */
    public function refreshToken(): bool
    {
        if (!Session::has('refresh_token')) {
            \App\Helpers\AuthLogger::logTokenRefresh(
                Session::get('user_info.username', 'unknown'), 
                false, 
                "No refresh token available"
            );
            return false;
        }
        
        $refreshToken = Session::get('refresh_token');
        $username = Session::get('user_info.username', 'unknown');
        
        try {
            $client = $this->getHttpClient();
            $tokenData = $this->performTokenRefresh($refreshToken, $client);
            $this->updateSessionWithNewTokens($tokenData, $refreshToken);
            
            \App\Helpers\AuthLogger::logTokenRefresh($username, true, "Token refreshed successfully");
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error refreshing token: ' . $e->getMessage());
            \App\Helpers\AuthLogger::logTokenRefresh(
                $username, 
                false, 
                "Error refreshing token: " . $e->getMessage()
            );
            return false;
        }
    }
    
    /**
     * Perform actual token refresh request
     */
    private function performTokenRefresh(string $refreshToken, Client $client): array
    {
        $response = $client->post(env('IS_TOKEN_URL'), [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(env('IS_CLIENT_ID') . ':' . env('IS_CLIENT_SECRET')),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
    
    /**
     * Update session with new tokens
     */
    private function updateSessionWithNewTokens(array $data, string $fallbackRefreshToken): void
    {
        $expiresAt = time() + (int)($data['expires_in'] ?? 3600);

        Session::put([
            'access_token' => $data['access_token'] ?? '',
            'id_token' => $data['id_token'] ?? '',
            'expires_at' => $expiresAt,
            'refresh_token' => $data['refresh_token'] ?? $fallbackRefreshToken,
        ]);
    }
    
    /**
     * Check if token is valid or refresh if necessary
     */
    protected function ensureValidToken(): bool
    {
        if (!Session::has('access_token')) {
            return false;
        }
        
        // Check if token is still valid (with 30 seconds buffer)
        if (Session::has('expires_at') && Session::get('expires_at') > (time() + 30)) {
            return true;
        }
        
        return $this->refreshToken();
    }
    
    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return Session::has('access_token') && Session::has('user_info');
    }
    
    /**
     * Get token expiry time with buffer
     */
    protected function getTokenExpiryWithBuffer(int $bufferSeconds = 30): int
    {
        return time() + $bufferSeconds;
    }
    
    /**
     * Check if token is about to expire
     */
    protected function isTokenNearExpiry(int $bufferSeconds = 30): bool
    {
        if (!Session::has('expires_at')) {
            return true; // Assume expired if no expiry time
        }
        
        return Session::get('expires_at') <= $this->getTokenExpiryWithBuffer($bufferSeconds);
    }
}
