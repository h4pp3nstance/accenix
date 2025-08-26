<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

trait OidcLogoutHandler
{
    protected function performOidcLogoutAndClearSession(Request $request)
    {
        $idToken = session('id_token');
        $this->callOidcLogoutEndpoint($idToken);
        $this->clearUserSession($request);
    }

    protected function callOidcLogoutEndpoint(?string $idToken): void
    {
        if (!$idToken) {
            Log::warning('OIDC logout skipped: no id_token');
            return;
        }
        try {
            $client = new Client(['verify' => false]);
            $logoutUrl = env('IS_LOGOUT_URL');
            $client->post($logoutUrl, [
                'form_params' => [
                    'id_token_hint' => $idToken,
                    'response_mode' => 'direct',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);
            Log::info('OIDC logout called at WSO2');
        } catch (\Exception $e) {
            Log::error('OIDC logout failed', ['error' => $e->getMessage()]);
        }
    }

    protected function clearUserSession(Request $request): void
    {
        $sessionId = $request->session()->getId();
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Log::debug('Session cleared for logout', [
            'old_session_id' => $sessionId,
            'new_session_id' => $request->session()->getId()
        ]);
    }
}
