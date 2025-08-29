<?php

use Illuminate\Support\Facades\Http;

/**
 * Get OAuth2 access token from API using client credentials grant.
 * @return string Access token
 * @throws Exception if token fetch fails
 */
function getTokenApi()
{
    $tokenurl = env('API_TOKEN_URL');
    $clientid = env('API_CLIENT_ID');
    $clientsecret = env('API_CLIENT_SECRET');

    $response = Http::asForm()->post($tokenurl, [
        'grant_type' => 'client_credentials',
        'client_id' => $clientid,
        'client_secret' => $clientsecret,
    ]);

    if (!$response->successful()) {
        throw new \Exception('Failed to fetch access token');
    }
    return $response->json()['access_token'];
}

/**
 * Fetch data from API endpoint using Bearer token.
 * @param string $apiUrl
 * @return \Illuminate\Http\Client\Response
 */
function fectApi($apiUrl)
{
    $access_token = getTokenApi();
    if (!$access_token) {
        throw new \Exception('Access token is missing from session.');
    }
    $response = Http::withOptions(['verify' => false])
        ->withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/json',
        ])
        ->get($apiUrl);
    return $response;
}

/**
 * Store (POST) data to API endpoint using Bearer token.
 * @param string $apiUrl
 * @param array $payloads
 * @return \Illuminate\Http\Client\Response
 */
function storeApi($apiUrl, $payloads)
{
    $access_token = getTokenApi();
    $response = Http::withOptions(['verify' => false])
        ->withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
        ])
        ->withBody(json_encode($payloads), 'application/json')
        ->post($apiUrl);
    return $response;
}

/**
 * Update (PUT) data to API endpoint using Bearer token.
 * @param string $apiUrl
 * @param array $payloads
 * @return \Illuminate\Http\Client\Response
 */
function updateApi($apiUrl, $payloads)
{
    $access_token = getTokenApi();
    $response = Http::withOptions(['verify' => false])
        ->withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
        ])
        ->withBody(json_encode($payloads), 'application/json')
        ->put($apiUrl);

    return $response;
}

/**
 * Delete data from API endpoint using Bearer token.
 * @param string $apiUrl
 * @return \Illuminate\Http\Client\Response
 */
function deleteApi($apiUrl)
{
    $access_token = getTokenApi();
    $response = Http::withOptions(['verify' => false])
        ->withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
        ])
        ->delete($apiUrl);

    return $response;
}

/**
 * Get user data from API using Basic Auth (username & password).
 * @param string $apiUrl
 * @param string $username
 * @param string $password
 * @return \Illuminate\Http\Client\Response
 * @deprecated Use getUserWithToken instead
 */
function getUser($apiUrl, $username, $password)
{
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . base64_encode("$username:$password"),
        'Content-Type'  => 'application/json'
    ])->withoutVerifying()->get($apiUrl);

    return $response;
}

/**
 * Get user data from API using OAuth token (client credentials).
 * @param string $apiUrl
 * @return \Illuminate\Http\Client\Response
 */
function getUserWithToken($apiUrl)
{
    $access_token = session('access_token');
    if (!$access_token) {
        throw new \Exception('Access token is missing from session.');
    }

    $response = Http::withOptions(['verify' => false])
        ->withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/scim+json',
            'Content-Type' => 'application/json'
        ])
        ->get($apiUrl);

    return $response;
}

/**
 * Get specific user data by ID using OAuth token (client credentials).
 * @param string $apiUrl
 * @param string $userId
 * @return \Illuminate\Http\Client\Response
 */
function getUserDetailWithToken($apiUrl, $userId)
{
    $access_token = session('access_token');
    if (!$access_token) {
        throw new \Exception('Access token is missing from session.');
    }
    
    $response = Http::withOptions(['verify' => false])
        ->withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/scim+json',
            'Content-Type' => 'application/json'
        ])
        ->get($apiUrl . '/' . $userId);

    return $response;
}

// ========================================
// WSO2 RBAC HELPER FUNCTIONS
// ========================================

use App\Http\Middleware\WSO2RoleMiddleware;
use App\Http\Middleware\WSO2ScopeMiddleware;
use Illuminate\Support\Facades\Session;

if (!function_exists('hasRole')) {
    /**
     * Check if current user has specific role.
     * @param string $role
     * @return bool
     */
    function hasRole($role): bool
    {
        return WSO2RoleMiddleware::hasRole($role);
    }
}

