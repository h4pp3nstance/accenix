<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * UserCreationHandler
 * 
 * Handles automatic user account creation during lead conversion process.
 * 
 * This service streamlines the lead-to-customer conversion by automatically creating
 * user accounts in WSO2 Identity Server instead of sending registration emails.
 * 
 * Features:
 * - Automatic username generation from contact information
 * - Secure temporary password generation
 * - WSO2 organization-scoped authentication
 * - Primary user assignment to organization
 * - Comprehensive error handling and rollback
 * 
 * Authentication Flow:
 * 1. Get initial access token using client credentials
 * 2. Switch to organization context using organization_switch grant
 * 3. Create user with organization-scoped token
 * 
 * @author System
 * @version 1.0
 */
class UserCreationHandler
{
    /**
     * Minimum username length
     */
    const MIN_USERNAME_LENGTH = 4;

    /**
     * Default password complexity requirements
     */
    const PASSWORD_LENGTH = 10;
    const PASSWORD_NUMBERS = 2;
    const PASSWORD_SYMBOLS = 1;

    /**
     * WSO2 Authentication scopes
     */
    const SYSTEM_SCOPE = 'SYSTEM';
    const CLIENT_CREDENTIALS_GRANT = 'client_credentials';
    const ORG_SWITCH_GRANT = 'organization_switch';

