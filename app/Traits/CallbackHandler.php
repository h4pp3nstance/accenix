<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

/**
 * CallbackHandler - Manages OAuth callback processing
 * 
 * This trait handles:
 * - OAuth callback validation
 * - Token exchange and processing
 * - User authentication flow
 * - Error handling for callback operations
 */
trait CallbackHandler
{
    /**
     * Handle OAuth callback from Identity Server
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    protected function handleOAuthCallback(Request $request)
    {
        $authorizationCode = $request->query('code');
        $routesConfig = $this->getRoutesConfig();

        try {
            if (!$authorizationCode) {
                \App\Helpers\AuthLogger::logLogin('unknown', false, 'No authorization code received');
                return $this->redirectWithError($routesConfig['login'], 'No authorization code received');
            }
            
            // 1. Exchange authorization code for tokens
            $tokenData = $this->exchangeCodeForTokens($authorizationCode);
            $tokens = $this->processTokenResponse($tokenData);
            
            if (!$tokens['id_token']) {
                Log::error('OAuth callback failed: No ID token received');
                return $this->errorResponse('Authentication failed: No ID token received', 400);
            }
            
            // 2. Extract and validate token data
            $accessTokenData = $this->extractAccessTokenData($tokens['access_token']);
            $jwtPayload = $this->decodeJwtPayload($tokens['id_token']);
            
            if (!$this->validateJwtStructure($tokens['id_token']) || !$jwtPayload) {
                Log::error('OAuth callback failed: Invalid ID token format');
                return $this->errorResponse('Authentication failed: Invalid token format', 400);
            }
            //dd($jwtPayload);
            $username = $jwtPayload['username'] ?? '';
            
            // 3. Log successful login
            \App\Helpers\AuthLogger::logLogin($username, true, "User logged in successfully", $request);
            

            // 4. Fetch user photo and build user info
            $photoUrl = $this->fetchUserPhotoFromScim($username);
            $userInfo = $this->buildUserInfo($jwtPayload, $photoUrl);

            // 5. Store session data
            $this->storeTokensInSession($tokens, $accessTokenData);
            $this->storeUserDataInSession($request, $userInfo, $jwtPayload, $tokens['scope']);

            // 6. Load user permissions
            $this->loadUserPermissions($request, $userInfo['roles'], $username);

            // 7. Fetch and store user detail in session for navbar/company id
            $userDetail = $this->fetchUserDetailFromScim($username);

            Log::info('User successfully authenticated', [
                'username' => $username,
                'roles' => $userInfo['roles'] ?? []
            ]);

            return redirect()->route($routesConfig['dashboard']);
            
        } catch (RequestException $e) {
            return $this->handleCallbackRequestException($e);
        } catch (\Exception $e) {
            return $this->handleCallbackGeneralException($e, $routesConfig);
        }
    }

    /**
     * Validate authorization code from callback
     *
     * @param string|null $authorizationCode
     * @return bool
     */
    protected function validateAuthorizationCode(?string $authorizationCode): bool
    {
        return !empty($authorizationCode);
    }

    /**
     * Process authentication tokens and user data
     *
     * @param array $tokens
     * @param array $jwtPayload
     * @param Request $request
     * @return string Username
     */
    protected function processAuthenticationData(array $tokens, array $jwtPayload, Request $request): string
    {
        $username = $jwtPayload['username'] ?? '';
        
        // Extract access token data
        $accessTokenData = $this->extractAccessTokenData($tokens['access_token']);
        
        // Fetch user photo and build user info
        $photoUrl = $this->fetchUserPhotoFromScim($username);
        $userInfo = $this->buildUserInfo($jwtPayload, $photoUrl);
        
        // Store session data
        $this->storeTokensInSession($tokens, $accessTokenData);
        $this->storeUserDataInSession($request, $userInfo, $jwtPayload, $tokens['scope']);
        
        // Load user permissions
        $this->loadUserPermissions($request, $userInfo['roles'], $username);
        
        return $username;
    }

    /**
     * Handle RequestException during callback processing
     *
     * @param RequestException $e
     * @return \Illuminate\Http\Response
     */
    protected function handleCallbackRequestException(RequestException $e)
    {
        $responseBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'No response';
        Log::error('OAuth callback RequestException', [
            'error' => $e->getMessage(),
            'response' => $responseBody
        ]);
        
        return $this->errorResponse(
            'Authentication failed: ' . $e->getMessage() . '<br>Response: ' . $responseBody, 
            500
        );
    }

    /**
     * Handle general Exception during callback processing
     *
     * @param \Exception $e
     * @param array $routesConfig
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleCallbackGeneralException(\Exception $e, array $routesConfig)
    {
        Log::error('OAuth callback unexpected error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return $this->redirectWithError($routesConfig['login'], 'Authentication failed. Please try again.');
    }
}
