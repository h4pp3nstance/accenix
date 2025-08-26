<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * NativeAuthHandler - Manages native application authentication
 * 
 * This trait handles:
 * - Native login validation
 * - WSO2 direct authentication flow
 * - Token exchange for native apps
 * - Error handling for native authentication
 */
trait NativeAuthHandler
{
    /**
     * Main entry for native login, now supports organizationId
     */
    public function handleNativeLogin(\Illuminate\Http\Request $request, $organizationName = null)
    {
        try {
            $credentials = $this->validateNativeLoginRequest($request);
            $this->logNativeAuthAttempt($credentials, $request);
            $client = $this->createNativeAuthClient();
            $userAgentHeader = $this->prepareUserAgent($credentials['client_user_agent'], $request);

            // Step 1: Initiate auth with org name (not orgId)
            $config = config('auth-custom.oauth');
            $state = bin2hex(random_bytes(8));
            $scope = implode(' ', $config['default_scopes']);
            $orgName = $organizationName ?? $request->input('organization');
            
            \Log::debug('NativeLogin: Step 1 - Initiate auth', ['username' => $credentials['username'], 'org' => $orgName]);
            
            // If no organization specified, handle carbon.super login
            if (empty($orgName)) {
                return $this->handleCarbonSuperAuth($request, $credentials, $client, $userAgentHeader, $config, $state, $scope);
            }
            
            // Handle sub-organization login
            $response1 = $client->post(rtrim($config['auth_url'], '?'), [
                'form_params' => [
                    'client_id' => $config['client_id'],
                    'response_type' => 'code',
                    'redirect_uri' => $config['redirect_uri'],
                    'scope' => $scope,
                    'state' => $state,
                    'response_mode' => 'direct',
                    'fidp' => 'OrganizationSSO',
                    'org' => $orgName,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => $userAgentHeader,
                ],
            ]);
            $initData = json_decode($response1->getBody(), true);
            \Log::debug('NativeLogin: Step 1 response', ['initData' => $initData]);

            // Step 2: Follow redirectUrl for sub-org auth
            $redirectUrl = $initData['nextStep']['authenticators'][0]['metadata']['additionalData']['redirectUrl'] ?? null;
            if (!$redirectUrl) {
                return response()->json(['error' => 'Organization not found or misconfigured.'], 422);
            }
            $response2 = $client->get($redirectUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => $userAgentHeader,
                ],
            ]);
            $subOrgData = json_decode($response2->getBody(), true);
            \Log::debug('NativeLogin: Step 2 response', ['subOrgData' => $subOrgData]);

            // Step 3: Authenticate with username/password in sub-org
            $subFlowId = $subOrgData['flowId'] ?? null;
            $basicAuth = $subOrgData['nextStep']['authenticators'][0] ?? null;
            $authnUrl = $subOrgData['links'][0]['href'] ?? null;
            if (!$subFlowId || !$basicAuth || !$authnUrl) {
                return response()->json(['error' => 'Sub-organization authentication failed.'], 422);
            }
            $response3 = $client->post($authnUrl, [
                'json' => [
                    'flowId' => $subFlowId,
                    'selectedAuthenticator' => [
                        'authenticatorId' => $basicAuth['authenticatorId'],
                        'params' => [
                            'username' => $credentials['username'],
                            'password' => $credentials['password'],
                        ],
                    ],
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => $userAgentHeader,
                ],
            ]);
            $subAuthData = json_decode($response3->getBody(), true);
            \Log::debug('NativeLogin: Step 3 response', ['subAuthData' => $subAuthData]);

            // Step 4: Complete auth in root org
            $rootFlowId = $initData['flowId'];
            $orgSsoAuth = $initData['nextStep']['authenticators'][0] ?? null;
            $rootAuthnUrl = $initData['links'][0]['href'] ?? null;
            if (!$rootFlowId || !$orgSsoAuth || !$rootAuthnUrl) {
                return response()->json(['error' => 'Root organization authentication failed.'], 422);
            }
            $response4 = $client->post($rootAuthnUrl, [
                'json' => [
                    'flowId' => $rootFlowId,
                    'selectedAuthenticator' => [
                        'authenticatorId' => $orgSsoAuth['authenticatorId'],
                        'params' => [
                            'code' => $subAuthData['authData']['code'],
                            'state' => $subAuthData['authData']['state'],
                        ],
                    ],
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => $userAgentHeader,
                ],
            ]);
            $rootAuthData = json_decode($response4->getBody(), true);
            \Log::debug('NativeLogin: Step 4 response', ['rootAuthData' => $rootAuthData]);

