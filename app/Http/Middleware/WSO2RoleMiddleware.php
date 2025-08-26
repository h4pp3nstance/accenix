<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class WSO2RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$allowedRoles): Response
    {
        \Log::debug('WSO2RoleMiddleware: Check access', [
            'url' => $request->url(),
            'allowedRoles' => $allowedRoles,
            'has_access_token' => Session::has('access_token'),
            'session_roles' => Session::get('user_info.roles', [])
        ]);
        
        // Check if user is authenticated
        if (!Session::has('access_token')) {
            \Log::warning('WSO2RoleMiddleware: No access token, redirecting to home');
            return redirect('/')->with('error', 'You must be logged in to access this page.');
        }

        // Get user roles from user_info (decoded JWT)
        $userRoles = Session::get('user_info.roles', []);
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        if (!is_array($userRoles)) {
            $userRoles = [];
        }
        
        // If no specific roles required, just check authentication
        if (empty($allowedRoles)) {
            return $next($request);
        }

        // Check if user has any of the required roles (case-insensitive)
        $userRolesLower = array_map('strtolower', $userRoles);
        $allowedRolesLower = array_map('strtolower', $allowedRoles);
        $hasRequiredRole = !empty(array_intersect($userRolesLower, $allowedRolesLower));

        if (!$hasRequiredRole) {
            \Log::warning('WSO2RoleMiddleware: Access denied', [
                'required_roles' => $allowedRoles,
                'user_roles' => $userRoles,
                'url' => $request->url()
            ]);
            // Return 403 with error message
            return response()->view('errors.403', [
                'message' => 'You do not have the required permissions to access this resource.',
                'required_roles' => $allowedRoles,
                'user_roles' => $userRoles
            ], 403);
        }

        \Log::debug('WSO2RoleMiddleware: Access granted', [
            'user_roles' => $userRoles,
            'url' => $request->url()
        ]);
        
        return $next($request);
    }

    /**
     * Check if user has specific role (case-insensitive)
     */
    public static function hasRole($role): bool
    {
        $userRoles = Session::get('user_info.roles', []);
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        if (!is_array($userRoles)) {
            $userRoles = [];
        }
        $userRolesLower = array_map('strtolower', $userRoles);
        return in_array(strtolower($role), $userRolesLower);
    }

    /**
     * Check if user has any of the specified roles (case-insensitive)
     */
    public static function hasAnyRole(array $roles): bool
    {
        $userRoles = Session::get('user_info.roles', []);
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        if (!is_array($userRoles)) {
            $userRoles = [];
        }
        $userRolesLower = array_map('strtolower', $userRoles);
        $rolesLower = array_map('strtolower', $roles);
        return !empty(array_intersect($userRolesLower, $rolesLower));
    }

    /**
     * Get user roles
     */
    public static function getUserRoles(): array
    {
        $userRoles = Session::get('user_info.roles', []);
        if (is_string($userRoles)) {
            $userRoles = [$userRoles];
        }
        if (!is_array($userRoles)) {
            $userRoles = [];
        }
        return $userRoles;
    }
}
