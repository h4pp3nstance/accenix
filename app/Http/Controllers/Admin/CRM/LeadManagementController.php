<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Traits\LeadActionsTrait;
use App\Services\WSO2OrganizationService;
use App\Services\LeadConversionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LeadManagementController extends Controller
{
    use LeadActionsTrait;
    
    private $wso2Service;
    private $leadConversionHandler;

    public function __construct(WSO2OrganizationService $wso2Service, LeadConversionHandler $leadConversionHandler)
    {
        $this->wso2Service = $wso2Service;
        $this->leadConversionHandler = $leadConversionHandler;
    }
    
    /**
     * Display lead listing with filters
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getLeadsData($request);
        }
        
        // Get filter options
        $filterOptions = $this->getFilterOptions();
        
        return view('admin.crm.leads.index', compact('filterOptions'));
    }
    
    /**
     * Get leads data for DataTables
     */
    public function getLeadsData(Request $request)
    {
        try {
            // Query organizations with "lead-" prefix instead of users
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations');
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Extract organizations array from the response wrapper
                $organizations = $responseData['organizations'] ?? [];
                
                // Filter organizations that start with "lead-"
                $leadOrganizations = array_filter($organizations, function($org) {
                    return isset($org['name']) && str_starts_with($org['name'], 'lead-');
                });
                
                // For each lead organization, get the full details including attributes
                $leads = [];
                foreach ($leadOrganizations as $leadOrg) {
                    $detailResponse = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                        ->withOptions(['verify' => false])
                        ->get(env('IS_URL') . '/api/server/v1/organizations/' . $leadOrg['id']);
                    
                    if ($detailResponse->successful()) {
                        $fullOrgData = $detailResponse->json();
                        $formattedLead = $this->formatLeadData($fullOrgData);
                        $leads[] = $formattedLead;
                    }
                }
                
                // Apply local filters
                if ($request->has('status') && $request->status !== '' && $request->status !== null) {
                    $leads = array_filter($leads, function($lead) use ($request) {
                        return strtolower($lead['status']) === strtolower($request->status);
                    });
                }
                
                // Apply date filters
                if ($request->has('date_from') && $request->date_from !== '' && $request->date_from !== null) {
                    $dateFrom = new \DateTime($request->date_from);
                    $leads = array_filter($leads, function($lead) use ($dateFrom) {
                        try {
                            $createdDate = new \DateTime($lead['created']);
                            return $createdDate >= $dateFrom;
                        } catch (\Exception $e) {
                            return true; // Include if date parsing fails
                        }
                    });
                }
                
                if ($request->has('date_to') && $request->date_to !== '' && $request->date_to !== null) {
                    $dateTo = new \DateTime($request->date_to . ' 23:59:59');
                    $leads = array_filter($leads, function($lead) use ($dateTo) {
                        try {
                            $createdDate = new \DateTime($lead['created']);
                            return $createdDate <= $dateTo;
                        } catch (\Exception $e) {
                            return true; // Include if date parsing fails
                        }
                    });
                }
                
                return response()->json([
                    'draw' => $request->input('draw'),
                    'recordsTotal' => count($leads),
                    'recordsFiltered' => count($leads),
                    'data' => array_values($leads),
                ]);
            }
            
            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to fetch leads data'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Leads Data Error: ' . $e->getMessage());
            
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
     * Show individual lead details
     */
    public function show($id)
    {
        try {
            // Get lead details from WSO2 Organizations API
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations/' . $id);
            
            if ($response->successful()) {
                $organization = $response->json();
                
                // Verify this is a lead organization
                if (!str_starts_with($organization['name'], 'lead-')) {
                    return redirect()->route('admin.crm.leads.index')
                        ->with('error', 'Organization is not a lead');
                }
                
                $lead = $this->formatLeadData($organization);
                
                // Get activity history (TODO: implement with organization attributes or database)
                $activities = $this->getLeadActivities($id);
                
                return view('admin.crm.leads.show', compact('lead', 'organization', 'activities'));
            }
            
            return redirect()->route('admin.crm.leads.index')
                ->with('error', 'Lead not found');
            
        } catch (\Exception $e) {
            \Log::error('Lead Show Error: ' . $e->getMessage());
            
            return redirect()->route('admin.crm.leads.index')
                ->with('error', 'Error loading lead details');
        }
    }
    
    /**
     * Update lead status and information
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:new,contacted,qualified,converted,rejected',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Menggunakan format PATCH sesuai WSO2 Organization API Documentation
            // Format: array langsung dengan path spesifik untuk setiap attribute
            $patchData = [
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/lead_status",
                    "value" => $request->status
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/last_contact_date",
                    "value" => now()->toISOString()
                ]
            ];

            // Add notes if provided
            if ($request->notes) {
                $patchData[] = [
                    "operation" => "REPLACE",
                    "path" => "/attributes/status_notes_" . time(),
                    "value" => $request->notes . ' | Updated by: ' . (auth()->user()->name ?? 'System') . ' | Date: ' . now()->format('Y-m-d H:i:s')
                ];
            }

            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $id, $patchData);

            if ($response->successful()) {
                \Log::info('Lead Status Updated Successfully', [
                    'lead_id' => $id,
                    'new_status' => $request->status,
                    'notes' => $request->notes
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Lead status updated successfully'
                ]);
            }

            \Log::error('WSO2 Update Status Failed', [
                'lead_id' => $id,
                'request_data' => $patchData,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead status'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Lead Status Update Error: ' . $e->getMessage(), [
                'lead_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating lead status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Activate a lead (approve lead for further processing)
     */
    public function activate($id)
    {
        try {
            // Menggunakan format PATCH sesuai WSO2 Organization API Documentation
            // Format: array langsung dengan path spesifik untuk setiap attribute
            $patchData = [
                [
                    "operation" => "REPLACE",
                    "path" => "/name",
                    "value" => request('orgName')
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/lead_status",
                    "value" => "qualified"
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/last_contact_date",
                    "value" => now()->toISOString()
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/approved_by",
                    "value" => auth()->user()->name ?? 'System'
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/approved_date",
                    "value" => now()->toISOString()
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/onboarding_status",
                    "value" => "approved"
                ]
            ];

            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $id, $patchData);

            if ($response->successful()) {
                \Log::info('Lead Approved Successfully', [
                    'lead_id' => $id,
                    'approved_by' => auth()->user()->name ?? 'System',
                    'approved_date' => now()->toISOString()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Lead approved and activated successfully'
                ]);
            }

            \Log::error('WSO2 Lead Activation Failed', [
                'lead_id' => $id,
                'request_data' => $patchData,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate lead'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Lead Activation Error: ' . $e->getMessage(), [
                'lead_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error activating lead: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Format lead data for display (from organization data)
     */
    private function formatLeadData($organization)
    {
        $attributes = $organization['attributes'] ?? [];
        $attributeMap = [];
        
        foreach ($attributes as $attr) {
            $attributeMap[$attr['key']] = $attr['value'];
        }
        
        return [
            'id' => $organization['id'],
            'userName' => $organization['name'], // Use organization name as identifier
            'displayName' => $attributeMap['alias'] ?? $organization['name'],
            'email' => $attributeMap['contact_email'] ?? '',
            'active' => false, // Leads are always inactive until converted
            'created' => $attributeMap['registration_date'] ?? $organization['created'] ?? '',
            'lastModified' => $organization['lastModified'] ?? '',
            'company_name' => $attributeMap['alias'] ?? '',
            'contact_person' => $attributeMap['contact_person'] ?? '',
            'contact_phone' => $attributeMap['contact_phone'] ?? '',
            'business_type' => $attributeMap['business_type'] ?? '',
            'address' => $attributeMap['address'] ?? '',
            'current_system' => $attributeMap['current_system'] ?? '',
            'specific_needs' => $attributeMap['specific_needs'] ?? '',
            'status' => $attributeMap['lead_status'] ?? 'new',
            'lead_source' => $attributeMap['lead_source'] ?? '',
            'organization_name' => str_replace('lead-', '', $organization['name']),
            'assigned_sales_rep' => $attributeMap['assigned_sales_rep'] ?? '',
            'estimated_value' => $attributeMap['estimated_value'] ?? '',
            'priority_level' => $attributeMap['priority_level'] ?? 'normal',
            'account_status' => $this->getAccountStatus($attributeMap, $organization),
            'last_contact_date' => $attributeMap['last_contact_date'] ?? '',
            'conversion_date' => $attributeMap['conversion_date'] ?? ''
        ];
    }
    
    /**
     * Determine account status based on organization data
     */
    private function getAccountStatus($attributeMap, $organization)
    {
        $leadStatus = $attributeMap['lead_status'] ?? 'new';
        $orgStatus = $organization['status'] ?? 'ACTIVE';
        $onboardingStatus = $attributeMap['onboarding_status'] ?? 'new';
        
        // Priority order: lead_status -> onboarding_status -> organization status
        switch ($leadStatus) {
            case 'new':
                return 'Prospective Client';
            case 'contacted':
                return 'Engaged Prospect';
            case 'qualified':
                return 'Qualified Lead';
            case 'converted':
                return 'Active Customer';
            case 'rejected':
                return 'Inactive Prospect';
            default:
                return $orgStatus === 'ACTIVE' ? 'Active Lead' : 'Inactive Lead';
        }
    }
    
    /**
     * Get lead organization details
     */
    private function getLeadOrganization($orgName)
    {
        try {
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations');
            
            if ($response->successful()) {
                $organizations = $response->json();
                
                foreach ($organizations as $org) {
                    if ($org['name'] === 'lead-' . $orgName) {
                        return $org;
                    }
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Get Lead Organization Error: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get lead activities (placeholder for database implementation)
     */
    private function getLeadActivities($leadId)
    {
        // For now, create sample activities based on lead status and timestamps
        $activities = [];
        
        try {
            // Get organization details to extract timestamps
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations/' . $leadId);
            
            if ($response->successful()) {
                $organization = $response->json();
                $lead = $this->formatLeadData($organization);
                
                // Add creation activity
                $activities[] = [
                    'id' => 1,
                    'title' => 'Lead Created',
                    'description' => 'Lead was created in the system',
                    'type' => 'system',
                    'created_at' => date('d M Y, H:i', strtotime($lead['created'])),
                    'user' => 'System',
                    'icon' => 'fas fa-plus-circle',
                    'color' => 'success'
                ];
                
                // Add status-based activities
                switch ($lead['status']) {
                    case 'contacted':
                        $activities[] = [
                            'id' => 2,
                            'title' => 'Lead Contacted',
                            'description' => 'Initial contact made with the lead',
                            'type' => 'contact',
                            'created_at' => date('d M Y, H:i', strtotime($lead['lastModified'])),
                            'user' => 'Sales Team',
                            'icon' => 'fas fa-phone',
                            'color' => 'primary'
                        ];
                        break;
                    case 'qualified':
                        $activities[] = [
                            'id' => 2,
                            'title' => 'Lead Contacted',
                            'description' => 'Initial contact made with the lead',
                            'type' => 'contact',
                            'created_at' => date('d M Y, H:i', strtotime($lead['created'] . ' +1 hour')),
                            'user' => 'Sales Team',
                            'icon' => 'fas fa-phone',
                            'color' => 'primary'
                        ];
                        $activities[] = [
                            'id' => 3,
                            'title' => 'Lead Qualified',
                            'description' => 'Lead has been qualified as a potential customer',
                            'type' => 'qualification',
                            'created_at' => date('d M Y, H:i', strtotime($lead['lastModified'])),
                            'user' => 'Sales Manager',
                            'icon' => 'fas fa-check-circle',
                            'color' => 'warning'
                        ];
                        break;
                    case 'converted':
                        $activities[] = [
                            'id' => 2,
                            'title' => 'Lead Contacted',
                            'description' => 'Initial contact made with the lead',
                            'type' => 'contact',
                            'created_at' => date('d M Y, H:i', strtotime($lead['created'] . ' +1 hour')),
                            'user' => 'Sales Team',
                            'icon' => 'fas fa-phone',
                            'color' => 'primary'
                        ];
                        $activities[] = [
                            'id' => 3,
                            'title' => 'Lead Qualified',
                            'description' => 'Lead has been qualified as a potential customer',
                            'type' => 'qualification',
                            'created_at' => date('d M Y, H:i', strtotime($lead['created'] . ' +2 hours')),
                            'user' => 'Sales Manager',
                            'icon' => 'fas fa-check-circle',
                            'color' => 'warning'
                        ];
                        $activities[] = [
                            'id' => 4,
                            'title' => 'Lead Converted',
                            'description' => 'Lead has been successfully converted to customer',
                            'type' => 'conversion',
                            'created_at' => date('d M Y, H:i', strtotime($lead['lastModified'])),
                            'user' => 'Sales Manager',
                            'icon' => 'fas fa-star',
                            'color' => 'success'
                        ];
                        break;
                }
                
                // Add last modified activity if different from created
                if ($lead['created'] !== $lead['lastModified'] && !in_array($lead['status'], ['converted', 'qualified'])) {
                    $activities[] = [
                        'id' => count($activities) + 1,
                        'title' => 'Lead Updated',
                        'description' => 'Lead information was updated',
                        'type' => 'update',
                        'created_at' => date('d M Y, H:i', strtotime($lead['lastModified'])),
                        'user' => 'System',
                        'icon' => 'fas fa-edit',
                        'color' => 'info'
                    ];
                }
                
                // Sort activities by created_at descending
                usort($activities, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching lead activities: ' . $e->getMessage());
        }
        
        return $activities;
    }
    
    /**
     * Convert lead to customer using LeadConversionHandler
     */
    public function convertLead(Request $request, $id)
    {
        try {
            // Validate request data
            $request->validate([
                'organization_name' => 'sometimes|string|max:255',
                'customer_name' => 'sometimes|string|max:255',
                'customer_email' => 'sometimes|email|max:255',
                'notes' => 'sometimes|string|max:1000'
            ]);

            // Get lead details first
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations/' . $id);
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }

            $organization = $response->json();
            $attributes = $organization['attributes'] ?? [];
            $attributeMap = [];
            
            foreach ($attributes as $attr) {
                $attributeMap[$attr['key']] = $attr['value'];
            }

            // Override attributes with modal input if provided
            if ($request->has('organization_name')) {
                $attributeMap['alias'] = $request->organization_name;
            }
            if ($request->has('customer_name')) {
                $attributeMap['contact_person'] = $request->customer_name;
            }
            if ($request->has('customer_email')) {
                $attributeMap['contact_email'] = $request->customer_email;
            }
            if ($request->has('notes')) {
                $attributeMap['conversion_notes'] = $request->notes;
            }

            // Use Handler for complete lead conversion workflow
            $result = $this->leadConversionHandler->handle($id, $organization, $attributeMap);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'invitation_sent' => $result['data']['invitation_sent'] ?? false,
                    'email_sent_to' => $result['data']['email_sent_to'] ?? null,
                    'organization_id' => $result['data']['organization_id'] ?? $id,
                    'name_changed' => [
                        'from' => $result['data']['old_name'] ?? null,
                        'to' => $result['data']['new_name'] ?? null
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Convert Lead Controller Error: ' . $e->getMessage(), [
                'lead_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert lead: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send customer registration invitation email with pre-filled data
     */
    private function sendCustomerRegistrationInvitation($organization, $attributeMap)
    {
        try {
            $contactEmail = $attributeMap['contact_email'] ?? '';
            $contactName = $attributeMap['contact_person'] ?? '';
            $companyName = $attributeMap['alias'] ?? $organization['name'] ?? '';

            if (empty($contactEmail) || empty($contactName)) {
                return [
                    'success' => false,
                    'message' => 'Missing required contact information for invitation'
                ];
            }

            // Generate WSO2 organization-scoped token
            $tokenResult = $this->generateWSO2InvitationToken($organization['id']);
            if (!$tokenResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to generate invitation token: ' . $tokenResult['message']
                ];
            }

            $accessToken = $tokenResult['access_token']; // Use access token directly
            $organizationId = $organization['id'];
            
            // Create invitation URL with access token directly (simplified)
            $invitationUrl = route('customer.register.invitation', [
                'token' => $accessToken
            ]) . '?' . http_build_query([
                'org' => $organizationId,
                'email' => base64_encode($contactEmail)
            ]);

            // Store invitation info in organization attributes for tracking (no cache needed)
            $tokenAttributes = [
                'invitation_expires' => $tokenResult['expires_at'],
                'invitation_created_by' => auth()->user()->name ?? 'System',
                'invitation_method' => 'wso2_direct_token' // Track method used
            ];
            
            // Get current organization attributes to determine ADD vs REPLACE
            $orgResponse = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations/' . $organizationId);
            
            $currentOrgAttributes = [];
            if ($orgResponse->successful()) {
                $orgData = $orgResponse->json();
                $attributes = $orgData['attributes'] ?? [];
                foreach ($attributes as $attr) {
                    $currentOrgAttributes[$attr['key']] = $attr['value'];
                }
            }
            
            $tokenPatchData = $this->buildAttributePatchData($currentOrgAttributes, $tokenAttributes);
            
            $tokenResponse = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $organizationId, $tokenPatchData);
            
            if (!$tokenResponse->successful()) {
                // Revoke tokens if attribute storage fails
                $this->revokeWSO2Token($accessToken);
                $this->revokeWSO2Token($tokenResult['initial_token']);
                \Log::error('Failed to store invitation token reference', [
                    'organization_id' => $organizationId,
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body()
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to store invitation reference'
                ];
            }

            // Send invitation email
            $emailData = [
                'customer_name' => $contactName,
                'company_name' => $companyName,
                'registration_url' => $invitationUrl,
                'expires_at' => now()->addDays(7)->format('M d, Y'),
                'expiry_hours' => 7 * 24, // 7 days = 168 hours
                'contact_email' => $contactEmail
            ];

            \Log::info('Sending customer registration invitation', [
                'organization_id' => $organizationId,
                'email' => $contactEmail,
                'invitation_url' => $invitationUrl
            ]);

            // Send email using Laravel Mail
            \Mail::send('emails.crm.customer-registration-invite', $emailData, function($message) use ($contactEmail, $contactName, $companyName) {
                $message->to($contactEmail, $contactName)
                        ->subject('Welcome! Complete Your ' . $companyName . ' Account Setup');
            });

            \Log::info('Customer registration invitation sent successfully', [
                'organization_id' => $organizationId,
                'email' => $contactEmail,
                'token' => substr($accessToken, 0, 12) . '...' // Log partial token for debugging
            ]);

            return [
                'success' => true,
                'invitation_url' => $invitationUrl,
                'email_sent_to' => $contactEmail,
                'expires_at' => now()->addDays(7)->toISOString()
            ];

        } catch (\Exception $e) {
            \Log::error('Customer registration invitation exception: ' . $e->getMessage(), [
                'organization_id' => $organization['id'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error sending invitation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send customer registration invitation instead of creating user directly
     * User will create their own account via secure invitation link
     */
    private function sendCustomerInvitation($organization, $attributeMap)
    {
        try {
            // Extract user information from lead attributes
            $contactEmail = $attributeMap['contact_email'] ?? '';
            $contactName = $attributeMap['contact_person'] ?? '';
            $companyName = $attributeMap['alias'] ?? $organization['name'] ?? '';

            if (empty($contactEmail) || empty($contactName)) {
                return [
                    'success' => false,
                    'message' => 'Missing required contact information (email or name)'
                ];
            }

            \Log::info('Sending customer registration invitation', [
                'organization_id' => $organization['id'],
                'organization_name' => $organization['name'],
                'contact_email' => $contactEmail,
                'contact_name' => $contactName
            ]);

            // Send invitation email with secure token for self-registration
            $invitationResult = $this->sendCustomerRegistrationInvitation($organization, $attributeMap);

            if ($invitationResult['success']) {
                \Log::info('Customer registration invitation sent successfully', [
                    'organization_id' => $organization['id'],
                    'email' => $contactEmail,
                    'invitation_url' => $invitationResult['invitation_url'],
                    'expires_at' => $invitationResult['expires_at']
                ]);

                return [
                    'success' => true,
                    'message' => 'Customer registration invitation sent successfully',
                    'invitation_sent' => true,
                    'email_sent_to' => $contactEmail,
                    'invitation_expires' => $invitationResult['expires_at'],
                    'invitation_url' => $invitationResult['invitation_url'] // For debugging/admin purposes
                ];
            } else {
                \Log::error('Failed to send customer registration invitation', [
                    'organization_id' => $organization['id'],
                    'error_message' => $invitationResult['message']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to send registration invitation: ' . $invitationResult['message']
                ];
            }

        } catch (\Exception $e) {
            \Log::error('Customer registration invitation exception: ' . $e->getMessage(), [
                'organization_id' => $organization['id'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error sending registration invitation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build patch data for WSO2 organization attributes dynamically
     * Uses REPLACE for existing attributes and ADD for new ones
     */
    private function buildAttributePatchData($currentAttributeMap, $attributesToUpdate)
    {
        $patchData = [];
        
        foreach ($attributesToUpdate as $key => $value) {
            $operation = isset($currentAttributeMap[$key]) ? "REPLACE" : "ADD";
            
            $patchData[] = [
                "operation" => $operation,
                "path" => "/attributes/" . $key,
                "value" => $value
            ];
        }
        
        \Log::info('Built attribute patch data', [
            'current_attributes' => array_keys($currentAttributeMap),
            'new_attributes' => array_keys($attributesToUpdate),
            'patch_operations' => array_map(function($item) {
                return $item['operation'] . ' ' . $item['path'];
            }, $patchData)
        ]);
        
        return $patchData;
    }

    /**
     * Generate WSO2-compliant password
     */
    private function generateCompliantPassword()
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';

        // Ensure we have at least one from each category
        $password = '';
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $special[rand(0, strlen($special) - 1)];

        // Fill remaining with random mix
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < 12; $i++) {
            $password .= $allChars[rand(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }
    
    /**
     * Bulk activate selected leads
     */
    public function bulkActivate(Request $request)
    {
        try {
            $request->validate([
                'lead_ids' => 'required|array',
                'lead_ids.*' => 'required|string'
            ]);
            
            $leadIds = $request->lead_ids;
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($leadIds as $leadId) {
                try {
                    $updateData = [
                        'schemas' => ['urn:ietf:params:scim:api:messages:2.0:PatchOp'],
                        'Operations' => [
                            [
                                'op' => 'replace',
                                'path' => 'active',
                                'value' => true
                            ],
                            [
                                'op' => 'replace',
                                'path' => 'urn:scim:wso2:schema.attributes[key eq "lead_status"].value',
                                'value' => 'converted'
                            ]
                        ]
                    ];
                    
                    $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                        ->withOptions(['verify' => false])
                        ->patch(env('IS_URL') . '/scim2/Users/' . $leadId, $updateData);
                    
                    if ($response->successful()) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to activate lead: " . $leadId;
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Error activating lead {$leadId}: " . $e->getMessage();
                }
            }
            
            $message = "Bulk activation completed. {$successCount} leads activated successfully.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} leads failed to activate.";
            }
            
            return response()->json([
                'success' => $errorCount === 0,
                'message' => $message,
                'details' => [
                    'total' => count($leadIds),
                    'success' => $successCount,
                    'errors' => $errorCount,
                    'error_details' => $errors
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Bulk Lead Activation Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error during bulk activation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export leads to Excel
     */
    public function export(Request $request)
    {
        try {
            // Get all organizations
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations');
            
            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to fetch organizations data'], 500);
            }
            
            $responseData = $response->json();
            $organizations = $responseData['organizations'] ?? [];
            
            // Filter lead organizations
            $leadOrganizations = array_filter($organizations, function($org) {
                return isset($org['name']) && str_starts_with($org['name'], 'lead-');
            });
            
            // Get full details for each lead organization
            $leads = [];
            foreach ($leadOrganizations as $leadOrg) {
                $detailResponse = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                    ->withOptions(['verify' => false])
                    ->get(env('IS_URL') . '/api/server/v1/organizations/' . $leadOrg['id']);
                
                if ($detailResponse->successful()) {
                    $fullOrgData = $detailResponse->json();
                    $leads[] = $this->formatLeadData($fullOrgData);
                }
            }
            
            // Apply filters
            if ($request->has('status') && $request->status !== '') {
                $leads = array_filter($leads, function($lead) use ($request) {
                    return strtolower($lead['status']) === strtolower($request->status);
                });
            }
            
            if ($request->has('date_from') && $request->date_from !== '') {
                $dateFrom = new \DateTime($request->date_from);
                $leads = array_filter($leads, function($lead) use ($dateFrom) {
                    $createdDate = new \DateTime($lead['created']);
                    return $createdDate >= $dateFrom;
                });
            }
            
            if ($request->has('date_to') && $request->date_to !== '') {
                $dateTo = new \DateTime($request->date_to . ' 23:59:59');
                $leads = array_filter($leads, function($lead) use ($dateTo) {
                    $createdDate = new \DateTime($lead['created']);
                    return $createdDate <= $dateTo;
                });
            }
            
            // Prepare export data
            $exportData = [];
            $exportData[] = [
                'Company Name',
                'Contact Person',
                'Email',
                'Phone',
                'Business Type',
                'Address',
                'Current System',
                'Specific Needs',
                'Status',
                'Lead Source',
                'Active',
                'Created Date',
                'Last Modified'
            ];
            
            foreach ($leads as $lead) {
                $exportData[] = [
                    $lead['company_name'],
                    $lead['contact_person'],
                    $lead['email'],
                    $lead['contact_phone'],
                    $lead['business_type'],
                    $lead['address'],
                    $lead['current_system'],
                    $lead['specific_needs'],
                    $lead['status'],
                    $lead['lead_source'],
                    $lead['active'] ? 'Yes' : 'No',
                    date('Y-m-d H:i:s', strtotime($lead['created'])),
                    date('Y-m-d H:i:s', strtotime($lead['lastModified']))
                ];
            }
            
            // Create Excel file
            $filename = 'leads_export_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];
            
            $callback = function() use ($exportData) {
                $file = fopen('php://output', 'w');
                foreach ($exportData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            \Log::error('Lead Export Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule a follow-up for the lead
     */
    public function scheduleFollowup(Request $request, $id)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:call,email,meeting,demo',
                'date' => 'required|date|after:today',
                'time' => 'required|string',
                'notes' => 'nullable|string|max:1000'
            ]);

            $followupDateTime = $request->date . ' ' . $request->time;
            $followupId = 'followup_' . time();

            // Menggunakan format PATCH sesuai WSO2 Organization API Documentation
            // Format: array langsung dengan path spesifik untuk setiap attribute
            $patchData = [
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/" . $followupId,
                    "value" => json_encode([
                        'type' => $request->type,
                        'scheduled_date' => $followupDateTime,
                        'notes' => $request->notes,
                        'status' => 'scheduled',
                        'created_by' => auth()->user()->name ?? 'System',
                        'created_at' => now()->toISOString()
                    ])
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/next_followup_date",
                    "value" => $followupDateTime
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/last_contact_date",
                    "value" => now()->toISOString()
                ]
            ];

            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $id, $patchData);

            if ($response->successful()) {
                \Log::info('Follow-up Scheduled Successfully', [
                    'lead_id' => $id,
                    'followup_type' => $request->type,
                    'scheduled_date' => $followupDateTime,
                    'created_by' => auth()->user()->name ?? 'System'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Follow-up scheduled successfully'
                ]);
            }

            \Log::error('WSO2 Schedule Follow-up Failed', [
                'lead_id' => $id,
                'request_data' => $patchData,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule follow-up'
            ], 500);

        } catch (\Exception $e) {
            \Log::error('Schedule Follow-up Error: ' . $e->getMessage(), [
                'lead_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while scheduling follow-up'
            ], 500);
        }
    }

    /**
     * Get filter options for the interface
     */
    private function getFilterOptions()
    {
        return [
            'statuses' => [
                'new' => 'New',
                'contacted' => 'Contacted',
                'qualified' => 'Qualified',
                'converted' => 'Converted',
                'rejected' => 'Rejected'
            ],
            'sources' => [
                'website' => 'Website',
                'referral' => 'Referral',
                'social_media' => 'Social Media',
                'email_campaign' => 'Email Campaign'
            ]
        ];
    }

    // ==========================================
    // WSO2 TOKEN MANAGEMENT METHODS
    // ==========================================

    /**
     * Generate WSO2 organization-scoped token for customer invitation with automatic revocation
     */
    /**
     * Generate WSO2 invitation token (simplified - direct token approach)
     */
    private function generateWSO2InvitationToken($organizationId)
    {
        try {
            // Initialize WSO2 Organization Service
            $wso2Service = app('App\Services\WSO2OrganizationService');

            // Step 1: Get initial access token
            $initialToken = $wso2Service->getInitialAccessToken();
            if (!$initialToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get initial access token'
                ];
            }

            // Step 2: Switch to organization context
            $orgToken = $wso2Service->switchToOrganization($initialToken, $organizationId);
            if (!$orgToken) {
                // Revoke initial token since we failed
                $this->revokeWSO2Token($initialToken);
                return [
                    'success' => false,
                    'message' => 'Failed to switch to organization context'
                ];
            }

            \Log::info('WSO2 invitation token generated successfully', [
                'token' => substr($orgToken, 0, 12) . '...',
                'organization_id' => $organizationId,
                'created_by' => auth()->user()->name ?? 'System'
            ]);
            
            // Return the access token directly - no cache needed
            return [
                'success' => true,
                'access_token' => $orgToken,
                'initial_token' => $initialToken,
                'expires_at' => now()->addHours(1)->toISOString() // WSO2 default token expiry
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to generate WSO2 invitation token', [
                'organization_id' => $organizationId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to generate invitation token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Revoke WSO2 access token using existing implementation
     */
    private function revokeWSO2Token(?string $accessToken): bool
    {
        if (!$accessToken || !env('IS_REVOKE_URL')) {
            return false;
        }
        
        try {
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $response = $client->post(env('IS_REVOKE_URL'), [
                'form_params' => [
                    'token' => $accessToken,
                    'token_type_hint' => 'access_token',
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(env('IS_CLIENT_ID') . ':' . env('IS_CLIENT_SECRET')),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'timeout' => 10,
            ]);
            
            if ($response->getStatusCode() === 200) {
                \Log::info('WSO2 token revoked successfully', [
                    'token_hint' => substr($accessToken, 0, 10) . '...'
                ]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            \Log::warning('Failed to revoke WSO2 token', [
                'error' => $e->getMessage(),
                'token_hint' => substr($accessToken, 0, 10) . '...'
            ]);
            return false;
        }
    }

    /**
     * Revoke invitation tokens and cleanup (simplified - no cache)
     * Note: With simplified approach, tokens are revoked directly during verification
     */
    private function revokeInvitationTokens($tokenReference): bool
    {
        \Log::info('Legacy revokeInvitationTokens called - tokens now handled directly', [
            'reference' => substr($tokenReference, 0, 12) . '...',
            'note' => 'Tokens are revoked during verification process'
        ]);
        
        // With simplified approach, tokens are revoked during verification
        // This method kept for backward compatibility but no action needed
        return true;
    }

    /**
     * Manually revoke invitation token for a lead
     */
    public function revokeInvitationToken(Request $request, $leadId)
    {
        try {
            // Get organization details to extract token reference
            $orgResponse = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations/' . $leadId);

            if (!$orgResponse->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organization not found'
                ], 404);
            }

            $organization = $orgResponse->json();
            $attributes = $organization['attributes'] ?? [];
            $attributeMap = [];
            
            foreach ($attributes as $attr) {
                $attributeMap[$attr['key']] = $attr['value'];
            }

            $tokenReference = $attributeMap['invitation_token_reference'] ?? null;
            
            if ($tokenReference) {
                // Revoke WSO2 tokens using token reference
                $this->revokeInvitationTokens($tokenReference);
                
                // Clear the token reference from organization attributes
                $patchData = [
                    [
                        "operation" => "REMOVE",
                        "path" => "/attributes/invitation_token_reference"
                    ],
                    [
                        "operation" => "REMOVE", 
                        "path" => "/attributes/invitation_expires"
                    ]
                ];

                Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                    ->withOptions(['verify' => false])
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $leadId, $patchData);

                \Log::info('Invitation token manually revoked by admin', [
                    'lead_id' => $leadId,
                    'admin_user' => auth()->user()->username ?? 'unknown',
                    'token_reference' => substr($tokenReference, 0, 8) . '...'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Invitation token successfully revoked'
                ]);
            }

            // Check for legacy invitation token
            $legacyToken = $attributeMap['invitation_token'] ?? null;
            if ($legacyToken) {
                // Remove legacy invitation token
                $patchData = [
                    [
                        "operation" => "REMOVE",
                        "path" => "/attributes/invitation_token"
                    ],
                    [
                        "operation" => "REMOVE",
                        "path" => "/attributes/invitation_expires"  
                    ]
                ];

                Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                    ->withOptions(['verify' => false])
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $leadId, $patchData);

                \Log::info('Legacy invitation token manually revoked by admin', [
                    'lead_id' => $leadId,
                    'admin_user' => auth()->user()->username ?? 'unknown'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Legacy invitation token successfully revoked'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No invitation token found to revoke'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error revoking invitation token: ' . $e->getMessage(), [
                'lead_id' => $leadId,
                'admin_user' => auth()->user()->username ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke invitation token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual revocation API untuk admin (legacy method - kept for compatibility)
     */
    public function revokeInvitation(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'token_reference' => 'required|string'
        ]);

        $tokenReference = $request->input('token_reference');
        
        if ($this->revokeInvitationTokens($tokenReference)) {
            return response()->json([
                'success' => true,
                'message' => 'Invitation revoked successfully'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to revoke invitation or invitation not found'
        ], 400);
    }
}
