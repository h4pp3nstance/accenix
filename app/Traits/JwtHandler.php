<?php

namespace App\Traits;

trait JwtHandler
{
    /**
     * Decode JWT token payload
     */
    protected function decodeJwtPayload(string $token): ?array
    {
        $tokenParts = explode('.', $token);
        
        if (count($tokenParts) !== 3) {
            return null;
        }
        
        $payload = $tokenParts[1];
        $decodedPayload = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));
        
        return json_decode($decodedPayload, true);
    }
    
    /**
     * Validate JWT token structure
     */
    protected function validateJwtStructure(string $token): bool
    {
        return count(explode('.', $token)) === 3;
    }
    
    /**
     * Extract company and user_id from access token
     */
    protected function extractAccessTokenData(string $accessToken): array
    {
        $payload = $this->decodeJwtPayload($accessToken);
        
        return [
            'company' => $payload['company'] ?? null,
            'user_id' => $payload['sub'] ?? '',
        ];
    }
}
