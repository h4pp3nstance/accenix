<?php

namespace App\Traits;

trait ConfigurationHandler
{
    /**
     * Get OAuth configuration
     *
     * @return array
     */
    protected function getOAuthConfig(): array
    {
        return [
            'client_id' => config('auth-custom.oauth.client_id'),
            'client_secret' => config('auth-custom.oauth.client_secret'),
            'auth_url' => config('auth-custom.oauth.auth_url'),
            'redirect_uri' => route('oauth.callback'),
            'default_scopes' => config('auth-custom.oauth.default_scopes', ['openid', 'profile', 'roles', 'internal_login'])
        ];
    }

    /**
     * Get SCIM API configuration
     *
     * @return array
     */
    protected function getScimConfig(): array
    {
        return [
            'user_url' => config('auth-custom.scim.user_url'),
            'verify_ssl' => config('auth-custom.scim.verify_ssl', app()->environment('production')),
            'headers' => [
                'accept' => 'application/scim+json',
                'Content-Type' => 'application/json'
            ]
        ];
    }

    /**
     * Get application routes configuration
     *
     * @return array
     */
    protected function getRoutesConfig(): array
    {
        return [
            'dashboard' => config('auth-custom.routes.dashboard', 'dashboard-dev'),
            'login' => config('auth-custom.routes.login', '/'),
            'profile' => config('auth-custom.routes.profile_view', 'admin.profile')
        ];
    }
}
