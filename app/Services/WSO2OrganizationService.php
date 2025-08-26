<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WSO2OrganizationService
{
    private $baseUrl;
    private $username;
    private $password;

    public function __construct()
    {
        $this->baseUrl = env('IS_URL');
        $this->username = env('WSO2_ADMIN_USERNAME');
        $this->password = env('WSO2_ADMIN_PASSWORD');
    }

    /**
     * Get all organizations with filtering support (enhanced for API compliance)
     */
    public function getOrganizations($filters = [])
    {
        try {
            $params = [];
            
            // Support direct filter parameter (as per WSO2 API documentation)
            if (!empty($filters['filter'])) {
                $params['filter'] = $filters['filter'];
            } else {
                // Legacy support - build filter from individual parameters
                $filterParts = [];
                
                if (!empty($filters['name'])) {
                    $filterParts[] = 'name co ' . $filters['name'];
                }
                
                if (!empty($filters['status'])) {
                    $filterParts[] = 'status eq ' . $filters['status'];
                }
                
                if (!empty($filterParts)) {
                    $params['filter'] = implode(' and ', $filterParts);
                }
            }
            
            // Other standard parameters
            if (!empty($filters['limit'])) {
                $params['limit'] = $filters['limit'];
            }
            
            if (!empty($filters['offset'])) {
                $params['offset'] = $filters['offset'];
            }
            
            if (!empty($filters['recursive'])) {
                $params['recursive'] = $filters['recursive'];
            }

            if (!empty($filters['after'])) {
                $params['after'] = $filters['after'];
            }

            if (!empty($filters['before'])) {
                $params['before'] = $filters['before'];
            }

            Log::info('WSO2 Organizations API call', [
                'url' => $this->baseUrl . '/api/server/v1/organizations',
                'params' => $params
            ]);

            // Set shorter timeout for dashboard requests (5 seconds instead of default 10)
            $timeout = 5;
            if (request()->is('admin/crm/dashboard*')) {
                $timeout = 3; // Even shorter for dashboard
            }

            $response = Http::timeout($timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withOptions(['verify' => false])
                ->get($this->baseUrl . '/api/server/v1/organizations', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get organizations', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['organizations' => []];

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Get Organizations Error: ' . $e->getMessage());
            return ['organizations' => []];
        }
    }

    /**
     * Get organization by ID with optional permissions
     */
    public function getOrganization($id, $includePermissions = false)
    {
        try {
            $params = [];
            if ($includePermissions) {
                $params['includePermissions'] = 'true';
            }

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withOptions(['verify' => false])
                ->get($this->baseUrl . '/api/server/v1/organizations/' . $id, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get organization', [
                'org_id' => $id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Get Organization Error: ' . $e->getMessage(), [
                'org_id' => $id
            ]);
            return null;
        }
    }

    /**
     * Check if organization name is available
     */
    public function checkOrganizationNameAvailability($name)
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . '/api/server/v1/organizations/check-name', [
                    'name' => $name
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['available'] ?? false;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Check Name Availability Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new organization
     */
    public function createOrganization($data)
    {
        try {
            $organizationData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'attributes' => $data['attributes'] ?? []
            ];

            if (!empty($data['parentId'])) {
                $organizationData['parentId'] = $data['parentId'];
            }

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . '/api/server/v1/organizations', $organizationData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to create organization', [
                'data' => $organizationData,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Create Organization Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update organization using PATCH operations
     */
    public function updateOrganization($id, $operations)
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch($this->baseUrl . '/api/server/v1/organizations/' . $id, $operations);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to update organization', [
                'org_id' => $id,
                'operations' => $operations,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Update Organization Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update organization using PUT
     */
    public function replaceOrganization($id, $data)
    {
        try {
            $organizationData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'status' => $data['status'] ?? 'ACTIVE',
                'attributes' => $data['attributes'] ?? []
            ];

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->put($this->baseUrl . '/api/server/v1/organizations/' . $id, $organizationData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to replace organization', [
                'org_id' => $id,
                'data' => $organizationData,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Replace Organization Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete organization
     */
    public function deleteOrganization($id)
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withOptions(['verify' => false])
                ->delete($this->baseUrl . '/api/server/v1/organizations/' . $id);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Delete Organization Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get organization metadata
     */
    public function getOrganizationMetadata()
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withOptions(['verify' => false])
                ->get($this->baseUrl . '/api/server/v1/organizations/metadata');

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Get Metadata Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get organization users using organization context with organization_switch
     */
    public function getOrganizationUsers($organizationId, $count = 1000)
    {
        try {
            // Step 1: Get initial access token
            $initialToken = $this->getInitialAccessToken();
            if (!$initialToken) {
                Log::error('Failed to get initial access token for organization users');
                return [];
            }

            // Step 2: Switch to organization context
            $orgToken = $this->switchToOrganization($initialToken, $organizationId);
            if (!$orgToken) {
                Log::error('Failed to switch to organization context for users');
                return [];
            }

            // Step 3: Get users with organization-scoped token
            $response = Http::withToken($orgToken)
                ->withOptions(['verify' => false])
                ->get(env('USER_URL'), [
                    'count' => $count
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['Resources'] ?? [];
            }

            // Only log non-403 errors since 403 is expected for organizations user doesn't have access to
            if ($response->status() !== 403) {
                Log::error('Failed to get organization users', [
                    'org_id' => $organizationId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            return [];

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Get Users Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update user in organization context with organization_switch
     */
    public function updateOrganizationUser($organizationId, $userId, $operations)
    {
        try {
            // Step 1: Get initial access token
            $initialToken = $this->getInitialAccessToken();
            if (!$initialToken) {
                Log::error('Failed to get initial access token for user update');
                return false;
            }

            // Step 2: Switch to organization context
            $orgToken = $this->switchToOrganization($initialToken, $organizationId);
            if (!$orgToken) {
                Log::error('Failed to switch to organization context for user update');
                return false;
            }

            // Step 3: Update user with organization-scoped token
            $response = Http::withToken($orgToken)
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('USER_URL') . '/' . $userId, $operations);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('WSO2 Organization Service - Update User Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get initial access token using client credentials (copied from working LeadController implementation)
     */
    public function getInitialAccessToken()
    {
        try {
            $clientId = env('IS_CLIENT_ID');
            $clientSecret = env('IS_CLIENT_SECRET');
            
            $response = Http::timeout(60)
                ->asForm()
                ->withOptions(['verify' => false]) // Ignore SSL verification
                ->post(env('IS_TOKEN_URL'), [
                    'grant_type' => 'client_credentials',
                    'scope' => 'SYSTEM',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                \Log::info('Initial access token obtained successfully');
                return $tokenData['access_token'];
            } else {
                \Log::error('Failed to get initial access token', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            \Log::error('Exception getting initial access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Switch to organization context using organization_switch grant (copied from working LeadController implementation)
     */
    public function switchToOrganization($initialToken, $organizationId)
    {
        try {
            $clientId = env('IS_CLIENT_ID');
            
            $response = Http::timeout(60)
                ->asForm()
                ->withOptions(['verify' => false]) // Ignore SSL verification
                ->post(env('IS_TOKEN_URL'), [
                    'grant_type' => 'organization_switch',
                    'token' => $initialToken,
                    'scope' => 'SYSTEM',
                    'switching_organization' => $organizationId,
                    'client_id' => $clientId
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                \Log::info('Organization switch successful', [
                    'organization_id' => $organizationId,
                    'scope' => $tokenData['scope'] ?? 'unknown'
                ]);
                return $tokenData['access_token'];
            } else {
                \Log::error('Failed to switch organization', [
                    'organization_id' => $organizationId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            \Log::error('Exception switching organization: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create user in WSO2 using organization_switch flow (based on working LeadController implementation)
     */
    public function createUserInOrganization($userData, $organizationId)
    {
        try {
            // Step 1: Get initial access token
            \Log::info('Getting initial access token for user creation...');
            $initialToken = $this->getInitialAccessToken();
            if (!$initialToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get initial access token'
                ];
            }

            // Step 2: Switch to organization context
            \Log::info('Switching to organization context...', ['org_id' => $organizationId]);
            $orgToken = $this->switchToOrganization($initialToken, $organizationId);
            if (!$orgToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to switch to organization context'
                ];
            }

            // Step 3: Create user with organization-scoped token
            \Log::info('Sending user creation request to WSO2...');
            
            $response = Http::timeout(60)
                ->withToken($orgToken) // Use organization-scoped token
                ->acceptJson()
                ->withOptions(['verify' => false])
                ->post(env('USER_URL'), $userData);

            \Log::info('User creation request completed');
            
            \Log::info('WSO2 User Creation Response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'user_id' => $responseData['id'] ?? null,
                    'data' => $responseData
                ];
            } else {
                \Log::error('WSO2 User creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to create user in WSO2'
                ];
            }

        } catch (\Exception $e) {
            \Log::error('User creation exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
