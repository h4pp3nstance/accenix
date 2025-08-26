<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\WSO2OrganizationService;
use App\Services\CRMEmailService;

class OrganizationManagementController extends Controller
{
    private $wso2Service;
    private $emailService;

    public function __construct(WSO2OrganizationService $wso2Service, CRMEmailService $emailService)
    {
        $this->wso2Service = $wso2Service;
        $this->emailService = $emailService;
    }
    /**
     * Display organization listing
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getOrganizationsData($request);
        }
        
        return view('admin.crm.organizations.index');
    }
    
    /**
     * Get organizations data for DataTables using WSO2 service
     */
    public function getOrganizationsData(Request $request)
    {
        try {
            $filters = [
                'limit' => 1000
            ];
            
            // Apply filters from request
            if ($request->has('type') && $request->type) {
                // We'll filter after getting all organizations
            }
            
            if ($request->has('status') && $request->status) {
                $filters['status'] = $request->status;
            }
            
            if ($request->has('search') && $request->search) {
                $filters['name'] = $request->search;
            }

            $response = $this->wso2Service->getOrganizations($filters);
            $organizations = $response['organizations'] ?? [];
            
            // Separate lead organizations from active organizations
            $leadOrgs = [];
            $activeOrgs = [];
            
            foreach ($organizations as $org) {
                if (str_starts_with($org['name'], 'lead-')) {
                    $leadOrgs[] = $this->formatOrganizationData($org, 'lead');
                } else {
                    $activeOrgs[] = $this->formatOrganizationData($org, 'active');
                }
            }
            
            // Filter by type if requested
            $filterType = $request->get('type', 'all');
            if ($filterType === 'leads') {
                $data = $leadOrgs;
            } elseif ($filterType === 'active') {
                $data = $activeOrgs;
            } else {
                $data = array_merge($leadOrgs, $activeOrgs);
            }
            
            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => count($data),
                'recordsFiltered' => count($data),
                'data' => $data,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Organizations Data Error: ' . $e->getMessage());
            
            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show organization details using WSO2 service
     */
    public function show($id)
    {
        try {
            // Get organization details
            $organizationRaw = $this->wso2Service->getOrganization($id, true); // Include permissions
            if (!$organizationRaw) {
                return redirect()->route('admin.crm.organizations.index')
                    ->with('error', 'Organization not found');
            }
            // Format organization data for view (agar displayName tanpa 'lead-')
            $organization = $this->formatOrganizationData($organizationRaw, str_starts_with($organizationRaw['name'], 'lead-') ? 'lead' : 'active');
            // Get organization users
            $users = $this->wso2Service->getOrganizationUsers($id);
            // Get recent activities (simplified for now)
            $activities = $this->getOrganizationActivities($id);
            return view('admin.crm.organizations.show', compact(
                'organization', 
                'users', 
                'activities'
            ));
        } catch (\Exception $e) {
            \Log::error('Organization Show Error: ' . $e->getMessage());
            return redirect()->route('admin.crm.organizations.index')
                ->with('error', 'Error loading organization details');
        }
    }
    
    /**
     * Update organization information
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:ACTIVE,DISABLED'
            ]);
            
            $updateData = [
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status
            ];
            
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $id, $updateData);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Organization updated successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update organization'
            ], 400);
            
        } catch (\Exception $e) {
            \Log::error('Organization Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating organization: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Convert lead organization to active organization with email notifications
     */
    public function convertLead(Request $request, $id)
    {
        try {
            // Get current organization details
            $organization = $this->wso2Service->getOrganization($id);
            
            if (!$organization) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organization not found'
                ], 404);
            }
            
            // Check if it's a lead organization
            if (!str_starts_with($organization['name'], 'lead-')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This is not a lead organization'
                ], 400);
            }
            
            // Remove 'lead-' prefix from organization name
            $newName = str_replace('lead-', '', $organization['name']);
            $newDescription = str_replace('Lead organization for', 'Active organization for', $organization['description'] ?? '');
            
            // Use PATCH operations as per WSO2 API documentation
            $patchOperations = [
                [
                    'operation' => 'REPLACE',
                    'path' => '/name',
                    'value' => $newName
                ],
                [
                    'operation' => 'REPLACE',
                    'path' => '/description',
                    'value' => $newDescription
                ],
                [
                    'operation' => 'REPLACE',
                    'path' => '/status',
                    'value' => 'ACTIVE'
                ]
            ];
            
            $updatedOrg = $this->wso2Service->updateOrganization($id, $patchOperations);
            
            if ($updatedOrg) {
                \Log::info('Lead organization converted successfully', [
                    'org_id' => $id,
                    'old_name' => $organization['name'],
                    'new_name' => $newName,
                    'notes' => $request->input('notes', '')
                ]);
                
                // Get users before activating them
                $users = $this->wso2Service->getOrganizationUsers($id);
                
                // Activate associated users in the organization
                $this->activateOrganizationUsers($id, $newName, $users);
                
                // Send email notification about the conversion
                $this->emailService->sendConversionNotification($updatedOrg, $users);
                
                // Send welcome emails to activated users
                foreach ($users as $user) {
                    $this->emailService->sendWelcomeEmail($user, $updatedOrg);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => "Lead organization '{$organization['name']}' has been successfully converted to active organization '{$newName}'. Welcome emails sent to " . count($users) . " users."
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert lead organization'
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error('Lead Conversion Error: ' . $e->getMessage(), [
                'org_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error converting lead organization: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Format organization data for display
     */
    private function formatOrganizationData($org, $type)
    {
        $userCount = $this->getOrganizationUserCount($org['id']);
        
        return [
            'id' => $org['id'],
            'name' => $org['name'],
            'displayName' => $type === 'lead' ? str_replace('lead-', '', $org['name']) : $org['name'],
            'description' => $org['description'] ?? '',
            'type' => $type,
            'status' => $org['status'] ?? 'ACTIVE',
            // 'created' => $org['created'] ?? '',
            'lastModified' => $org['lastModified'] ?? '',
            // 'userCount' => $userCount,
            'parentId' => $org['parent']['id'] ?? null,
            'parentName' => $org['parent']['name'] ?? null
        ];
    }
    
    /**
     * Get organization users via SCIM with organization context
     */
    private function getOrganizationUsers($organizationId)
    {
        // try {
        //     // Gunakan WSO2OrganizationService yang sudah handle organization switch
        //     $users = $this->wso2Service->getOrganizationUsers($organizationId);
        //     \Log::debug('WSO2Service returned users', ['count' => count($users)]);
        //     return $users;
        // } catch (\Exception $e) {
        //     \Log::error('Get Organization Users Error: ' . $e->getMessage());
        // }
        
        // Temporarily disabled due to slow performance
        return [];
    }
    
    /**
     * Get organization user count
     */
    private function getOrganizationUserCount($organizationId)
    {
        // Simplified count - in real implementation, this should be more efficient
        return count($this->getOrganizationUsers($organizationId));
    }
    
    /**
     * Get organization applications
     */
    private function getOrganizationApplications($organizationId)
    {
        try {
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations/' . $organizationId . '/applications');
            
            if ($response->successful()) {
                return $response->json();
            }
            
        } catch (\Exception $e) {
            \Log::error('Get Organization Applications Error: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Activate all users in an organization using WSO2 service
     */
    private function activateOrganizationUsers($organizationId, $orgName = null, $users = null)
    {
        try {
            if (!$orgName) {
                // Get organization name if not provided
                $organization = $this->wso2Service->getOrganization($organizationId);
                if ($organization) {
                    $orgName = $organization['name'];
                } else {
                    \Log::error('Failed to get organization name for user activation');
                    return;
                }
            }
            
            if (!$users) {
                $users = $this->wso2Service->getOrganizationUsers($organizationId);
            }
            
            \Log::info('Activating users for organization', [
                'org_id' => $organizationId,
                'org_name' => $orgName,
                'user_count' => count($users)
            ]);
            
            foreach ($users as $user) {
                try {
                    // Use SCIM PATCH with organization context for user activation
                    $updateData = [
                        'schemas' => ['urn:ietf:params:scim:api:messages:2.0:PatchOp'],
                        'Operations' => [
                            [
                                'op' => 'replace',
                                'path' => 'active',
                                'value' => true
                            ]
                        ]
                    ];
                    
                    $success = $this->wso2Service->updateOrganizationUser($organizationId, $user['id'], $updateData);
                    
                    if ($success) {
                        \Log::info('User activated successfully', [
                            'user_id' => $user['id'],
                            'username' => $user['userName'] ?? 'unknown',
                            'org_name' => $orgName
                        ]);
                    } else {
                        \Log::error('Failed to activate user', [
                            'user_id' => $user['id'],
                            'username' => $user['userName'] ?? 'unknown',
                            'org_name' => $orgName
                        ]);
                    }
                        
                } catch (\Exception $e) {
                    \Log::error('Activate User Error: ' . $e->getMessage(), [
                        'user_id' => $user['id'] ?? 'unknown',
                        'org_name' => $orgName
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Activate Organization Users Error: ' . $e->getMessage(), [
                'org_id' => $organizationId,
                'org_name' => $orgName
            ]);
        }
    }

    /**
     * Get organization activities (simplified for WSO2-only implementation)
     */
    private function getOrganizationActivities($organizationId)
    {
        // For now, return empty array - in future this could track activities
        // by monitoring organization changes, user activations, etc.
        return [];
    }
}
