<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Services\WSO2OrganizationService;

class CustomerRegistrationController extends Controller
{
    protected $wso2Service;

    public function __construct(WSO2OrganizationService $wso2Service)
    {
        $this->wso2Service = $wso2Service;
    }

    /**
     * Show customer registration form from invitation link
     */
    public function showInvitationForm(Request $request, $token)
    {
        try {
            // Get organization and email from URL parameters
            $organizationId = $request->query('org');
            $encodedEmail = $request->query('email');
            
            if (empty($organizationId) || empty($encodedEmail)) {
                return redirect()->route('home')->with('error', 'Invalid invitation link');
            }

            $prefilledEmail = base64_decode($encodedEmail);
            
            // Verify invitation token and get organization details
            $organization = $this->verifyInvitationToken($organizationId, $token);
            
            if (!$organization) {
                // Log security attempt for audit
                \Log::warning('Invalid invitation attempt', [
                    'organization_id' => $organizationId,
                    'token' => substr($token, 0, 8) . '...',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                
                return redirect()->route('home')->with('error', 'Invalid or expired invitation link');
            }

            // Get organization attributes for pre-filling
            $attributes = $organization['attributes'] ?? [];
            $attributeMap = [];
            
            foreach ($attributes as $attr) {
                $attributeMap[$attr['key']] = $attr['value'];
            }

            $prefilledData = [
                'email' => $prefilledEmail,
                'organization_id' => $organizationId,
                'organization_name' => $organization['name'],
                'company_name' => $attributeMap['company_name_original'] ?? $organization['name'],
                'contact_person' => $attributeMap['contact_person'] ?? '',
                'token' => $token
            ];

            return view('auth.customer-register', compact('prefilledData'));

        } catch (\Exception $e) {
            \Log::error('Customer invitation form error: ' . $e->getMessage());
            return redirect()->route('home')->with('error', 'Error loading registration form');
        }
    }

    /**
     * Handle customer registration form submission
     */
    public function submitRegistration(Request $request, $token)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'organization_id' => 'required|string',
                'first_name' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'username' => 'required|string|max:255|alpha_num',
                'email' => 'required|email|max:255',
                'phone' => [
                    'required',
                    'regex:/^\+\d{8,15}$/',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:30',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,30}$/',
                ],
            ], [
                'password.regex' => 'Password must be 8-30 characters, include at least 1 uppercase, 1 lowercase, 1 number, and 1 special character.',
                'phone.regex' => 'Phone number must start with + and include country code, e.g. +628123456789.',
                'username.alpha_num' => 'Username must contain only letters and numbers.'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $organizationId = $request->input('organization_id');
            
            // Verify invitation token again
            $organization = $this->verifyInvitationToken($organizationId, $token);
            
            if (!$organization) {
                return back()->withErrors(['token' => 'Invalid or expired invitation link'])->withInput();
            }

            // Create user in WSO2 using organization_switch flow
            $userResult = $this->createCustomerUserAccount($request, $organizationId);
            
            if (!$userResult['success']) {
                return back()->withErrors(['registration' => $userResult['message']])->withInput();
            }

            // Update organization status to mark registration complete
            $this->markRegistrationComplete($organizationId, $userResult['user_id']);

            // Send welcome email to user
            $this->sendWelcomeEmail($request->input('email'), $request->input('first_name'));

            \Log::info('Customer registration completed successfully', [
                'organization_id' => $organizationId,
                'user_id' => $userResult['user_id'],
                'email' => $request->input('email')
            ]);

            return redirect()->route('home')->with('success', 'Registration completed successfully! You can now login to your account.');

        } catch (\Exception $e) {
            \Log::error('Customer registration error: ' . $e->getMessage(), [
                'token' => $token,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['registration' => 'Registration failed. Please try again.'])->withInput();
        }
    }

    /**
     * Handle customer registration form submission (simplified flow)
     */
    public function submit(Request $request)
    {
        try {
            $organizationId = $request->query('org');
            $token = $request->token;
            
            \Log::info('Processing customer registration', [
                'organization_id' => $organizationId,
                'token' => substr($token, 0, 8) . '...',
                'email' => $request->email
            ]);
            
            // Step 1: Validate token directly with WSO2
            $organization = null;
            $leadData = null;
            
            if ($organizationId && $token) {
                $organization = $this->verifyInvitationToken($organizationId, $token);
                
                if ($organization) {
                    // Convert to leadData format for compatibility
                    $attributes = $organization['attributes'] ?? [];
                    $attributeMap = [];
                    
                    foreach ($attributes as $attr) {
                        $attributeMap[$attr['key']] = $attr['value'];
                    }
                    
                    $leadData = [
                        'valid' => true,
                        'lead_id' => $organization['id'],
                        'data' => $attributeMap
                    ];
                }
            }
            
            // Fallback to legacy validation if needed
            if (!$leadData) {
                $leadData = $this->validateRegistrationToken($token);
            }
            
            if (!$leadData['valid']) {
                return back()->withErrors(['token' => $leadData['message'] ?? 'Invalid invitation token'])->withInput();
            }

            // Validate form data
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'first_name' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_]+$/',
                'email' => 'required|email|max:255',
                'phone' => [
                    'required',
                    'regex:/^\+\d{8,15}$/',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:30',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,30}$/',
                ],
            ], [
                'password.regex' => 'Password must be 8-30 characters, include at least 1 uppercase, 1 lowercase, 1 number, and 1 special character.',
                'phone.regex' => 'Phone number must start with + and include country code, e.g. +628123456789.',
                'username.regex' => 'Username can only contain letters, numbers, and underscores.'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Verify email matches the lead contact email
            if ($request->email !== $leadData['data']['contact_email']) {
                return back()->withErrors(['email' => 'Email must match the registered contact email for this company.'])->withInput();
            }

            // Create user using WSO2 organization_switch flow
            $userCreationResult = $this->createCustomerUser($request, $leadData['data']);

            if (!$userCreationResult['success']) {
                return back()->withErrors(['general' => 'Failed to create user account: ' . $userCreationResult['message']])->withInput();
            }

            // Update lead status to completed registration
            $this->updateLeadRegistrationStatus($leadData['lead_id'], $userCreationResult['user_id']);

            // Invalidate registration token
            $this->invalidateRegistrationToken($leadData['lead_id']);

            // Mark invitation token as used (instead of revoking immediately for audit)
            $this->markInvitationTokenAsUsed($request->token);

            // Redirect to success page
            return view('auth.customer-register-success', [
                'user_id' => $userCreationResult['user_id'],
                'username' => $request->username,
                'company_name' => $leadData['data']['company_name']
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer registration submission error: ' . $e->getMessage(), [
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return back()->withErrors(['general' => 'An error occurred during registration. Please try again.'])->withInput();
        }
    }

    /**
     * Validate registration token and get lead data
     */
    private function validateRegistrationToken($token)
    {
        try {
            // Get all organizations and find matching token
            $organizations = $this->wso2Service->getOrganizations(['limit' => 1000]);
            
            foreach ($organizations['organizations'] ?? [] as $org) {
                $attributes = $org['attributes'] ?? [];
                $attributeMap = [];
                
                foreach ($attributes as $attr) {
                    $attributeMap[$attr['key']] = $attr['value'];
                }

                // Check if this organization has the matching token
                if (($attributeMap['registration_token'] ?? '') === $token) {
                    // Check if token is not expired
                    $expiryDate = $attributeMap['registration_token_expiry'] ?? '';
                    if ($expiryDate && now()->isAfter($expiryDate)) {
                        return [
                            'valid' => false,
                            'message' => 'Registration link has expired. Please contact our support team for assistance.'
                        ];
                    }

                    // Check if lead is in correct status
                    if (($attributeMap['lead_status'] ?? '') !== 'converted') {
                        return [
                            'valid' => false,
                            'message' => 'This registration link is not valid. The lead has not been converted yet.'
                        ];
                    }

                    // Check if registration is already completed
                    if (($attributeMap['customer_status'] ?? '') === 'active') {
                        return [
                            'valid' => false,
                            'message' => 'Registration has already been completed for this company.'
                        ];
                    }

                    return [
                        'valid' => true,
                        'lead_id' => $org['id'],
                        'data' => $attributeMap
                    ];
                }
            }

            return [
                'valid' => false,
                'message' => 'Invalid registration token. Please check your email link or contact support.'
            ];

        } catch (\Exception $e) {
            \Log::error('Token validation error: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Error validating registration token. Please try again.'
            ];
        }
    }

    /**
     * Create customer user using WSO2 organization_switch flow with superadmin role
     */
    private function createCustomerUser($request, $leadData)
    {
        try {
            $organizationId = $leadData['organization_id'] ?? $leadData['lead_id'] ?? '';
            
            // Prepare user payload using exact format from requirement with superadmin role
            $payload = [
                'emails' => [
                    [
                        'primary' => true,
                        'value' => $request->email
                    ]
                ],
                'name' => [
                    'familyName' => $request->last_name ?: 'Customer',
                    'givenName' => $request->first_name
                ],
                'password' => $request->password,
                'userName' => 'PRIMARY/' . $request->username,
                'schemas' => [
                    'urn:ietf:params:scim:schemas:core:2.0:User',
                    'urn:scim:wso2:schema',
                    'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User'
                ],
                'active' => true,
                'phoneNumbers' => [
                    [
                        'type' => 'work',
                        'value' => $request->phone,
                        'primary' => true
                    ]
                ],
                'urn:scim:wso2:schema' => [
                    'role' => 'superadmin', // Assign superadmin role as requested
                    'organization_id' => $organizationId,
                    'customer_status' => 'active',
                    'onboarding_status' => 'completed',
                    'registration_source' => 'invitation'
                ],
                'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User' => [
                    'accountDisabled' => false
                ]
            ];

            \Log::info('Creating customer user with superadmin role', [
                'organization_id' => $organizationId,
                'username' => $request->username,
                'email' => $request->email,
                'role' => 'superadmin'
            ]);

            // Use WSO2OrganizationService to create user with organization_switch flow
            $result = $this->wso2Service->createUserInOrganization($payload, $organizationId);

            if ($result['success']) {
                \Log::info('Customer user created successfully with superadmin role', [
                    'organization_id' => $organizationId,
                    'user_id' => $result['user_id'],
                    'username' => $request->username,
                    'role' => 'superadmin'
                ]);

                return [
                    'success' => true,
                    'user_id' => $result['user_id'],
                    'username' => $request->username,
                    'role' => 'superadmin'
                ];
            } else {
                \Log::error('Failed to create customer user with superadmin role', [
                    'organization_id' => $organizationId,
                    'error' => $result['message'],
                    'payload' => $payload
                ]);

                return [
                    'success' => false,
                    'message' => $result['message']
                ];
            }

        } catch (\Exception $e) {
            \Log::error('Customer user creation exception: ' . $e->getMessage(), [
                'organization_id' => $organizationId ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'Error creating user account: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update lead status after successful registration
     */
    private function updateLeadRegistrationStatus($leadId, $userId)
    {
        try {
            $patchData = [
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/customer_status",
                    "value" => "active"
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/onboarding_status",
                    "value" => "completed"
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/user_id",
                    "value" => $userId
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/registration_completed_date",
                    "value" => now()->toISOString()
                ]
            ];

            Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $leadId, $patchData);

            \Log::info('Lead registration status updated', [
                'lead_id' => $leadId,
                'user_id' => $userId
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to update lead registration status: ' . $e->getMessage());
        }
    }

    /**
     * Invalidate registration token
     */
    private function invalidateRegistrationToken($leadId)
    {
        try {
            $patchData = [
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/registration_token",
                    "value" => "USED_" . time()
                ]
            ];

            Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $leadId, $patchData);

        } catch (\Exception $e) {
            \Log::error('Failed to invalidate registration token: ' . $e->getMessage());
        }
    }

    /**
     * Verify invitation token and get organization details
     */
    /**
     * Verify invitation token directly with WSO2 (simplified approach)
     */
    private function verifyInvitationToken($organizationId, $token)
    {
        try {
            \Log::info('Verifying invitation token with WSO2', [
                'organization_id' => $organizationId,
                'token' => substr($token, 0, 8) . '...'
            ]);
            
            // Step 1: Check if token is still active using WSO2 introspection
            $introspectionResult = $this->introspectWSO2Token($token);
            
            if (!$introspectionResult['success']) {
                \Log::warning('WSO2 introspection failed', [
                    'organization_id' => $organizationId,
                    'error' => $introspectionResult['error']
                ]);
                return null;
            }
            
            if (!$introspectionResult['active']) {
                \Log::warning('Token is not active in WSO2', [
                    'organization_id' => $organizationId,
                    'token' => substr($token, 0, 8) . '...'
                ]);
                return null;
            }
            
            // Step 2: Get organization details using admin API
            $orgResponse = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations/' . $organizationId);

            if (!$orgResponse->successful()) {
                \Log::warning('Organization not found', [
                    'organization_id' => $organizationId,
                    'status' => $orgResponse->status()
                ]);
                return null;
            }
            
            $organization = $orgResponse->json();
            
            // Step 3: Validate organization attributes
            $attributes = $organization['attributes'] ?? [];
            $attributeMap = [];
            
            foreach ($attributes as $attr) {
                $attributeMap[$attr['key']] = $attr['value'];
            }
            
            // Check if customer is already registered
            if (($attributeMap['customer_status'] ?? '') === 'active') {
                \Log::warning('Customer already registered for this organization', [
                    'organization_id' => $organizationId
                ]);
                return null;
            }
            
            \Log::info('Token validation successful', [
                'organization_id' => $organizationId,
                'token' => substr($token, 0, 8) . '...'
            ]);
            
            return $organization;

        } catch (\Exception $e) {
            \Log::error('Error verifying invitation token: ' . $e->getMessage(), [
                'organization_id' => $organizationId,
                'token' => substr($token, 0, 8) . '...'
            ]);
            return null;
        }
    }

    /**
     * Validate WSO2 access token against organization switch endpoint
     */
    private function validateWSO2Token($wso2Token, $organizationId)
    {
        try {
            // Use organization switch to validate token and get organization details
            $response = Http::withToken($wso2Token)
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(env('IS_URL') . '/api/users/v1/me/organizations/' . $organizationId . '/switch');

            if ($response->successful()) {
                // Token is valid - get organization details
                $orgResponse = Http::withToken($wso2Token)
                    ->withOptions(['verify' => false])
                    ->get(env('IS_URL') . '/api/server/v1/organizations/' . $organizationId);

                if ($orgResponse->successful()) {
                    return $orgResponse->json();
                }
            }

            \Log::warning('WSO2 token validation failed', [
                'organization_id' => $organizationId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;

        } catch (\Exception $e) {
            \Log::error('WSO2 token validation error: ' . $e->getMessage(), [
                'organization_id' => $organizationId
            ]);
            return null;
        }
    }

    /**
     * Legacy invitation token verification (for backward compatibility)
     */
    private function verifyLegacyInvitationToken($organizationId, $token)
    {
        try {
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->get(env('IS_URL') . '/api/server/v1/organizations/' . $organizationId);

            if (!$response->successful()) {
                return null;
            }

            $organization = $response->json();
            $attributes = $organization['attributes'] ?? [];
            $attributeMap = [];
            
            foreach ($attributes as $attr) {
                $attributeMap[$attr['key']] = $attr['value'];
            }

            // Check token
            $storedToken = $attributeMap['invitation_token'] ?? '';
            $expiresAt = $attributeMap['invitation_expires'] ?? '';

            if ($storedToken !== $token) {
                \Log::warning('Invalid legacy invitation token', [
                    'organization_id' => $organizationId,
                    'provided_token' => substr($token, 0, 8) . '...',
                    'stored_token' => substr($storedToken, 0, 8) . '...'
                ]);
                return null;
            }

            // Check expiration - STRICT enforcement
            if (!empty($expiresAt)) {
                $expiryDate = \Carbon\Carbon::parse($expiresAt);
                if (now()->isAfter($expiryDate)) {
                    \Log::warning('Expired legacy invitation token - access denied', [
                        'organization_id' => $organizationId,
                        'token' => substr($token, 0, 8) . '...',
                        'expires_at' => $expiresAt,
                        'current_time' => now()->toISOString(),
                        'hours_past_expiry' => now()->diffInHours($expiryDate, false)
                    ]);
                    return null;
                }
            } else {
                // If no expiry date is set, check if token was created more than 7 days ago
                // Use lead conversion date as proxy for token creation
                $conversionDate = $attributeMap['conversion_date'] ?? '';
                if (!empty($conversionDate)) {
                    $tokenCreatedAt = \Carbon\Carbon::parse($conversionDate);
                    $defaultExpiryAt = $tokenCreatedAt->addDays(7);
                    
                    if (now()->isAfter($defaultExpiryAt)) {
                        \Log::warning('Legacy invitation token expired (default 7-day rule)', [
                            'organization_id' => $organizationId,
                            'token' => substr($token, 0, 8) . '...',
                            'conversion_date' => $conversionDate,
                            'default_expiry' => $defaultExpiryAt->toISOString(),
                            'current_time' => now()->toISOString()
                        ]);
                        return null;
                    }
                }
            }

            // Check if customer has already completed registration (prevent reuse)
            $customerStatus = $attributeMap['customer_status'] ?? '';
            if ($customerStatus === 'active') {
                \Log::warning('Legacy invitation token - customer already registered', [
                    'organization_id' => $organizationId,
                    'token' => substr($token, 0, 8) . '...',
                    'customer_status' => $customerStatus
                ]);
                return null;
            }

            \Log::info('Legacy invitation token validated successfully', [
                'organization_id' => $organizationId,
                'token' => substr($token, 0, 8) . '...',
                'expires_at' => $expiresAt
            ]);

            return $organization;

        } catch (\Exception $e) {
            \Log::error('Legacy invitation token verification error: ' . $e->getMessage(), [
                'organization_id' => $organizationId
            ]);
            return null;
        }
    }

    /**
     * Revoke WSO2 access token
     */
    private function revokeWSO2Token($accessToken)
    {
        try {
            // Use consistent environment variables (same as introspection)
            $clientId = env('IS_CLIENT_ID');
            $clientSecret = env('IS_CLIENT_SECRET');
            $wso2BaseUrl = env('IS_URL');
            
            if (!$clientId || !$clientSecret || !$wso2BaseUrl) {
                \Log::warning('WSO2 configuration missing for token revocation');
                return false;
            }
            
            $revokeUrl = rtrim($wso2BaseUrl, '/') . '/oauth2/revoke';
            
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->withOptions(['verify' => false])
                ->post($revokeUrl, [
                    'token' => $accessToken,
                    'token_type_hint' => 'access_token'
                ]);

            if ($response->successful()) {
                \Log::info('WSO2 access token revoked successfully', [
                    'url' => $revokeUrl
                ]);
                return true;
            } else {
                \Log::warning('Failed to revoke WSO2 access token', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'url' => $revokeUrl
                ]);
                return false;
            }

        } catch (\Exception $e) {
            \Log::error('WSO2 token revocation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark invitation token as used by revoking it from WSO2 (simplified approach)
     */
    private function markInvitationTokenAsUsed($token)
    {
        try {
            \Log::info('Revoking invitation token from WSO2', [
                'token' => substr($token, 0, 8) . '...'
            ]);
            
            // Simply revoke the token from WSO2 - no cache management needed
            $revocationSuccess = $this->revokeWSO2Token($token);
            
            if ($revocationSuccess) {
                \Log::info('Invitation token successfully revoked from WSO2', [
                    'token' => substr($token, 0, 8) . '...'
                ]);
            } else {
                \Log::warning('Failed to revoke invitation token from WSO2', [
                    'token' => substr($token, 0, 8) . '...'
                ]);
            }
            
            return $revocationSuccess;

        } catch (\Exception $e) {
            \Log::error('Error revoking invitation token: ' . $e->getMessage(), [
                'token' => substr($token, 0, 8) . '...'
            ]);
            return false;
        }
    }

    /**
     * Introspect WSO2 token to check if it's still active
     */
    private function introspectWSO2Token($accessToken)
    {
        try {
            // Use env variables directly since they're more reliable
            $wso2BaseUrl = env('IS_URL');
            $clientId = env('WSO2_ADMIN_USERNAME'); // Use IS_CLIENT_ID instead of SCIM_CLIENT_ID
            $clientSecret = env('WSO2_ADMIN_PASSWORD'); // Use IS_CLIENT_SECRET
            
            if (!$wso2BaseUrl || !$clientId || !$clientSecret) {
                \Log::warning('WSO2 configuration missing for introspection', [
                    'has_base_url' => !empty($wso2BaseUrl),
                    'has_client_id' => !empty($clientId),
                    'has_client_secret' => !empty($clientSecret)
                ]);
                
                return [
                    'success' => false,
                    'active' => false,
                    'error' => 'WSO2 configuration missing'
                ];
            }
            
            $introspectUrl = rtrim($wso2BaseUrl, '/') . '/oauth2/introspect';
            
            \Log::info('Attempting WSO2 token introspection', [
                'url' => $introspectUrl,
                'client_id' => $clientId
            ]);
            
            $response = Http::withOptions(['verify' => false])
                ->withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($introspectUrl, [
                    'token' => $accessToken
                ]);

            if ($response->successful()) {
                $introspectionData = $response->json();
                
                \Log::info('WSO2 token introspection result', [
                    'active' => $introspectionData['active'] ?? false,
                    'client_id' => $introspectionData['client_id'] ?? null,
                    'exp' => $introspectionData['exp'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'active' => $introspectionData['active'] ?? false,
                    'data' => $introspectionData
                ];
            }

            \Log::warning('WSO2 token introspection failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $introspectUrl
            ]);

            return [
                'success' => false,
                'active' => false,
                'error' => 'Introspection request failed: ' . $response->status()
            ];

        } catch (\Exception $e) {
            \Log::error('WSO2 token introspection error: ' . $e->getMessage());
            return [
                'success' => false,
                'active' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Revoke invitation tokens after successful registration
     */
    private function revokeInvitationTokens($tokenReference)
    {
        try {
            $tokenCacheKey = "invitation_token_ref_{$tokenReference}";
            $cachedTokenData = Cache::get($tokenCacheKey);
            
            if ($cachedTokenData) {
                // Revoke the WSO2 access token
                $this->revokeWSO2Token($cachedTokenData['wso2_token']);
                
                // Remove the token reference from cache
                Cache::forget($tokenCacheKey);
                
                \Log::info('Invitation token reference and WSO2 token revoked', [
                    'token_reference' => substr($tokenReference, 0, 8) . '...'
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error revoking invitation tokens: ' . $e->getMessage(), [
                'token_reference' => substr($tokenReference, 0, 8) . '...'
            ]);
        }
    }

    /**
     * Create customer user account using organization_switch flow
     */
    private function createCustomerUserAccount($request, $organizationId)
    {
        try {
            // Build user payload with superadmin role as requested
            $payload = [
                'emails' => [
                    [
                        'primary' => true,
                        'value' => $request->input('email')
                    ]
                ],
                'name' => [
                    'givenName' => $request->input('first_name'),
                    'familyName' => $request->input('last_name', 'Customer')
                ],
                'password' => $request->input('password'),
                'userName' => 'PRIMARY/' . $request->input('username'), // Use PRIMARY prefix for organization users
                'schemas' => [
                    'urn:ietf:params:scim:schemas:core:2.0:User',
                    'urn:scim:wso2:schema',
                    'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User'
                ],
                'active' => true,
                'phoneNumbers' => [
                    [
                        'type' => 'work',
                        'value' => $request->input('phone')
                    ]
                ],
                'urn:scim:wso2:schema' => [
                    'company' => $request->input('company_name', ''),
                    'customer_status' => 'active',
                    'onboarding_status' => 'completed'
                ],
                'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User' => [
                    'accountDisabled' => false
                ]
            ];

            \Log::info('Creating customer user account', [
                'organization_id' => $organizationId,
                'username' => $request->input('username'),
                'email' => $request->input('email')
            ]);

            // Use WSO2OrganizationService to create user
            $result = $this->wso2Service->createUserInOrganization($payload, $organizationId);

            if ($result['success']) {
                // Assign superadmin role to the user
                $this->assignSuperAdminRole($result['user_id'], $organizationId);
                
                return [
                    'success' => true,
                    'user_id' => $result['user_id'],
                    'data' => $result['data'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message']
                ];
            }

        } catch (\Exception $e) {
            \Log::error('Customer user creation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create user account: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Assign superadmin role to the created user
     */
    private function assignSuperAdminRole($userId, $organizationId)
    {
        try {
            // This will be implemented based on your WSO2 role assignment pattern
            // For now, log the assignment - you may need to implement role assignment via WSO2 API
            \Log::info('Assigning superadmin role to customer user', [
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'role' => 'superadmin'
            ]);

            // TODO: Implement WSO2 role assignment API call here
            // Example:
            // $this->wso2Service->assignRoleToUser($userId, 'superadmin', $organizationId);

        } catch (\Exception $e) {
            \Log::error('Role assignment error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'organization_id' => $organizationId
            ]);
        }
    }

    /**
     * Mark registration as complete in organization attributes
     */
    private function markRegistrationComplete($organizationId, $userId)
    {
        try {
            $patchData = [
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/customer_status",
                    "value" => "active"
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/onboarding_status",
                    "value" => "completed"
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/registration_completed_date",
                    "value" => now()->toISOString()
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/primary_user_id",
                    "value" => $userId
                ]
            ];

            Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $organizationId, $patchData);

        } catch (\Exception $e) {
            \Log::error('Mark registration complete error: ' . $e->getMessage());
        }
    }

    /**
     * Send welcome email to the newly registered customer
     */
    private function sendWelcomeEmail($email, $firstName)
    {
        try {
            $emailData = [
                'user_name' => $firstName,
                'login_url' => route('home'),
                'support_email' => 'support@loccana.com'
            ];

            \Mail::send('emails.crm.welcome-user', $emailData, function($message) use ($email, $firstName) {
                $message->to($email, $firstName)
                        ->subject('Welcome to Loccana - Your Account is Ready!');
            });

            \Log::info('Welcome email sent', ['email' => $email]);

        } catch (\Exception $e) {
            \Log::error('Welcome email error: ' . $e->getMessage());
        }
    }
}
