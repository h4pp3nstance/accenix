<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

/**
 * NativeLoginControllerHandler - Manages native login controller operations
 * 
 * This trait handles:
 * - Complete native login flow
 * - Integration with session and token management
 * - Error handling and response formatting
 */
use App\Traits\ProfilePageHandler;

trait NativeLoginControllerHandler
{
    /**
     * Handle native login request (renamed to avoid trait collision)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleNativeLoginController(Request $request)
    {
        try {
            // 1. Validate request
            $credentials = $this->validateNativeLoginRequest($request);
            
            // 2. Log authentication attempt
            $this->logNativeAuthAttempt($credentials, $request);
            
            // 3. Prepare authentication
            $client = $this->createNativeAuthClient();
            $userAgentHeader = $this->prepareUserAgent($credentials['client_user_agent'], $request);
            
            // 4. Initiate authentication flow
            $initData = $this->initiateNativeAuthFlow($client, $credentials, $userAgentHeader);
            
            // 5. Validate flow initialization
            $flowId = $initData['flowId'] ?? null;
            $nextStep = $initData['nextStep'] ?? null;
            
            if (!$flowId || !$nextStep || empty($nextStep['authenticators'])) {
                return response()->json(['error' => 'Invalid authentication session.'], 500);
            }
            
            // 6. Find basic authenticator
            $basicAuth = $this->findBasicAuthenticator($nextStep);
            if (!$basicAuth) {
                return response()->json(['error' => 'No username/password authenticator found.'], 500);
            }
            
            // 7. Get authentication URL
            $authnUrl = $this->getAuthenticationUrl($initData);
            
            // 8. Perform authentication
            $authnData = $this->performAuthentication(
                $client,
                $authnUrl,
                $flowId,
                $basicAuth,
                $credentials,
                $userAgentHeader
            );
            
            // 9. Extract authorization code
            $authorizationCode = $this->extractAuthorizationCode($authnData);
            if (!$authorizationCode) {
                return response()->json([
                    'error' => 'Authentication failed. Please check your credentials or contact support.'
                ], 401);
            }
            
            // 10. Exchange code for tokens
            $tokens = $this->exchangeCodeForTokensNative($client, $authorizationCode, $userAgentHeader);
            
            if (empty($tokens['id_token']) || empty($tokens['access_token'])) {
                return response()->json(['error' => 'Failed to obtain tokens.'], 401);
            }
            
            // 11. Process tokens and session
            $this->processNativeAuthenticationSuccess($request, $tokens, $credentials['username']);
            
            return response()->json(['success' => true, 'redirect' => '/dashboard']);
            
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (RequestException $e) {
            return $this->handleNativeAuthRequestException($e);
        } catch (\Exception $e) {
            return $this->handleNativeAuthGeneralException($e);
        }
    }

    /**
     * Process successful native authentication
     *
     * @param Request $request
     * @param array $tokens
     * @param string $username
     */
    protected function processNativeAuthenticationSuccess(Request $request, array $tokens, string $username): void
    {
        // Process tokens
        $processedTokens = $this->processNativeTokens($tokens);

        // Decode tokens and extract data
        $accessTokenData = $this->extractAccessTokenData($processedTokens['access_token']);
        $jwtPayload = $this->decodeJwtPayload($processedTokens['id_token']);
        $userInfo = $this->buildNativeUserInfo($jwtPayload, $username);

        // Store session data
        $this->storeTokensInSession($processedTokens, $accessTokenData);
        $this->storeUserDataInSession($request, $userInfo, $jwtPayload, $processedTokens['scope'] ?? '');

        // Fetch and store user detail in session for navbar/company id
        $userDetail = $this->fetchUserDetailFromScim($username);
        Session::put('userDetail', $userDetail);

        Log::info('Native authentication successful', [
            'username' => $userInfo['username'],
            'roles' => $userInfo['roles'] ?? []
        ]);
    }

    /**
     * Validate native authentication flow data
     *
     * @param array $initData
     * @return bool
     */
    protected function validateNativeAuthFlow(array $initData): bool
    {
        return isset($initData['flowId']) && 
               isset($initData['nextStep']) && 
               !empty($initData['nextStep']['authenticators']);
    }

    /**
     * Log native authentication flow step
     *
     * @param string $step
     * @param array $context
     */
    protected function logNativeAuthStep(string $step, array $context = []): void
    {
        Log::debug('Native auth step: ' . $step, $context);
    }
}
