<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait OAuthHandler
{
    /**
     * Get HTTP client based on environment
     */
    protected function getHttpClient(): Client
    {
        return app()->environment('production') 
            ? new Client() 
            : new Client(['verify' => false]);
    }
    
    /**
     * Exchange authorization code for tokens
     */
    protected function exchangeCodeForTokens(string $authorizationCode): array
    {
        $config = $this->getOAuthConfig();
        
        $client = $this->getHttpClient();
        
        $response = $client->post(config('auth-custom.oauth.token_url', env('IS_TOKEN_URL')), [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'redirect_uri' => $config['redirect_uri'],
            ],
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
        
        return json_decode($response->getBody(), true);
    }
    
    /**
     * Process token response and calculate expiration
     */
    protected function processTokenResponse(array $data): array
    {
        return [
            'access_token' => $data['access_token'] ?? '',
            'id_token' => $data['id_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'scope' => $data['scope'] ?? '',
            'expires_in' => $data['expires_in'] ?? $data['expire_in'] ?? 3600,
            'expires_at' => time() + (int)($data['expires_in'] ?? $data['expire_in'] ?? 3600),
        ];
    }
}