            // Step 5: Exchange code for token
            $finalCode = $rootAuthData['authData']['code'] ?? null;
            if (!$finalCode) {
                return response()->json(['error' => 'Failed to obtain authorization code.'], 401);
            }
            $response5 = $client->post($config['token_url'], [
                'form_params' => [
                    'client_id' => $config['client_id'],
                    'code' => $finalCode,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $config['redirect_uri'],
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => $userAgentHeader,
                ],
            ]);
            $tokens = json_decode($response5->getBody(), true);
            \Log::debug('NativeLogin: Step 5 response', ['tokens' => $tokens]);

            if (empty($tokens['id_token']) || empty($tokens['access_token'])) {
                return response()->json(['error' => 'Failed to obtain tokens.'], 401);
            }

            // Store tokens and user info in session
            $userInfo = $this->decodeJwtPayload($tokens['id_token']);

            // Ensure roles is always an array, never null
            $roles = $userInfo['roles'] ?? [];
            if (!is_array($roles)) {
                $roles = [];
                \Log::warning('NativeLogin: Invalid roles format in JWT token', [
                    'username' => $userInfo['username'] ?? null,
                    'roles_type' => gettype($userInfo['roles'] ?? null),
                    'roles_value' => $userInfo['roles'] ?? null
                ]);
            }

            session([
                'access_token' => $tokens['access_token'],
                'id_token' => $tokens['id_token'],
                'user_info' => $userInfo,
                'roles' => $roles,
            ]);
            \Log::debug('NativeLogin: Session set', [
                'username' => $userInfo['username'] ?? null,
                'roles' => $userInfo['roles'] ?? null,
            ]);

