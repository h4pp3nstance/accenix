<?php

namespace App\Http\Controllers\Auth;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use GuzzleHttp\Exception\RequestException;
use App\Helpers\ScimHelper;
use App\Http\Requests\ProfilePhotoRequest;
use App\Traits\JwtHandler;
use App\Traits\OAuthHandler;
use App\Traits\UserProfileHandler;
use App\Traits\SessionHandler;
use App\Traits\OidcLogoutHandler;
use App\Traits\ConfigurationHandler;
use App\Traits\ResponseFormatter;
use App\Traits\RedirectHandler;
use App\Traits\CallbackHandler;
use App\Traits\AuthLogoutHandler;
use App\Traits\ProfileControllerHandler;
use App\Traits\PhotoManagementHandler;
use App\Traits\ProfilePageHandler;

class AuthController extends Controller
{
    use JwtHandler, OAuthHandler, UserProfileHandler,
        SessionHandler, OidcLogoutHandler, ConfigurationHandler,
        ResponseFormatter, RedirectHandler, CallbackHandler,
        AuthLogoutHandler, ProfileControllerHandler, PhotoManagementHandler, ProfilePageHandler;

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

    public function logout(Request $request)
    {
        return $this->handleStandardLogout($request);
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
     * @param ProfilePhotoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfilePhoto(ProfilePhotoRequest $request)
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
