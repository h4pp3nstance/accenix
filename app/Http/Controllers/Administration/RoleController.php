<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\RolePermissionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ScimHelper;
use Exception;

class RoleController extends Controller
{
    protected $rolePermissionService;
    protected $scimHelper;

    public function __construct(RolePermissionService $rolePermissionService, ScimHelper $scimHelper)
    {
        $this->rolePermissionService = $rolePermissionService;
        $this->scimHelper = $scimHelper;
    }

    public function ajax(Request $request)
    {
        try {
            // Get all role-permission mappings from the service
            $rolePermissions = $this->rolePermissionService->getRolePermissions();
            
            $roles = [];
            foreach ($rolePermissions as $roleName => $roleData) {
                $permissions = $roleData['permissions'] ?? [];
                $userCount = $roleData['user_count'] ?? 0;
                
                $roles[] = [
                    'id' => $roleName,
                    'name' => $roleName,
                    'user_count' => $userCount,
                    'permissions_count' => count($permissions),
                    'permissions' => $permissions
                ];
            }

            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => count($roles),
                'recordsFiltered' => count($roles),
                'data' => $roles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function index(Request $request)
    {
        return view('administration.role.index');
    }

    public function create()
    {
        // Get all available permissions for selection
        $allPermissions = $this->getAllPermissions();
        return view('administration.role.create', compact('allPermissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'displayName' => 'required|string|max:255',
            'audienceValue' => 'required|string',
            'audienceType' => 'required|string|in:application,organization',
            'users' => 'array',
            'users.*' => 'string',
            'permissions' => 'array',
            'permissions.*' => 'string'
        ]);

        try {
            $accessToken = $this->scimHelper->getTokenISApi();
            $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
            $tenantDomain = 'carbon.super';
            $endpoint = $baseUrl . '/t/' . $tenantDomain . '/scim2/v2/Roles';

            // Prepare role data
            $roleData = [
                'schemas' => ['urn:ietf:params:scim:schemas:extension:2.0:Role'],
                'displayName' => $request->displayName,
                'audience' => [
                    'value' => $request->audienceValue,
                    'type' => $request->audienceType
                ]
            ];

            // Add users if provided
            if ($request->users && count($request->users) > 0) {
                $roleData['users'] = array_map(function($userId) {
                    return ['value' => $userId];
                }, $request->users);
            }

            // Add permissions if provided
            if ($request->permissions && count($request->permissions) > 0) {
                $roleData['permissions'] = array_map(function($permission) {
                    return [
                        'value' => $permission,
                        'display' => $permission
                    ];
                }, $request->permissions);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/scim+json',
                'Accept' => 'application/scim+json'
            ])
            ->withOptions([
                'verify' => false,
                'timeout' => 30
            ])
            ->post($endpoint, $roleData);

            if ($response->successful()) {
                // Refresh cache after creating role
                $this->rolePermissionService->refreshCache();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully',
                    'data' => $response->json()
                ]);
            } else {
                Log::error('Failed to create role in WSO2', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create role: ' . $response->body()
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error creating role in WSO2', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($roleName)
    {
        $rolePermissions = $this->rolePermissionService->getRolePermissions();
        $roleData = $rolePermissions[$roleName] ?? [];
        $permissions = $roleData['permissions'] ?? [];
        $users = $roleData['users'] ?? [];
        $userCount = $roleData['user_count'] ?? 0;
        
        // Convert permissions from objects to strings for backward compatibility with the view
        $permissionStrings = [];
        foreach ($permissions as $permission) {
            if (is_array($permission)) {
                $permissionStrings[] = $permission['id'] ?? $permission['name'] ?? '';
            } else {
                $permissionStrings[] = $permission;
            }
        }
        
        // Ensure users array has proper structure
        $formattedUsers = [];
        foreach ($users as $user) {
            if (is_string($user)) {
                $formattedUsers[] = [
                    'id' => $user,
                    'name' => $user
                ];
            } elseif (is_array($user)) {
                $formattedUsers[] = [
                    'id' => $user['id'] ?? $user['value'] ?? $user['name'] ?? 'unknown',
                    'name' => $user['name'] ?? $user['display'] ?? $user['id'] ?? 'Unknown User'
                ];
            }
        }
        
        $role = [
            'name' => $roleName,
            'users' => $formattedUsers,
            'user_count' => $userCount,
            'permissions' => $permissionStrings,
            'is_protected' => $this->isProtectedRole($roleName)
        ];
        
        $allPermissions = $this->getAllPermissions();
        return view('administration.role.edit', compact('role', 'allPermissions'));
    }

    public function update(Request $request, $roleName)
    {
        $originalName = $request->input('original_name', $roleName);
        $newName = $request->input('name');
        $request->validate([
            'name' => 'required|string|max:255',
            'users' => 'array',
            'users.*' => 'string',
            'permissions' => 'array',
            'permissions.*' => 'string'
        ]);

        try {
            // First, get the role ID by fetching role details using the original name
            $roleId = $this->getRoleIdByName($originalName);
            if (!$roleId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $accessToken = $this->scimHelper->getTokenISApi();
            $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
            $tenantDomain = 'carbon.super';
            $endpoint = $baseUrl . '/t/' . $tenantDomain . '/scim2/v2/Roles/' . $roleId;

            // Prepare role data for update
            $roleData = [
                'displayName' => $newName
            ];

            // Add users if provided
            if ($request->users && count($request->users) > 0) {
                $roleData['users'] = array_map(function($userId) {
                    return ['value' => $userId];
                }, $request->users);
            }

            // Add permissions if provided
            if ($request->permissions && count($request->permissions) > 0) {
                $roleData['permissions'] = array_map(function($permission) {
                    return [
                        'value' => $permission,
                        'display' => $permission
                    ];
                }, $request->permissions);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/scim+json',
                'Accept' => 'application/scim+json'
            ])
            ->withOptions([
                'verify' => false,
                'timeout' => 30
            ])
            ->put($endpoint, $roleData);

            if ($response->successful()) {
                // Refresh cache after updating role
                $this->rolePermissionService->refreshCache();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully',
                    'data' => $response->json()
                ]);
            } else {
                Log::error('Failed to update role in WSO2', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update role: ' . $response->body()
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error updating role in WSO2', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($roleName)
    {
        $rolePermissions = $this->rolePermissionService->getRolePermissions();
        $roleData = $rolePermissions[$roleName] ?? [];
        $permissions = $roleData['permissions'] ?? [];
        $userCount = $roleData['user_count'] ?? 0;
        $users = $roleData['users'] ?? [];
        // Convert permissions from objects to strings for backward compatibility with the view
        $permissionStrings = [];
        foreach ($permissions as $permission) {
            if (is_array($permission)) {
                $permissionStrings[] = $permission['id'] ?? $permission['name'] ?? '';
            } else {
                $permissionStrings[] = $permission;
            }
        }
        // Ensure users array has proper structure (like edit page)
        $formattedUsers = [];
        foreach ($users as $user) {
            if (is_string($user)) {
                $formattedUsers[] = [
                    'id' => $user,
                    'name' => $user
                ];
            } elseif (is_array($user)) {
                $formattedUsers[] = [
                    'id' => $user['id'] ?? $user['value'] ?? $user['name'] ?? 'unknown',
                    'name' => $user['name'] ?? $user['display'] ?? $user['id'] ?? 'Unknown User'
                ];
            }
        }
        $role = [
            'name' => $roleName,
            'user_count' => $userCount,
            'users' => $formattedUsers,
            'permissions' => $permissionStrings,
            'permissions_count' => count($permissionStrings)
        ];
        return view('administration.role.show', compact('role'));
    }

    public function destroy($roleName)
    {
        try {
            // Check if the role is protected
            if ($this->isProtectedRole($roleName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete protected system role: ' . $roleName
                ], 403);
            }

            // First, get the role ID by fetching role details
            $roleId = $this->getRoleIdByName($roleName);
            if (!$roleId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $accessToken = $this->scimHelper->getTokenISApi();
            $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
            $tenantDomain = 'carbon.super';
            $endpoint = $baseUrl . '/t/' . $tenantDomain . '/scim2/v2/Roles/' . $roleId;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/scim+json'
            ])
            ->withOptions([
                'verify' => false,
                'timeout' => 30
            ])
            ->delete($endpoint);

            if ($response->successful()) {
                // Refresh cache after deleting role
                $this->rolePermissionService->refreshCache();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully'
                ]);
            } else {
                Log::error('Failed to delete role in WSO2', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete role: ' . $response->body()
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting role in WSO2', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function refreshPermissions()
    {
        try {
            // Clear API resources cache
            Cache::forget('wso2_api_resources_scopes');
            
            // Run the artisan command to refresh permissions
            Artisan::call('wso2:refresh-permissions');
            
            return response()->json([
                'success' => true,
                'message' => 'Permissions and API resources refreshed successfully from WSO2'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getAllPermissions()
    {
        // Get all unique permissions from all roles
        $rolePermissions = $this->rolePermissionService->getRolePermissions();
        $allPermissions = [];
        
        foreach ($rolePermissions as $roleData) {
            $permissions = $roleData['permissions'] ?? [];
            foreach ($permissions as $permission) {
                // Handle both string and object permission formats
                $permissionId = is_array($permission) ? ($permission['id'] ?? $permission['name'] ?? '') : $permission;
                if ($permissionId && !in_array($permissionId, $allPermissions)) {
                    $allPermissions[] = $permissionId;
                }
            }
        }
        return $allPermissions;
    }

    /**
     * Get role ID by role name from WSO2
     */
    private function getRoleIdByName($roleName)
    {
        try {
            $accessToken = $this->scimHelper->getTokenISApi();
            $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
            $tenantDomain = 'carbon.super';
            $endpoint = $baseUrl . '/t/' . $tenantDomain . '/scim2/v2/Roles';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json'
            ])
            ->withOptions([
                'verify' => false,
                'timeout' => 30
            ])
            ->get($endpoint, [
                'count' => 1000 // fetch all roles (adjust if needed)
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['Resources']) && count($data['Resources']) > 0) {
                    foreach ($data['Resources'] as $role) {
                        if (strtolower($role['displayName']) === strtolower($roleName)) {
                            return $role['id'];
                        }
                    }
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Error getting role ID from WSO2', [
                'role_name' => $roleName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get available users from WSO2 for role assignment
     */
    public function getAvailableUsers()
    {
        try {
            $accessToken = $this->scimHelper->getTokenISApi();
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get access token'
                ], 401);
            }

            $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
            $tenantDomain = 'carbon.super';
            $endpoint = $baseUrl . '/t/' . $tenantDomain . '/scim2/Users';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json'
            ])
            ->withOptions([
                'verify' => false,
                'timeout' => 30
            ])
            ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                $users = [];
                
                if (isset($data['Resources'])) {
                    foreach ($data['Resources'] as $user) {
                        $users[] = [
                            'id' => $user['id'],
                            'userName' => $user['userName'] ?? 'Unknown',
                            'name' => $user['displayName'] ?? $user['userName'] ?? 'Unknown',
                            'displayName' => $user['displayName'] ?? $user['userName'] ?? 'Unknown'
                        ];
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $users
                ]);
            }
            
            Log::error('Failed to fetch users from WSO2', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users from WSO2'
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error fetching users', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available applications for audience selection
     */
    public function getAvailableApplications()
    {
        try {
            $accessToken = $this->scimHelper->getTokenISApi();
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get access token'
                ], 401);
            }

            $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
            $tenantDomain = 'carbon.super';
            $endpoint = $baseUrl . '/t/' . $tenantDomain . '/o/api/server/v1/applications';
            
            // Add query parameters to get clientId and audience information
            $queryParams = [
                'attributes' => 'clientId,associatedRoles.allowedAudience'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'User-Agent' => 'Laravel-Application/1.0'
            ])
            ->withOptions([
                'verify' => false,
                'timeout' => 30
            ])
            ->get($endpoint, $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                $applications = [];
                
                if (isset($data['applications'])) {
                    foreach ($data['applications'] as $app) {
                        // Only include applications that have write access and are not system apps
                        if (isset($app['access']) && $app['access'] === 'WRITE' && 
                            !in_array($app['name'], ['Console', 'My Account'])) {
                            
                            $applications[] = [
                                'id' => $app['id'],
                                'name' => $app['name'],
                                'clientId' => $app['clientId'] ?? '',
                                'description' => $app['description'] ?? '',
                                'accessUrl' => $app['accessUrl'] ?? '',
                                'allowedAudience' => $app['associatedRoles']['allowedAudience'] ?? 'APPLICATION'
                            ];
                        }
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $applications
                ]);
            }
            
            Log::error('Failed to fetch applications from WSO2', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch applications from WSO2'
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error fetching applications', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching applications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available API resources and their scopes from WSO2
     */
    public function getApiResources()
    {
        try {
            // Check cache first
            $cacheKey = 'wso2_api_resources_scopes';
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                return response()->json([
                    'success' => true,
                    'data' => $cached
                ]);
            }

            $accessToken = $this->scimHelper->getTokenISApi();
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get access token'
                ], 401);
            }

            // Get all API resources
            $response = Http::withToken($accessToken)
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/o/api/server/v1/api-resources');

            if (!$response->successful()) {
                Log::error('Failed to fetch API resources from WSO2', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch API resources from WSO2'
                ], 500);
            }

            $apiResourcesData = $response->json();
            $apiResources = $apiResourcesData['APIResources'] ?? [];
            $allScopes = [];

            // Fetch detailed information for each API resource to get scopes
            foreach ($apiResources as $resource) {
                $resourceId = $resource['id'];
                
                $detailResponse = Http::withToken($accessToken)
                    ->withOptions(['verify' => false])
                    ->get(env('IS_URL') . "/o/api/server/v1/api-resources/{$resourceId}");

                if ($detailResponse->successful()) {
                    $resourceDetail = $detailResponse->json();
                    $scopes = $resourceDetail['scopes'] ?? [];
                    
                    foreach ($scopes as $scope) {
                        $allScopes[] = [
                            'id' => $scope['name'],
                            'name' => $scope['name'],
                            'displayName' => $scope['displayName'] ?? $scope['name'],
                            'description' => $scope['description'] ?? '',
                            'category' => $resourceDetail['name'] ?? 'API Resource'
                        ];
                    }
                }
            }

            // Cache for 30 minutes
            Cache::put($cacheKey, $allScopes, 1800);

            return response()->json([
                'success' => true,
                'data' => $allScopes
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching API resources', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a role is protected (cannot be deleted)
     */
    private function isProtectedRole($roleName)
    {
        $protectedRoles = ['everyone', 'admin', 'Application/everyone', 'Application/admin', 'system'];
        
        return in_array(strtolower($roleName), array_map('strtolower', $protectedRoles)) || 
               in_array($roleName, $protectedRoles);
    }

    /**
     * Get available organizations from WSO2
     */
    public function getAvailableOrganizations()
    {
        try {
            $accessToken = $this->scimHelper->getTokenISApi();
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get access token'
                ], 401);
            }

            $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
            $tenantDomain = 'carbon.super';
            $endpoint = $baseUrl . '/t/' . $tenantDomain . '/api/users/v1/me/organizations/root/descendants';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'User-Agent' => 'Laravel-Application/1.0'
            ])
            ->withOptions([
                'verify' => false,
                'timeout' => 30
            ])
            ->get($endpoint);

            if ($response->successful()) {
                $organizations = $response->json();
                
                // Ensure the response is an array
                if (!is_array($organizations)) {
                    $organizations = [];
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $organizations
                ]);
            }
            
            Log::error('Failed to fetch organizations from WSO2', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            // Return fallback data if API fails
            return response()->json([
                'success' => true,
                'data' => [
                    [
                        'id' => '10084a8d-113f-4211-a0d5-efe36b082211',
                        'name' => 'Super'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching organizations', [
                'error' => $e->getMessage()
            ]);
            
            // Return fallback data if exception occurs
            return response()->json([
                'success' => true,
                'data' => [
                    [
                        'id' => '10084a8d-113f-4211-a0d5-efe36b082211',
                        'name' => 'Super'
                    ]
                ]
            ]);
        }
    }

    /**
     * Get users assigned to a specific role
     */
    public function getRoleUsers($roleId)
    {
        try {
            // Get role details with users from the service
            $roleDetails = $this->rolePermissionService->getRoleDetails($roleId);

            if (!$roleDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $users = $roleDetails['users'] ?? [];
            // Map WSO2 user fields to expected frontend keys
            $users = array_map(function($user) {
                return [
                    'id' => $user['value'] ?? null,
                    'name' => $user['display'] ?? null,
                    'userName' => $user['display'] ?? null, // Use display for both if no username
                ];
            }, $users);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching role users', [
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching role users: ' . $e->getMessage()
            ], 500);
        }
    }
}
