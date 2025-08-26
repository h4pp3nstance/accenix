<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\ScimHelper;

trait UserProfileHandler
{
    /**
     * Fetch user photo from SCIM
     */
    protected function fetchUserPhotoFromScim(string $username): ?string
    {
        try {
            $access_token = Session::get('access_token');
            
            $searchResp = Http::withOptions(['verify' => app()->environment('production')])
                ->withHeaders([
                    'accept' => 'application/scim+json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token,
                ])
                ->get(config('auth-custom.scim.user_url') . '?filter=userName eq "' . $username . '"');
                
            if ($searchResp->successful()) {
                $searchData = json_decode($searchResp->body());
                if (!empty($searchData->Resources) && isset($searchData->Resources[0]->id)) {
                    $userId = $searchData->Resources[0]->id;
                    $userDetailResp = ScimHelper::getUserDetailWithToken(env('USER_URL'), $userId);
                    if ($userDetailResp->successful()) {
                        $userDetail = $userDetailResp->json();
                        if (!empty($userDetail['photos']) && is_array($userDetail['photos'])) {
                            return $userDetail['photos'][0]['value'] ?? null;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching user photo from SCIM: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Build user info array from JWT payload
     */
    protected function buildUserInfo(array $jwtPayload, ?string $photoUrl = null): array
    {
        $roles = $jwtPayload['roles'] ?? [];
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return [
            'username' => $jwtPayload['username'] ?? '',
            'roles' => $roles,
            'email' => $jwtPayload['email'] ?? '',
            'Photo' => $photoUrl,
        ];
    }
}