if (!function_exists('hasAnyRole')) {
    /**
     * Check if current user has any of the specified roles.
     * @param array|string $roles
     * @return bool
     */
    function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }
        return WSO2RoleMiddleware::hasAnyRole($roles);
    }
}

if (!function_exists('hasScope')) {
    /**
     * Check if current user has specific scope/permission.
     * @param string $scope
     * @return bool
     */
    function hasScope($scope): bool
    {
        return WSO2ScopeMiddleware::hasScope($scope);
    }
}

if (!function_exists('hasAnyScope')) {
    /**
     * Check if current user has any of the specified scopes.
     * @param array|string $scopes
     * @return bool
     */
    function hasAnyScope($scopes): bool
    {
        if (is_string($scopes)) {
            $scopes = explode(',', $scopes);
        }
        return WSO2ScopeMiddleware::hasAnyScope($scopes);
    }
}

if (!function_exists('hasAllScopes')) {
    /**
     * Check if current user has all of the specified scopes.
     * @param array|string $scopes
     * @return bool
     */
    function hasAllScopes($scopes): bool
    {
        if (is_string($scopes)) {
            $scopes = explode(',', $scopes);
        }
        return WSO2ScopeMiddleware::hasAllScopes($scopes);
    }
}

if (!function_exists('isSuperAdmin')) {
    /**
     * Check if current user is superadmin.
     * @return bool
     */
    function isSuperAdmin(): bool
    {
        return hasRole('superadmin');
    }
}

if (!function_exists('isDev')) {
    /**
     * Check if current user is dev.
     * @return bool
     */
    function isDev(): bool
    {
        return hasRole('dev');
    }
}

if (!function_exists('isViewer')) {
    /**
     * Check if current user is viewer.
     * @return bool
     */
    function isViewer(): bool
    {
        return hasRole('viewer');
    }
}

if (!function_exists('canManageUsers')) {
    /**
     * Check if user can manage users (has user creation/update/delete permissions).
     * @return bool
     */
    function canManageUsers(): bool
    {
        return hasAnyScope(['user:create', 'user:update', 'user:delete']);
    }
}

if (!function_exists('canManageItems')) {
    /**
     * Check if user can manage items (has item create/update/delete permissions).
     * @return bool
     */
    function canManageItems(): bool
    {
        return hasAnyScope(['item:create', 'item:update', 'item:delete']);
    }
}

if (!function_exists('getRolesApi')) {
    /**
     * Fetch roles using Bearer token from IS (using getTokenISApi) and env('ROLES_URL').
     * @return \Illuminate\Http\Client\Response
     */
    function getRolesApi()
    {
        $access_token = \App\Helpers\Session::get('access_token');
        $rolesUrl = env('ROLES_URL');
        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/scim+json',
            ])
            ->get($rolesUrl);
        return $response;
    }
}

if (!function_exists('normalizeRoles')) {
    /**
     * Normalize roles input to always be an array of strings.
     * @param array|string $roles
     * @return array
     */
    function normalizeRoles($roles)
    {
        if (!is_array($roles)) {
            return [$roles];
        }
        return array_filter($roles, function($r) { return !empty($r); });
    }
}

if (!function_exists('extractRoleIds')) {
    /**
     * Extract role IDs from user roles array (object or array).
     * @param array $roles
     * @return array
     */
    function extractRoleIds($roles)
    {
        $ids = [];
        foreach ($roles as $role) {
            if (is_object($role) && isset($role->id)) {
                $ids[] = $role->id;
            } elseif (is_array($role) && isset($role['id'])) {
                $ids[] = $role['id'];
            } elseif (is_object($role) && isset($role->value)) {
                $ids[] = $role->value;
            } elseif (is_array($role) && isset($role['value'])) {
                $ids[] = $role['value'];
            }
        }
        return $ids;
    }
}

if (!function_exists('getRolePermissionService')) {
    /**
     * Get RolePermissionService instance.
     * @return \App\Services\RolePermissionService
     */
    function getRolePermissionService()
    {
        return app(\App\Services\RolePermissionService::class);
    }
}

