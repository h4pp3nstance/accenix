<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\RolePermissionService;

class WSO2PermissionMiddleware
{
    private $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permissions
     * @param  string  $logic (and|or) - default 'or'
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permissions, $logic = 'or')
    {
        // Check if user is authenticated
        if (!session()->has('access_token') || !session()->has('roles')) {
            Log::warning('WSO2PermissionMiddleware: Unauthenticated access attempt', [
                'url' => $request->url(),
                'ip' => $request->ip()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        // Parse required permissions
        $requiredPermissions = explode(',', $permissions);
        $requiredPermissions = array_map('trim', $requiredPermissions);

        // Get user roles from session
        $userRoles = session('user_info.roles', []);
        // Ensure $userRoles is always an array
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        if (!is_array($userRoles)) {
            $userRoles = [];
        }
        $roleNames = $this->extractRoleNames($userRoles);

        Log::info('WSO2PermissionMiddleware: Checking permissions', [
            'user_roles' => $roleNames,
            'required_permissions' => $requiredPermissions,
            'logic' => $logic,
            'url' => $request->url()
        ]);

        // Check permissions based on logic
        $hasAccess = false;
        if ($logic === 'and') {
            $hasAccess = $this->rolePermissionService->hasAllPermissions($roleNames, $requiredPermissions);
        } else {
            $hasAccess = $this->rolePermissionService->hasAnyPermission($roleNames, $requiredPermissions);
        }

        if (!$hasAccess) {
            Log::warning('WSO2PermissionMiddleware: Access denied', [
                'user_roles' => $roleNames,
                'required_permissions' => $requiredPermissions,
                'logic' => $logic,
                'url' => $request->url(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have the required permissions to access this resource.'
                ], 403);
            }

            return redirect()->back()->with('error', 'You do not have the required permissions to access this resource.');
        }

        Log::info('WSO2PermissionMiddleware: Access granted', [
            'user_roles' => $roleNames,
            'required_permissions' => $requiredPermissions,
            'logic' => $logic,
            'url' => $request->url()
        ]);

        return $next($request);
    }

    /**
     * Extract role names from session roles data
     *
     * @param array $userRoles
     * @return array
     */
    private function extractRoleNames($userRoles)
    {
        $roleNames = [];
        // Ensure $userRoles is always an array
        if (!is_array($userRoles)) {
            $userRoles = [$userRoles];
        }
        foreach ($userRoles as $role) {
            if (is_object($role)) {
                $roleNames[] = $role->display ?? $role->value ?? '';
            } elseif (is_array($role)) {
                $roleNames[] = $role['display'] ?? $role['value'] ?? '';
            } elseif (is_string($role)) {
                $roleNames[] = $role;
            }
        }
        return array_filter($roleNames);
    }

    /**
     * Static method to check if user has specific permission
     *
     * @param string $permission
     * @return bool
     */
    public static function hasPermission($permission)
    {
        if (!session()->has('access_token')) {
            return false;
        }
        $userRoles = session('user_info.roles', []);
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        if (!is_array($userRoles)) {
            $userRoles = [];
        }
        $middleware = new self(app(RolePermissionService::class));
        $roleNames = $middleware->extractRoleNames($userRoles);
        return app(RolePermissionService::class)->hasPermission($roleNames, $permission);
    }

    /**
     * Static method to check if user has any of the specified permissions
     *
     * @param array $permissions
     * @return bool
     */
    public static function hasAnyPermission($permissions)
    {
        if (!session()->has('access_token')) {
            return false;
        }
        $userRoles = session('user_info.roles', []);
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        if (!is_array($userRoles)) {
            $userRoles = [];
        }
        $middleware = new self(app(RolePermissionService::class));
        $roleNames = $middleware->extractRoleNames($userRoles);
        return app(RolePermissionService::class)->hasAnyPermission($roleNames, $permissions);
    }

    /**
     * Static method to check if user has all of the specified permissions
     *
     * @param array $permissions
     * @return bool
     */
    public static function hasAllPermissions($permissions)
    {
        if (!session()->has('access_token')) {
            return false;
        }
        $userRoles = session('user_info.roles', []);
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        if (!is_array($userRoles)) {
            $userRoles = [];
        }
        $middleware = new self(app(RolePermissionService::class));
        $roleNames = $middleware->extractRoleNames($userRoles);
        return app(RolePermissionService::class)->hasAllPermissions($roleNames, $permissions);
    }
}
