<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ConfigurationHandler;
use App\Traits\ResponseFormatter;
use App\Traits\RedirectHandler;
use App\Traits\CallbackHandler;
use App\Traits\UserProfileHandler;
use App\Traits\ProfileControllerHandler;
use App\Traits\OAuthHandler;
use App\Traits\JwtHandler;
use App\Traits\SessionHandler;
use App\Traits\OidcLogoutHandler;
use App\Traits\AuthLogoutHandler;
use App\Traits\NativeAuthHandler;
use App\Traits\NativeLoginControllerHandler;


use App\Traits\ProfilePageHandler;

class NativeLoginController extends Controller
{
    use JwtHandler, SessionHandler, OidcLogoutHandler,
        AuthLogoutHandler, NativeAuthHandler, NativeLoginControllerHandler,
        ConfigurationHandler, ResponseFormatter, RedirectHandler, CallbackHandler,
        UserProfileHandler, ProfileControllerHandler, OAuthHandler, ProfilePageHandler;

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.native-login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        // Get organization name from request
        $organizationInput = trim($request->input('organization'));
        
        // If organization is empty, default to carbon.super
        if (empty($organizationInput)) {
            \Log::info('Native Login: Empty organization provided, defaulting to carbon.super');
            return $this->handleCarbonSuperLogin($request);
        }
        
        // For sub-organization login
        $organizationName = strtolower($organizationInput);
        $organizationId = \App\Helpers\OrganizationHelper::getOrganizationIdByName($organizationName);
        
        \Log::info('Native Login: Sub-organization login', [
            'organization' => $organizationName,
            'org_id' => $organizationId
        ]);
        
        // Pass organization name (not ID) to trait method
        return $this->handleNativeLogin($request, $organizationName);
    }
    
    /**
     * Handle carbon.super login (admin access)
     */
    private function handleCarbonSuperLogin(Request $request)
    {
        \Log::info('Native Login: Initiating carbon.super login', [
            'username' => $request->input('username'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // For carbon.super, we use null organization to trigger the special flow
        return $this->handleNativeLogin($request, null);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        \Log::debug('NativeLogout: logout method entered');
        return $this->handleStandardLogout($request);
    }

    /**
     * Redirect user to Identity Server for OAuth authentication
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToIdentityServer()
    {
        return $this->handleRedirectToIdentityServer();
    }

    /**
     * Handle OAuth callback from Identity Server
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function handleCallback(Request $request)
    {
        return $this->handleOAuthCallback($request);
    }

    /**
     * Logout with custom redirect
     */
    public function logoutWithRedirect(Request $request, string $redirectUrl = null)
    {
        return $this->handleLogoutWithCustomRedirect($request, $redirectUrl);
    }

    /**
     * API logout endpoint (returns JSON)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiLogout(Request $request)
    {
        return $this->handleApiLogout($request);
    }

    /**
     * Security logout (for expired tokens, suspicious activity, etc.)
     */
    public function securityLogout(Request $request, string $reason = 'Security logout')
    {
        return $this->handleSecurityLogout($request, $reason);
    }

    /**
     * Handle profile photo upload or removal from profile page
     *
     * @param \App\Http\Requests\ProfilePhotoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfilePhoto(\App\Http\Requests\ProfilePhotoRequest $request)
    {
        return $this->handleProfilePhotoUpdateRequest($request);
    }

    /**
     * Show profile page with user detail data from SCIM
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showProfilePage()
    {
        return $this->handleShowProfilePage();
    }
}