            return response()->json(['success' => true, 'redirect' => '/dashboard']);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            \Log::error('NativeLogin: RequestException', ['error' => $e->getMessage()]);
            return $this->handleNativeAuthRequestException($e);
        } catch (\Exception $e) {
            \Log::error('NativeLogin: General Exception', ['error' => $e->getMessage()]);
            return $this->handleNativeAuthGeneralException($e);
        }
    }
    /**
     * Validate native login request
     *
     * @param Request $request
     * @return array
     */
    protected function validateNativeLoginRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'client_user_agent' => 'nullable|string',
            'client_platform' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        return [
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'client_user_agent' => $request->input('client_user_agent'),
            'client_platform' => $request->input('client_platform'),
        ];
    }

    /**
     * Log native authentication attempt
     *
     * @param array $credentials
     * @param Request $request
     */
    protected function logNativeAuthAttempt(array $credentials, Request $request): void
    {
        if ($credentials['client_user_agent'] || $credentials['client_platform']) {
            Log::info('Native login client info', [
                'user_agent' => $credentials['client_user_agent'],
                'platform' => $credentials['client_platform'],
                'username' => $credentials['username'],
                'ip' => $request->ip(),
            ]);
        }
    }

    /**
     * Create HTTP client for native authentication
     *
     * @return Client
     */
    protected function createNativeAuthClient(): Client
    {
        return new Client([
            'verify' => false,
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
    }

    /**
     * Prepare authentication headers
     *
     * @param string|null $clientUserAgent
     * @param Request $request
     * @return string
     */
    protected function prepareUserAgent(?string $clientUserAgent, Request $request): string
    {
        return $clientUserAgent ?: $request->header('User-Agent', '');
    }

    /**
     * Initiate native authentication flow
     *
     * @param Client $client
     * @param array $credentials
     * @param string $userAgentHeader
     * @return array
     */
    protected function initiateNativeAuthFlow(Client $client, array $credentials, string $userAgentHeader): array
    {
        $config = config('auth-custom.oauth');
        $state = bin2hex(random_bytes(8));
        $scope = implode(' ', $config['default_scopes'] ?? ['openid', 'profile', 'roles']);

        $formParams = [
            'client_id' => $config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $scope,
            'state' => $state,
            'response_mode' => 'direct',
        ];
        // Add organizationId if provided
        if (func_num_args() > 3 && func_get_arg(3)) {
            $formParams['orgId'] = func_get_arg(3);
        }

        $response = $client->post(rtrim($config['auth_url'], '?'), [
            'form_params' => $formParams,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'User-Agent' => $userAgentHeader,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Find username/password authenticator
     *
     * @param array $nextStep
     * @return array|null
     */
    protected function findBasicAuthenticator(array $nextStep): ?array
    {
        if (empty($nextStep['authenticators'])) {
            return null;
        }

        foreach ($nextStep['authenticators'] as $auth) {
            if (
                (isset($auth['authenticator']) && strtolower($auth['authenticator']) === 'username & password') ||
                (isset($auth['metadata']['i18nKey']) && $auth['metadata']['i18nKey'] === 'authenticator.basic')
            ) {
                return $auth;
            }
        }

        return null;
    }

    /**
     * Get authentication URL from response
     *
     * @param array $initData
     * @return string
     */
    protected function getAuthenticationUrl(array $initData): string
    {
        if (!empty($initData['links'])) {
            foreach ($initData['links'] as $link) {
                if ($link['name'] === 'authentication') {
                    return $link['href'];
                }
            }
        }

        $config = config('auth-custom.oauth');
        return rtrim($config['auth_url'], '/authorize?') . '/authn';
    }

    /**
     * Perform authentication with credentials
     *
     * @param Client $client
     * @param string $authnUrl
     * @param string $flowId
     * @param array $basicAuth
     * @param array $credentials
     * @param string $userAgentHeader
     * @return array
     */
    protected function performAuthentication(
        Client $client,
        string $authnUrl,
        string $flowId,
        array $basicAuth,
        array $credentials,
        string $userAgentHeader
    ): array {
        $response = $client->post($authnUrl, [
            'json' => [
                'flowId' => $flowId,
                'selectedAuthenticator' => [
                    'authenticatorId' => $basicAuth['authenticatorId'],
                    'params' => [
                        'username' => $credentials['username'],
                        'password' => $credentials['password'],
                    ],
                ],
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => $userAgentHeader,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Extract authorization code from authentication response
     *
     * @param array $authnData
     * @return string|null
     */
    protected function extractAuthorizationCode(array $authnData): ?string
    {
        return $authnData['code'] ?? ($authnData['authData']['code'] ?? null);
    }

    /**
     * Exchange authorization code for tokens (native flow)
     *
     * @param Client $client
     * @param string $authorizationCode
     * @param string $userAgentHeader
     * @return array
     */
    protected function exchangeCodeForTokensNative(Client $client, string $authorizationCode, string $userAgentHeader): array
    {
        $config = config('auth-custom.oauth');

        $response = $client->post($config['token_url'], [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'redirect_uri' => $config['redirect_uri'],
                'client_id' => $config['client_id'],
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'User-Agent' => $userAgentHeader,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Process tokens and fix missing fields
     *
     * @param array $tokens
     * @return array
     */
    protected function processNativeTokens(array $tokens): array
    {
        // Handle missing 'expires_at' gracefully, use 'expires_in' if needed
        if (!isset($tokens['expires_at']) && isset($tokens['expires_in'])) {
            $tokens['expires_at'] = now()->addSeconds((int)$tokens['expires_in'])->toDateTimeString();
        }

        // Handle missing 'refresh_token' gracefully
        if (!isset($tokens['refresh_token'])) {
            $tokens['refresh_token'] = null;
        }

        return $tokens;
    }

    /**
     * Build user info from JWT payload for native authentication
     *
     * @param array $jwtPayload
     * @param string $username
     * @return array
     */
    protected function buildNativeUserInfo(array $jwtPayload, string $username): array
    {
        // Fetch user photo from SCIM (reuse SSO logic)
        $photoUrl = null;
        if (method_exists($this, 'fetchUserPhotoFromScim')) {
            $photoUrl = $this->fetchUserPhotoFromScim($username);
        }
        return [
            'username' => $jwtPayload['username'] ?? $username,
            'roles' => $jwtPayload['roles'] ?? [],
            'email' => $jwtPayload['email'] ?? null,
            'Photo' => $photoUrl,
        ];
    }

    /**
     * Handle native authentication request exception
     *
     * @param RequestException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleNativeAuthRequestException(RequestException $e)
    {
        Log::error('WSO2 RequestException', [
            'message' => $e->getMessage(),
            'response' => $e->getResponse() ? (string) $e->getResponse()->getBody() : null,
        ]);

        return response()->json([
            'error' => 'Authentication service unavailable. Please try again later.'
        ], 503);
    }

    /**
     * Handle native authentication general exception
     *
     * @param \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleNativeAuthGeneralException(\Exception $e)
    {
        Log::error('NativeLoginController Exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Unexpected error occurred. Please try again later.'
        ], 500);
    }

    /**
     * Handle carbon.super authentication (direct login to main organization)
     */
    protected function handleCarbonSuperAuth($request, $credentials, $client, $userAgentHeader, $config, $state, $scope)
    {
        try {
            \Log::info('NativeLogin: Initiating carbon.super authentication');
            
            // Step 1: Call carbon.super authorize endpoint (different from sub-org)
            $carbonSuperAuthUrl = str_replace('/oauth2/authorize', '/t/carbon.super/oauth2/authorize', $config['auth_url']);
            
            $response = $client->post($carbonSuperAuthUrl, [
                'form_params' => [
                    'client_id' => $config['client_id'],
                    'response_type' => 'code',
                    'redirect_uri' => $config['redirect_uri'],
                    'scope' => $scope,
                    'state' => $state,
                    'response_mode' => 'direct',
                    // No 'fidp' and 'org' for carbon.super - uses default flow
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => $userAgentHeader,
                ],
            ]);
            
            $initData = json_decode($response->getBody(), true);
            \Log::debug('NativeLogin: Carbon.super init response', ['initData' => $initData]);
            
            // Step 2: Extract flow data
            $flowId = $initData['flowId'] ?? null;
            $authenticators = $initData['nextStep']['authenticators'] ?? [];
            $authnUrl = $initData['links'][0]['href'] ?? null;
            
            if (!$flowId || empty($authenticators) || !$authnUrl) {
                \Log::error('NativeLogin: Carbon.super authentication setup failed', ['initData' => $initData]);
                return response()->json(['error' => 'Carbon.super authentication failed to initialize.'], 422);
            }
            
            // Step 3: Find BasicAuthenticator (QmFzaWNBdXRoZW50aWNhdG9yOkxPQ0FM)
            $basicAuthenticator = null;
            foreach ($authenticators as $auth) {
                if ($auth['authenticator'] === 'Username & Password' && $auth['idp'] === 'LOCAL') {
                    $basicAuthenticator = $auth;
                    break;
                }
            }
            
            if (!$basicAuthenticator) {
                \Log::error('NativeLogin: BasicAuthenticator not found in carbon.super', ['authenticators' => $authenticators]);
                return response()->json(['error' => 'Basic authentication not available in carbon.super.'], 422);
            }
            
            // Step 4: Authenticate with username/password using BasicAuthenticator
            $authResponse = $client->post($authnUrl, [
                'json' => [
                    'flowId' => $flowId,
                    'selectedAuthenticator' => [
                        'authenticatorId' => $basicAuthenticator['authenticatorId'],
                        'params' => [
                            'username' => $credentials['username'],
                            'password' => $credentials['password']
                        ]
                    ]
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => $userAgentHeader,
                ],
            ]);
            
            $authData = json_decode($authResponse->getBody(), true);
            \Log::debug('NativeLogin: Carbon.super auth response', ['authData' => $authData]);
            
            // Step 5: Check for successful authentication
            if (isset($authData['authData']['code']) && $authData['flowStatus'] === 'SUCCESS_COMPLETED') {
                $authCode = $authData['authData']['code'];
                \Log::info('NativeLogin: Carbon.super authentication successful', [
                    'code' => substr($authCode, 0, 10) . '...',
                    'flowStatus' => $authData['flowStatus']
                ]);
                
                // Step 6: Exchange code for tokens using carbon.super specific method
                return $this->exchangeCarbonSuperCodeForTokens($authCode, $config, $state, $request, $client, $userAgentHeader);
            }
            
            \Log::error('NativeLogin: Carbon.super authentication failed', ['authData' => $authData]);
            return response()->json(['error' => 'Invalid carbon.super credentials.'], 401);
            
        } catch (\Exception $e) {
            \Log::error('NativeLogin: Carbon.super authentication exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Carbon.super authentication failed.'], 500);
        }
    }

    /**
     * Exchange carbon.super authorization code for tokens
     */
    protected function exchangeCarbonSuperCodeForTokens($authCode, $config, $state, $request, $client, $userAgentHeader)
    {
        try {
            \Log::info('NativeLogin: Exchanging carbon.super code for tokens');
            
            // Step 3: Exchange authorization code for tokens (matches curl example)
            $tokenResponse = $client->post($config['token_url'], [
                'form_params' => [
                    'code' => $authCode,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $config['redirect_uri'],
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
                    'User-Agent' => $userAgentHeader,
                ],
            ]);
            
            $tokenData = json_decode($tokenResponse->getBody(), true);
            \Log::debug('NativeLogin: Carbon.super token response', ['tokenData' => array_keys($tokenData)]);
            
            if (!isset($tokenData['access_token']) || !isset($tokenData['id_token'])) {
                \Log::error('NativeLogin: Carbon.super token exchange failed', ['tokenData' => $tokenData]);
                return response()->json(['error' => 'Failed to exchange code for tokens.'], 500);
            }
            
            // Process tokens and create session (use existing methods from other traits)
            $tokens = $this->processTokenResponse($tokenData);
            $accessTokenData = $this->extractAccessTokenData($tokens['access_token']);
            $jwtPayload = $this->decodeJwtPayload($tokens['id_token']);
            
            if (!$this->validateJwtStructure($tokens['id_token']) || !$jwtPayload) {
                \Log::error('NativeLogin: Carbon.super JWT validation failed');
                return response()->json(['error' => 'Invalid token structure.'], 500);
            }
            
            // Create user session for carbon.super
            $sessionData = $this->createUserSession($jwtPayload, $accessTokenData, $tokens, $request);
            
            // Force session save
            $request->session()->save();
            
            \Log::info('NativeLogin: Carbon.super login successful', [
                'user' => $jwtPayload['username'] ?? 'unknown',
                'org_name' => $jwtPayload['org_name'] ?? 'carbon.super',
                'roles' => $jwtPayload['roles'] ?? []
            ]);
            
            // Return success response from session data
            return response()->json([
                'success' => $sessionData['success'],
                'message' => 'Carbon.super login successful',
                'redirect' => '/admin/crm/dashboard',
                'user' => $sessionData['user_info']
            ]);
            
        } catch (\Exception $e) {
            \Log::error('NativeLogin: Carbon.super token exchange exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Token exchange failed.'], 500);
        }
    }

    /**
     * Create user session for carbon.super authentication
     *
     * @param array $jwtPayload
     * @param array $accessTokenData
     * @param array $tokens
     * @param Request $request
     * @return array
     */
    protected function createUserSession($jwtPayload, $accessTokenData, $tokens, $request)
    {
        // Extract user information from JWT payload
        $userInfo = [
            'username' => $jwtPayload['username'] ?? $jwtPayload['sub'] ?? null,
            'roles' => $jwtPayload['roles'] ?? [],
            'org_name' => $jwtPayload['org_name'] ?? 'carbon.super',
            'org_id' => $jwtPayload['org_id'] ?? null,
            'email' => $jwtPayload['email'] ?? null,
            'given_name' => $jwtPayload['given_name'] ?? null,
            'family_name' => $jwtPayload['family_name'] ?? null
        ];

        // Set session data similar to regular native login
        session([
            'access_token' => $tokens['access_token'],
            'id_token' => $tokens['id_token'],
            'user_info' => $userInfo,
            'roles' => $userInfo['roles'],
            'organization_name' => $userInfo['org_name'],
            'is_carbon_super' => true, // Flag to identify carbon.super sessions
        ]);

        \Log::debug('NativeLogin: Carbon.super session created', [
            'username' => $userInfo['username'],
            'org_name' => $userInfo['org_name'],
            'roles' => $userInfo['roles'],
        ]);

        return [
            'success' => true,
            'redirect' => '/dashboard',
            'user_info' => $userInfo
        ];
    }
}
