<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\ScimHelper;

/**
 * ProfilePageHandler - Manages profile page operations and SCIM integration
 * 
 * This trait handles:
 * - User access validation
 * - SCIM user detail fetching
 * - Profile page data preparation
 */
trait ProfilePageHandler
{
    /**
     * Check if user is authenticated and token is valid
     *
     * @return \Illuminate\Http\Response|null
     */
    protected function validateUserAccess()
    {
        if (!$this->isAuthenticated()) {
            return redirect('/')->with('error', 'You must be logged in to access this page.');
        }
        
        if (!$this->ensureValidToken()) {
            return $this->securityLogout(request(), 'Token expired or invalid');
        }
        
        return null;
    }

    /**
     * Fetch user detail from SCIM API
     *
     * @param string $userName
     * @return array
     */
    protected function fetchUserDetailFromScim(string $userName): array
    {
        if (empty($userName)) {
            return [];
        }

        try {
            $access_token = Session::get('access_token');
            
            // Search user by username using SCIM filter
            $searchResponse = Http::withOptions(['verify' => app()->environment('production')])
                ->withHeaders([
                    'accept' => 'application/scim+json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token,
                ])
                ->get(config('auth-custom.scim.user_url') . '?filter=userName eq "' . $userName . '"');
                
            if (!$searchResponse->successful()) {
                Log::warning('SCIM user search failed', [
                    'username' => $userName,
                    'status' => $searchResponse->status()
                ]);
                return [];
            }

            $searchData = $searchResponse->json();
            
            if (empty($searchData['Resources']) || !isset($searchData['Resources'][0]['id'])) {
                Log::info('User not found in SCIM', ['username' => $userName]);
                return [];
            }

            $userId = $searchData['Resources'][0]['id'];
            $userDetailResponse = ScimHelper::getUserDetailWithToken(config('auth-custom.scim.user_url'), $userId);
            //dd($userDetailResponse->json()); // Debugging line to inspect the response
            
            if ($userDetailResponse->successful()) {
                Log::info('Successfully fetched user detail from SCIM', ['username' => $userName]);
                return $userDetailResponse->json();
            }

            Log::warning('Failed to fetch user detail from SCIM', [
                'username' => $userName,
                'user_id' => $userId,
                'status' => $userDetailResponse->status()
            ]);
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('Error fetching user profile from SCIM', [
                'username' => $userName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * Prepare profile page data
     *
     * @return array
     */
    protected function prepareProfilePageData(): array
    {
        $userInfo = Session::get('user_info', []);
        $userName = $userInfo['username'] ?? '';
        
        Log::info('Profile page data preparation', [
            'user_info' => $userInfo,
            'username' => $userName,
            'session_keys' => array_keys(Session::all())
        ]);
        
        $userDetail = $this->fetchUserDetailFromScim($userName);
        
        return [
            'user' => $userInfo,
            'userDetail' => $userDetail,
            'csrf_token' => csrf_token()
        ];
    }
}