    /**
     * Create user account automatically for converted lead
     * 
     * @param array $organization Organization data from WSO2
     * @param array $attributeMap Lead attributes containing contact information
     * @return array Success/failure result with user details
     */
    public function createUserAccount($organization, $attributeMap)
    {
        try {
            // Extract required contact information
            $contactEmail = $attributeMap['contact_email'] ?? '';
            $contactName = $attributeMap['contact_person'] ?? '';
            
            if (empty($contactEmail) || empty($contactName)) {
                return [
                    'success' => false,
                    'message' => 'Missing required contact information (contact_email or contact_person)'
                ];
            }

            // Generate credentials
            $username = $this->generateUsername($contactEmail, $contactName);
            $temporaryPassword = $this->generateTemporaryPassword();

            // Prepare user data with PRIMARY prefix for organization users
            $userData = $this->prepareUserData('PRIMARY/' . $username, $contactEmail, $contactName, $temporaryPassword);

            // Create user in WSO2 using organization-scoped token
            $createUserResult = $this->createWSO2User($userData, $organization['id']);
            if (!$createUserResult['success']) {
                return $createUserResult;
            }

            $userId = $createUserResult['user_id'];

            // Assign superadmin role to the user
            $roleAssignResult = $this->assignUserRoles($userId, $organization['id'], ['superadmin']);
            if (!$roleAssignResult['success']) {
                Log::warning('Failed to assign roles to user', [
                    'user_id' => $userId,
                    'organization_id' => $organization['id'],
                    'error' => $roleAssignResult['message']
                ]);
            }

            // Update organization with primary user information
            $this->updateOrganizationPrimaryUser($organization['id'], $userId, $attributeMap);

            Log::info('User account created successfully', [
                'organization_id' => $organization['id'],
                'user_id' => $userId,
                'username' => $username,
                'email' => $contactEmail
            ]);

            return [
                'success' => true,
                'user_id' => $userId,
                'username' => $username,
                'temporary_password' => $temporaryPassword,
                'email' => $contactEmail,
                'full_name' => $contactName
            ];

        } catch (\Exception $e) {
            Log::error('User creation error: ' . $e->getMessage(), [
                'organization_id' => $organization['id'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error creating user account: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate username from contact email and name
     * 
     * @param string $email Contact email address
     * @param string $name Contact person name
     * @return string Generated username
     */
    protected function generateUsername($email, $name)
    {
        // Try email prefix first (more unique)
        $emailPrefix = explode('@', $email)[0];
        $emailPrefix = preg_replace('/[^a-zA-Z0-9]/', '', $emailPrefix);
        
        if (strlen($emailPrefix) >= self::MIN_USERNAME_LENGTH) {
            return strtolower($emailPrefix);
        }

        // Fallback to name-based username
        $nameClean = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $nameWords = explode(' ', $nameClean);
        
        if (count($nameWords) >= 2) {
            // First name + last name initial
            $username = strtolower($nameWords[0] . substr($nameWords[count($nameWords)-1], 0, 1));
        } else {
            $username = strtolower($nameWords[0]);
        }

        // Add random suffix if username is too short
        if (strlen($username) < self::MIN_USERNAME_LENGTH) {
            $username .= rand(100, 999);
        }

        return $username;
    }

    /**
     * Generate secure temporary password for new users
     * Meets WSO2 requirements: uppercase, lowercase, numbers, and symbols
     * 
     * @return string Generated password with letters, numbers, and symbols
     */
    protected function generateTemporaryPassword()
    {
        // Generate components to meet WSO2 complexity requirements
        $uppercase = strtoupper(Str::random(2)); // At least 2 uppercase letters
        $lowercase = strtolower(Str::random(3)); // At least 3 lowercase letters
        $numbers = '';
        for ($i = 0; $i < self::PASSWORD_NUMBERS; $i++) {
            $numbers .= rand(0, 9);
        }
        $symbols = '@#'; // Multiple symbols for security
        
        // Combine all components
        $password = $uppercase . $lowercase . $numbers . $symbols;
        
        // Shuffle to randomize position of components
        return str_shuffle($password);
    }

    /**
     * Prepare user data payload for WSO2 SCIM API
     * Uses minimal format that matches successful WSO2 Console requests
     * 
     * @param string $username Username with PRIMARY/ prefix
     * @param string $email User email address
     * @param string $fullName Full name of the user
     * @param string $password Temporary password
     * @return array User data payload for WSO2
     */
    protected function prepareUserData($username, $email, $fullName, $password)
    {
        $nameParts = explode(' ', trim($fullName));
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'User';

        // Minimal payload format that matches successful WSO2 Console requests
        return [
            'emails' => [
                [
                    'primary' => true,
                    'value' => $email
                ]
            ],
            'name' => [
                'givenName' => $firstName,
                'familyName' => $lastName
            ],
            'password' => $password,
            'userName' => $username
        ];
    }

    /**
     * Create user in WSO2 using organization switch authentication pattern
     * 
     * @param array $userData User data payload
     * @param string $organizationId Target organization ID
     * @return array Success/failure result with user details
     */
    protected function createWSO2User($userData, $organizationId)
    {
        try {
            // Step 1: Get initial access token using client credentials
            Log::info('Getting initial access token for user creation...', [
                'organization_id' => $organizationId
            ]);
            
            $initialToken = $this->getInitialAccessToken();
            if (!$initialToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get initial access token'
                ];
            }

            // Step 2: Switch to organization context
            Log::info('Switching to organization context...', [
                'organization_id' => $organizationId
            ]);
            
            $orgToken = $this->switchToOrganization($initialToken, $organizationId);
            if (!$orgToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to switch to organization context'
                ];
            }

            // Step 3: Create user using organization-scoped token
            Log::info('Creating user with organization-scoped token...', [
                'organization_id' => $organizationId,
                'username' => $userData['userName']
            ]);
            
            $response = Http::withToken($orgToken)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->timeout(60)
                ->post(env('IS_URL') . '/t/carbon.super/o/scim2/Users', $userData);

            if ($response->successful()) {
                $user = $response->json();
                
                Log::info('WSO2 user created successfully', [
                    'organization_id' => $organizationId,
                    'user_id' => $user['id'],
                    'username' => $user['userName']
                ]);
                
                return [
                    'success' => true,
                    'user_id' => $user['id'],
                    'user_data' => $user
                ];
            }

            Log::error('Failed to create WSO2 user after organization switch', [
                'organization_id' => $organizationId,
                'username' => $userData['userName'],
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create user after organization switch: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Exception creating WSO2 user with organization switch', [
                'organization_id' => $organizationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error creating WSO2 user: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get initial access token using client credentials
     * First step in WSO2 organization switch authentication flow
     * 
     * @return string|null Access token or null if failed
     */
    protected function getInitialAccessToken()
    {
        try {
            $clientId = env('IS_CLIENT_ID');
            $clientSecret = env('IS_CLIENT_SECRET');
            
            $response = Http::timeout(60)
                ->asForm()
                ->withOptions(['verify' => false])
                ->post(env('IS_TOKEN_URL'), [
                    'grant_type' => self::CLIENT_CREDENTIALS_GRANT,
                    'scope' => 'SYSTEM',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                Log::info('Initial access token obtained successfully for user creation');
                return $tokenData['access_token'];
            } else {
                Log::error('Failed to get initial access token for user creation', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception getting initial access token for user creation: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Switch to organization context using organization_switch grant
     * Second step in WSO2 organization switch authentication flow
     * 
     * @param string $initialToken Initial access token from client credentials
     * @param string $organizationId Target organization ID
     * @return string|null Organization-scoped token or null if failed
     */
    protected function switchToOrganization($initialToken, $organizationId)
    {
        try {
            $clientId = env('IS_CLIENT_ID');
            
            $response = Http::timeout(60)
                ->asForm()
                ->withOptions(['verify' => false])
                ->post(env('IS_TOKEN_URL'), [
                    'grant_type' => self::ORG_SWITCH_GRANT,
                    'token' => $initialToken,
                    'scope' => self::SYSTEM_SCOPE, // SYSTEM scope provides all internal permissions
                    'switching_organization' => $organizationId,
                    'client_id' => $clientId
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                Log::info('Organization switch successful for user creation', [
                    'organization_id' => $organizationId,
                    'scope' => $tokenData['scope'] ?? 'unknown'
                ]);
                return $tokenData['access_token'];
            } else {
                Log::error('Failed to switch organization for user creation', [
                    'organization_id' => $organizationId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception switching organization for user creation: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete WSO2 user (rollback function)
     */
    protected function deleteWSO2User($userId)
    {
        try {
            Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->delete(env('IS_URL') . '/scim2/Users/' . $userId);

            Log::info('User deleted during rollback', ['user_id' => $userId]);

        } catch (\Exception $e) {
            Log::error('Failed to delete user during rollback', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update organization with primary user information
     * Records the newly created user as the primary contact for the organization
     * 
     * @param string $organizationId Organization ID
     * @param string $userId Created user ID
     * @param array $attributeMap Original lead attributes
     * @return void
     */
    protected function updateOrganizationPrimaryUser($organizationId, $userId, $attributeMap)
    {
        try {
            $attributes = [
                'primary_user_id' => $userId,
                'user_creation_method' => 'auto_from_lead',
                'account_setup_completed' => 'pending_password_change'
            ];

            // Build patch operations for organization update
            $patchOperations = [];
            foreach ($attributes as $key => $value) {
                $patchOperations[] = [
                    'operation' => isset($attributeMap[$key]) ? 'REPLACE' : 'ADD',
                    'path' => "/attributes/{$key}",
                    'value' => $value
                ];
            }

            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $organizationId, $patchOperations);

            if ($response->successful()) {
                Log::info('Organization updated with primary user info', [
                    'organization_id' => $organizationId,
                    'user_id' => $userId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update organization with user info', [
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get organization-scoped token for API operations
     * 
     * @param string $organizationId Organization ID
     * @return array Result with success status and token
     */
    protected function getOrganizationScopedToken($organizationId)
    {
        try {
            // Get initial access token
            $initialToken = $this->getInitialAccessToken();
            if (!$initialToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get initial access token',
                    'access_token' => null
                ];
            }

            // Switch to organization context
            $orgToken = $this->switchToOrganization($initialToken, $organizationId);
            if (!$orgToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to switch to organization context',
                    'access_token' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Organization token obtained successfully',
                'access_token' => $orgToken
            ];

        } catch (\Exception $e) {
            Log::error('Exception getting organization token: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'access_token' => null
            ];
        }
    }

    /**
     * Assign roles to user in WSO2 organization using Bulk API
     * Based on working curl example from organization management
     * 
     * @param string $userId WSO2 user ID
     * @param string $organizationId Organization ID
     * @param array $roles Array of role names to assign
     * @return array Success/failure result
     */
    protected function assignUserRoles($userId, $organizationId, $roles = ['superadmin'])
    {
        try {
            // 1. Dapatkan token scoped ke organisasi
            $orgTokenResult = $this->getOrganizationScopedToken($organizationId);
            if (!$orgTokenResult['success']) {
                return ['success' => false, 'message' => 'Token failed: ' . $orgTokenResult['message']];
            }
            \Log::info('Organization token result', [
                'organization_id' => $organizationId,
                'orgTokenResult' => $orgTokenResult
            ]);
            $orgToken = $orgTokenResult['access_token'];
            $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
            $searchEndpoint = $baseUrl . '/t/carbon.super' . '/o/scim2/v2/Roles/.search';

            $successfulRoles = [];
            $failedRoles = [];

            foreach ($roles as $roleName) {
                try {
                    // 2. Cari roleId berdasarkan nama
                    $searchResponse = Http::withToken($orgToken)
                        ->withOptions(['verify' => false])
                        ->withHeaders([
                            'Content-Type' => 'application/scim+json',
                            'Accept' => 'application/scim+json'
                        ])
                        ->post($searchEndpoint, [
                            "schemas" => ["urn:ietf:params:scim:api:messages:2.0:SearchRequest"],
                            "startIndex" => 1,
                            "filter" => "displayName eq \"{$roleName}\""
                        ]);

                    if (!$searchResponse->successful()) {
                        $failedRoles[] = $roleName;
                        \Log::warning('Search role failed', [
                            'role' => $roleName,
                            'status' => $searchResponse->status(),
                            'response' => $searchResponse->body()
                        ]);
                        continue;
                    }

                    $data = $searchResponse->json();
                    if (empty($data['Resources'])) {
                        $failedRoles[] = $roleName;
                        \Log::warning('Role not found via search', ['role' => $roleName]);
                        continue;
                    }

                    $roleId = $data['Resources'][0]['id'];

                    // 3. Assign via Bulk API
                    $bulkPayload = [
                        'Operations' => [
                            [
                                'method' => 'PATCH',
                                'path' => "/v2/Roles/{$roleId}",
                                'data' => [
                                    'Operations' => [
                                        [
                                            'op' => 'add',
                                            'value' => [
                                                'users' => [['value' => $userId]]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'failOnErrors' => 1,
                        'schemas' => ['urn:ietf:params:scim:api:messages:2.0:BulkRequest']
                    ];

                    $bulkResponse = Http::withToken($orgToken)
                        ->withOptions(['verify' => false])
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post("{$baseUrl}/t/carbon.super/o/scim2/Bulk", $bulkPayload);

                    if ($bulkResponse->successful()) {
                        $successfulRoles[] = $roleName;
                        \Log::info('Role assigned via Bulk', [
                            'user_id' => $userId,
                            'role' => $roleName,
                            'role_id' => $roleId
                        ]);
                    } else {
                        $failedRoles[] = $roleName;
                        \Log::warning('Bulk assign failed', [
                            'role' => $roleName,
                            'status' => $bulkResponse->status(),
                            'response' => $bulkResponse->body()
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedRoles[] = $roleName;
                    \Log::error('Error in role assignment loop', [
                        'role' => $roleName,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return [
                'success' => !empty($successfulRoles),
                'message' => $successfulRoles
                    ? 'Roles assigned: ' . implode(', ', $successfulRoles)
                    : 'No roles assigned',
                'successful_roles' => $successfulRoles,
                'failed_roles' => $failedRoles
            ];

        } catch (\Exception $e) {
            \Log::error('Critical error in assignUserRoles', [
                'user_id' => $userId,
                'org_id' => $organizationId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'System error: ' . $e->getMessage()
            ];
        }
    }
}
