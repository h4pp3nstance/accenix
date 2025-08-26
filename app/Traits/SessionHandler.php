<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

trait SessionHandler
{
    /**
     * Store authentication tokens in session
     */
    protected function storeTokensInSession(array $tokens, array $accessTokenData): void
    {
        Session::put('access_token', $tokens['access_token']);
        Session::put('id_token', $tokens['id_token']);
        Session::put('expires_at', $tokens['expires_at']);
        Session::put('user_id', $accessTokenData['user_id']);
        
        if ($tokens['refresh_token']) {
            Session::put('refresh_token', $tokens['refresh_token']);
        }
    }
    
    /**
     * Store user data in session
     */
    protected function storeUserDataInSession(Request $request, array $userInfo, array $jwtPayload, string $scope): void
    {
        $request->session()->put([
            'user_info' => $userInfo,
            'given_name' => $jwtPayload['given_name'] ?? '',
            'scope' => $scope,
            // Additional WSO2 payload data for RBAC
            'company' => $jwtPayload['company'] ?? null,
            'org_id' => $jwtPayload['org_id'] ?? '',
            'org_name' => $jwtPayload['org_name'] ?? '',
            'sub' => $jwtPayload['sub'] ?? '',
        ]);
    }
    
    /**
     * Load and store user permissions in session
     */
    protected function loadUserPermissions(Request $request, array $roles, string $username): void
    {
        try {
            $rolePermissionService = app('App\Services\RolePermissionService');
            $permissions = $rolePermissionService->getPermissionsForRoles($roles);
            
            // Store permissions in session
            $request->session()->put('permissions', $permissions);
            
            Log::info('Loaded permissions for user', [
                'username' => $username,
                'roles' => $roles,
                'permission_count' => count($permissions)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load permissions for user', [
                'username' => $username,
                'roles' => $roles,
                'error' => $e->getMessage()
            ]);
            // Continue without permissions - user can still access but with limited rights
        }
    }

}