if (!function_exists('hasPermission')) {
    /**
     * Check if current user has specific permission based on their roles.
     * @param string $permission
     * @return bool
     */
    function hasPermission($permission): bool
    {
        // Check if user is authenticated
        if (!session()->has('access_token') || !session()->has('roles')) {
            return false;
        }
        
            $userRoles = session('user_info.roles', []);
        if (empty($userRoles)) {
            return false;
        }
        
        // Ensure userRoles is an array (handle single role as string)
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        
        // Extract role names from session data
        $roleNames = [];
        foreach ($userRoles as $role) {
            if (is_object($role)) {
                $roleNames[] = $role->display ?? $role->value ?? '';
            } elseif (is_array($role)) {
                $roleNames[] = $role['display'] ?? $role['value'] ?? '';
            } elseif (is_string($role)) {
                $roleNames[] = $role;
            }
        }
        
        $roleNames = array_filter($roleNames);
        
        if (empty($roleNames)) {
            return false;
        }
        
        try {
            return getRolePermissionService()->hasPermission($roleNames, $permission);
        } catch (\Exception $e) {
            \Log::error('hasPermission helper error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('hasAnyPermission')) {
    /**
     * Check if current user has any of the specified permissions.
     * @param array|string $permissions
     * @return bool
     */
    function hasAnyPermission($permissions): bool
    {
        // Check if user is authenticated
        if (!session()->has('access_token') || !session()->has('roles')) {
            return false;
        }
        
        if (is_string($permissions)) {
            $permissions = explode(',', $permissions);
        }
        
            $userRoles = session('user_info.roles', []);
        if (empty($userRoles)) {
            return false;
        }
        
        // Ensure userRoles is an array (handle single role as string)
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        
        // Extract role names from session data
        $roleNames = [];
        foreach ($userRoles as $role) {
            if (is_object($role)) {
                $roleNames[] = $role->display ?? $role->value ?? '';
            } elseif (is_array($role)) {
                $roleNames[] = $role['display'] ?? $role['value'] ?? '';
            } elseif (is_string($role)) {
                $roleNames[] = $role;
            }
        }
        
        $roleNames = array_filter($roleNames);
        
        if (empty($roleNames)) {
            return false;
        }
        
        try {
            return getRolePermissionService()->hasAnyPermission($roleNames, $permissions);
        } catch (\Exception $e) {
            \Log::error('hasAnyPermission helper error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('hasAllPermissions')) {
    /**
     * Check if current user has all of the specified permissions.
     * @param array|string $permissions
     * @return bool
     */
    function hasAllPermissions($permissions): bool
    {
        // Check if user is authenticated
        if (!session()->has('access_token') || !session()->has('roles')) {
            return false;
        }
        
        if (is_string($permissions)) {
            $permissions = explode(',', $permissions);
        }
        
            $userRoles = session('user_info.roles', []);
        if (empty($userRoles)) {
            return false;
        }
        
        // Ensure userRoles is an array (handle single role as string)
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        
        // Extract role names from session data
        $roleNames = [];
        foreach ($userRoles as $role) {
            if (is_object($role)) {
                $roleNames[] = $role->display ?? $role->value ?? '';
            } elseif (is_array($role)) {
                $roleNames[] = $role['display'] ?? $role['value'] ?? '';
            } elseif (is_string($role)) {
                $roleNames[] = $role;
            }
        }
        
        $roleNames = array_filter($roleNames);
        
        if (empty($roleNames)) {
            return false;
        }
        
        try {
            return getRolePermissionService()->hasAllPermissions($roleNames, $permissions);
        } catch (\Exception $e) {
            \Log::error('hasAllPermissions helper error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getUserPermissions')) {
    /**
     * Get all permissions for current user based on their roles.
     * @return array
     */
    function getUserPermissions(): array
    {
        // Check if user is authenticated
        if (!session()->has('access_token') || !session()->has('roles')) {
            return [];
        }
        
        $userRoles = session('roles', []);
        if (empty($userRoles)) {
            return [];
        }
        
        // Ensure userRoles is an array (handle single role as string)
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        
        // Extract role names from session data
        $roleNames = [];
        foreach ($userRoles as $role) {
            if (is_object($role)) {
                $roleNames[] = $role->display ?? $role->value ?? '';
            } elseif (is_array($role)) {
                $roleNames[] = $role['display'] ?? $role['value'] ?? '';
            } elseif (is_string($role)) {
                $roleNames[] = $role;
            }
        }
        
        $roleNames = array_filter($roleNames);
        
        if (empty($roleNames)) {
            return [];
        }
        
        try {
            return getRolePermissionService()->getUserPermissions($roleNames);
        } catch (\Exception $e) {
            \Log::error('getUserPermissions helper error: ' . $e->getMessage());
            return [];
        }
    }
}