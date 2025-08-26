<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class WSO2ScopeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$requiredScopes): Response
    {
        // Check if user is authenticated
        if (!Session::has('access_token')) {
            return redirect('/')->with('error', 'You must be logged in to access this page.');
        }

        // Get user scope from session
        $userScope = Session::get('scope', '');
        $userScopes = explode(' ', $userScope);

        // If no specific scopes required, just check authentication
        if (empty($requiredScopes)) {
            return $next($request);
        }

        // Check if user has all required scopes
        $hasAllScopes = empty(array_diff($requiredScopes, $userScopes));

        if (!$hasAllScopes) {
            // Return 403 with error message
            return response()->view('errors.403', [
                'message' => 'You do not have the required permissions to perform this action.',
                'required_scopes' => $requiredScopes,
                'user_scopes' => $userScopes
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user has specific scope
     */
    public static function hasScope($scope): bool
    {
        $userScope = Session::get('scope', '');
        $userScopes = explode(' ', $userScope);
        return in_array($scope, $userScopes);
    }

    /**
     * Check if user has all of the specified scopes
     */
    public static function hasAllScopes(array $scopes): bool
    {
        $userScope = Session::get('scope', '');
        $userScopes = explode(' ', $userScope);
        return empty(array_diff($scopes, $userScopes));
    }

    /**
     * Check if user has any of the specified scopes
     */
    public static function hasAnyScope(array $scopes): bool
    {
        $userScope = Session::get('scope', '');
        $userScopes = explode(' ', $userScope);
        return !empty(array_intersect($scopes, $userScopes));
    }

    /**
     * Get user scopes
     */
    public static function getUserScopes(): array
    {
        $userScope = Session::get('scope', '');
        return explode(' ', $userScope);
    }
}
