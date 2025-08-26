<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class ScimHelper
{
    /**
     * Ambil detail user SCIM berdasarkan userId (dengan Basic Auth).
     * @param string $apiUrl
     * @param string $userId
     * @param string $username
     * @param string $password
     * @return \Illuminate\Http\Client\Response
     * @deprecated Use getUserDetailWithToken instead
     */
    public static function getUserDetail(string $apiUrl, string $userId, string $username, string $password)
    {
        return Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            'Content-Type'  => 'application/json'
        ])->withoutVerifying()->get($apiUrl . '/' . $userId);
    }
    
    /**
     * Ambil detail user SCIM berdasarkan userId (dengan Token Auth).
     * @param string $apiUrl
     * @param string $userId
     * @return \Illuminate\Http\Client\Response
     */
    public static function getUserDetailWithToken(string $apiUrl, string $userId)
    {
        try {
            if (empty($apiUrl) || empty($userId)) {
                \Log::warning('SCIM getUserDetailWithToken called with empty parameters');
                throw new \InvalidArgumentException('API URL and User ID are required');
            }
            
            $access_token = Session::get('access_token');
            if (!$access_token)  {
                throw new \Exception('Access token is missing from session.');
            }
            
            // Aktifkan SSL verification di production
            $verifySSL = app()->environment('production');
            
            // Validasi userId untuk mencegah injeksi
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $userId)) {
                \Log::warning('SCIM getUserDetailWithToken called with invalid userId format');
                throw new \InvalidArgumentException('Invalid User ID format');
            }
            
            return Http::withOptions(['verify' => $verifySSL])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept' => 'application/scim+json',
                    'Content-Type' => 'application/json'
                ])
                ->get($apiUrl . '/' . $userId);
        } catch (\Throwable $e) {
            \Log::error('SCIM getUserDetail error: ' . $e->getMessage());
            
            // Return failed response instead of throwing exception
            return Http::response([
                'success' => false,
                'message' => 'Failed to fetch user details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ambil access token dari Identity Server (WSO2 SCIM) menggunakan client credentials.
     * @return string
     * @throws \Exception jika gagal mendapatkan token
     */
    public static function getTokenISApi(): string
    {
        $tokenurl = config('auth-custom.scim.token_url');
        $clientid = config('auth-custom.scim.client_id');
        $clientsecret = config('auth-custom.scim.client_secret');
        
        if (empty($tokenurl) || empty($clientid) || empty($clientsecret)) {
            \Log::error('SCIM configuration missing: token URL or client credentials');
            throw new \Exception('Missing SCIM API configuration');
        }

        // Define the required scopes for SCIM API token as a single array

        // Define the required scopes for SCIM API token as an array
        $scopes = [
            'openid',
            'profile',
            'roles',
            'SYSTEM',
        ];

        $scope = implode(' ', $scopes);
        
        $authorization = base64_encode("$clientid:$clientsecret");
        
        try {
            // Aktifkan SSL verification di production
            $verifySSL = app()->environment('production');
            $response = Http::asForm()->withOptions(['verify' => $verifySSL])
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . $authorization,
                ])
                ->post($tokenurl, [
                    'grant_type' => 'client_credentials',
                    'scope' => $scope,
                ]);
                
            if (!$response->successful()) {
                $error = $response->body();
                \Log::error('SCIM API token request failed: ' . $error);
                throw new \Exception('Failed to fetch IS access token: ' . (is_string($error) ? $error : 'Unknown error'));
            }
            
            $data = $response->json();
            if (empty($data['access_token'])) {
                \Log::error('SCIM API response missing access_token');
                throw new \Exception('Invalid token response from SCIM API');
            }
            
            return $data['access_token'];
            
        } catch (\Throwable $e) {
            \Log::error('SCIM API token exception: ' . $e->getMessage());
            throw new \Exception('Failed to fetch IS access token: ' . $e->getMessage());
        }
    }

    /**
     * Get access token for Identity Server Application Management API
     * @return string
     * @throws \Exception if fails to get token
     */
    public static function getTokenForApplicationAPI(): string
    {
        $tokenurl = env('IS_TOKEN_URL');
        $clientid = env('IS_CLIENT_ID');
        $clientsecret = env('IS_CLIENT_SECRET');
        
        if (empty($tokenurl) || empty($clientid) || empty($clientsecret)) {
            throw new \Exception('Missing IS token configuration in .env file');
        }

        try {
            $response = Http::withOptions(['verify' => !app()->environment('local')])
                ->asForm()
                ->post($tokenurl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientid,
                    'client_secret' => $clientsecret,
                    'scope' => 'internal_application_mgt_view'
                ]);

            if (!$response->successful()) {
                \Log::error('Failed to get IS token: ' . $response->body());
                throw new \Exception('Failed to get IS token: ' . $response->body());
            }

            $data = $response->json();
            
            if (empty($data['access_token'])) {
                throw new \Exception('No access token in IS API response');
            }

            return $data['access_token'];
        } catch (\Exception $e) {
            \Log::error('Error getting IS token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build array phoneNumbers sesuai format SCIM dari input request.
     * @param array $input
     * @return array
     */
    public static function buildPhoneNumbers(array $input): array
    {
        $phones = $input['phone'] ?? [];
        $phone_types = $input['phone_type'] ?? [];
        $phone_type_others = $input['phone_type_other'] ?? [];
        $phoneNumbers = [];
        foreach ($phones as $i => $number) {
            if (empty($number)) continue;
            $type = $phone_types[$i] ?? 'mobile';
            if ($type === 'other' && !empty($phone_type_others[$i] ?? '')) {
                $type = $phone_type_others[$i];
            }
            $phoneNumbers[] = [
                'type' => $type,
                'value' => $number
            ];
        }
        return $phoneNumbers;
    }

    /**
     * Build array addresses sesuai format SCIM dari input request.
     * @param array $input
     * @return array
     */
    public static function buildAddresses(array $input): array
    {
        $street_addresses = $input['street_address'] ?? [];
        $cities = $input['city'] ?? [];
        $provinces = $input['province'] ?? [];
        $postal_codes = $input['postal_code'] ?? [];
        $countries = $input['country'] ?? [];
        $address_types = $input['address_type'] ?? [];
        $address_type_others = $input['address_type_other'] ?? [];
        $addressGroup = [];
        $maxAddr = max(count($street_addresses), count($cities), count($provinces), count($postal_codes), count($countries), count($address_types));
        for ($i = 0; $i < $maxAddr; $i++) {
            $street = $street_addresses[$i] ?? '';
            $city = $cities[$i] ?? '';
            $province = $provinces[$i] ?? '';
            $postal = $postal_codes[$i] ?? '';
            $countryVal = $countries[$i] ?? '';
            $type = $address_types[$i] ?? 'home';
            if ($type === 'other' && !empty($address_type_others[$i] ?? '')) {
                $type = $address_type_others[$i];
            }
            $formatted = trim(implode(', ', array_filter([$street, $city, $province, $postal, $countryVal])));
            if ($formatted === '') continue;
            if (!isset($addressGroup[$type])) {
                $addressGroup[$type] = [
                    'type' => $type,
                    'formatted' => $formatted
                ];
            } else {
                $addressGroup[$type]['formatted'] .= "\n" . $formatted;
            }
        }
        return array_values($addressGroup);
    }

    /**
     * Build SCIM PATCH operations array untuk update user/profile (name, email, phone, address, password, photo).
     * @param array $data (expects keys: first_name, family_name, email, phoneNumbers, addresses, password, photo)
     * @return array
     */
    public static function buildUserPatchOperations(array $data): array
    {
        $ops = [];
        if (isset($data['first_name'])) {
            $ops[] = [
                'op' => 'replace',
                'path' => 'name.givenName',
                'value' => $data['first_name'],
            ];
        }
        if (isset($data['family_name'])) {
            $ops[] = [
                'op' => 'replace',
                'path' => 'name.familyName',
                'value' => $data['family_name'],
            ];
        }
        if (isset($data['email'])) {
            $ops[] = [
                'op' => 'replace',
                'path' => 'emails',
                'value' => [[ 'value' => $data['email'] ]],
            ];
        }
        if (isset($data['phoneNumbers'])) {
            $ops[] = [
                'op' => 'replace',
                'path' => 'phoneNumbers',
                'value' => $data['phoneNumbers'],
            ];
        }
        if (isset($data['addresses'])) {
            $ops[] = [
                'op' => 'replace',
                'path' => 'addresses',
                'value' => $data['addresses'],
            ];
        }
        if (isset($data['password']) && $data['password']) {
            $ops[] = [
                'op' => 'replace',
                'path' => 'password',
                'value' => $data['password'],
            ];
        }
        if (isset($data['photo']) && $data['photo']) {
            $ops[] = [
                'op' => 'replace',
                'path' => 'photos',
                'value' => [[ 'value' => $data['photo'], 'type' => 'photo' ]],
            ];
        }
        return $ops;
    }

    /**
     * Fetches authorized APIs (scopes) for an application using the WSO2 IS API
     * 
     * @return array List of authorized scopes for the application
     * @throws \Exception If fails to get authorized APIs
     */
    public static function getAuthorizedScopes(): array
    {
        try {
            $appId = config('auth-custom.oauth.app_id');
            if (empty($appId)) {
                \Log::error('Missing application ID in auth-custom config (oauth.app_id)');
                throw new \Exception('Application ID not configured');
            }

            // Get access token for API authentication
            // Try using IS token instead of SCIM token for application management API
            try {
                $access_token = self::getTokenForApplicationAPI();
            } catch (\Exception $e) {
                \Log::warning('Failed to get IS token, falling back to SCIM token: ' . $e->getMessage());
                $access_token = self::getTokenISApi();
            }
            
            // Verify SSL in production only
            $verifySSL = app()->environment('production');
            
            // Include tenant information in the URL
            $apiUrl = rtrim(env('IS_URL'), '/') . '/api/server/v1/applications/' . $appId . '/authorized-apis';
            
            \Log::debug('Calling authorized APIs endpoint: ' . $apiUrl);
            
            $response = Http::withOptions(['verify' => $verifySSL])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept' => 'application/json',
                    'User-Agent' => 'Laravel-Application/1.0',
                ])
                ->get($apiUrl);
            
            \Log::debug('API Response Status: ' . $response->status());
            \Log::debug('API Response Body: ' . $response->body());
            
            if (!$response->successful()) {
                $error = $response->body();
                \Log::error('Failed to fetch authorized APIs: ' . $error);
                throw new \Exception('Failed to fetch authorized APIs: ' . (is_string($error) ? $error : 'Unknown error'));
            }
            
            $data = $response->json();
            
            // Extract scopes from response based on the actual API structure
            $scopes = [];
            
            // Base scopes that are always needed
            $scopes = ['openid', 'profile', 'roles'];
            
            // Add authorized API scopes from response
            if (!empty($data) && is_array($data)) {
                foreach ($data as $api) {
                    if (!empty($api['authorizedScopes'])) {
                        foreach ($api['authorizedScopes'] as $scopeObj) {
                            $scopeName = $scopeObj['name'] ?? null;
                            if ($scopeName && !in_array($scopeName, $scopes)) {
                                $scopes[] = $scopeName;
                            }
                        }
                    }
                }
            }
            
            \Log::info('Extracted ' . count($scopes) . ' scopes from API response');
            
            // Fallback to cached scopes if empty result but API call was successful
            if (count($scopes) <= 3) { // Only base scopes
                $cachedScopes = \Cache::get('app_authorized_scopes');
                if (!empty($cachedScopes)) {
                    \Log::info('Using cached scopes due to empty API response');
                    return $cachedScopes;
                }
            }
            
            // Cache the scopes for 24 hours to reduce API calls
            \Cache::put('app_authorized_scopes', $scopes, 60 * 24); // 24 hours
            
            return $scopes;
        } catch (\Throwable $e) {
            \Log::error('Failed to get authorized scopes: ' . $e->getMessage());
            
            // If cached scopes exist, use them as fallback
            $cachedScopes = \Cache::get('app_authorized_scopes');
            if (!empty($cachedScopes)) {
                \Log::info('Using cached scopes due to API error');
                return $cachedScopes;
            }
            
            throw new \Exception('Failed to fetch authorized scopes: ' . $e->getMessage());
        }
    }
}
