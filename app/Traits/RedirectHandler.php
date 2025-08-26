<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Helpers\ScimHelper;

/**
 * RedirectHandler - Manages OAuth redirect operations
 * 
 * This trait handles:
 * - OAuth redirect URL generation
 * - Scope management for authentication
 * - Identity Server redirect logic
 */
trait RedirectHandler
{
    /**
     * Redirect user to Identity Server for OAuth authentication
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleRedirectToIdentityServer()
    {
        $config = $this->getOAuthConfig();
        $encoded_redirect_uri = urlencode($config['redirect_uri']);
        
        // Use default scopes if user is not authenticated
        if (!Session::has('access_token')) {
            $scopes = $config['default_scopes'];
            Log::info('Using default scopes for unauthenticated user', [
                'scopes' => $scopes
            ]);
        } else {
            // Dynamically fetch scopes from the Identity Server API
            try {
                $scopes = ScimHelper::getAuthorizedScopes();
                Log::info('Successfully fetched scopes from Identity Server API', [
                    'scope_count' => count($scopes)
                ]);
            } catch (\Exception $e) {
                Log::error('Error fetching scopes, using fallback', [
                    'error' => $e->getMessage()
                ]);
                $scopes = $config['default_scopes'];
            }
        }

        $scope = implode(' ', $scopes);
        $encoded_scope = urlencode($scope);

        // Build the SSO URL with hardcoded parameters (response_type=code)
        $auth_url = $config['auth_url']
            . '?client_id=' . $config['client_id']
            . '&scope=' . $encoded_scope
            . '&redirect_uri=' . $encoded_redirect_uri
            . '&response_type=code';

        Log::info('Redirecting to OAuth URL', [
            'url_length' => strlen($auth_url),
            'scopes' => $scopes
        ]);

        return redirect()->away($auth_url);
    }

    /**
     * Get scopes for the current authentication context
     *
     * @return array
     */
    protected function getAuthenticationScopes(): array
    {
        $config = $this->getOAuthConfig();
        
        if (!Session::has('access_token')) {
            return $config['default_scopes'];
        }

        try {
            return ScimHelper::getAuthorizedScopes();
        } catch (\Exception $e) {
            Log::error('Error fetching scopes, using fallback', [
                'error' => $e->getMessage()
            ]);
            return $config['default_scopes'];
        }
    }

    /**
     * Build OAuth authorization URL
     *
     * @param array $scopes
     * @return string
     */
    protected function buildAuthorizationUrl(array $scopes): string
    {
        $config = $this->getOAuthConfig();
        $encoded_redirect_uri = urlencode($config['redirect_uri']);
        $scope = implode(' ', $scopes);
        $encoded_scope = urlencode($scope);

        return $config['auth_url']
            . '?client_id=' . $config['client_id']
            . '&scope=' . $encoded_scope
            . '&redirect_uri=' . $encoded_redirect_uri
            . '&response_type=code';
    }
}
