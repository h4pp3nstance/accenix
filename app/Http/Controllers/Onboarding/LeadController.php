<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Mail\NewLeadNotification;
use App\Mail\LeadConfirmationMail;
use App\Events\NotifEvent;

class LeadController extends Controller
{
    /**
     * Generate user-friendly error message for duplicate organization names
     */
    private function getDuplicateOrganizationMessage($companyName)
    {
        return "Organization name '{$companyName}' is already registered. Please try: adding your city or branch name (e.g., '{$companyName} Jakarta'), using your complete legal entity name, or contact our support team if this is genuinely your organization.";
    }

    /**
     * Show lead registration form
     */
    public function showRegistrationForm()
    {
        return view('onboarding.register');
    }

    /**
     * Handle lead registration
     */
    public function register(Request $request)
    {
        // Increase execution time for the entire registration process
        set_time_limit(300); // 5 minutes
        
        \Log::info('Lead registration started', [
            'company' => $request->company_name,
            'email' => $request->contact_email
        ]);
        
        // Validate lead data
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_position' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'company_address' => 'required|string|max:500',
            'business_type' => 'required|string|max:100',
            'other_business_type' => 'required_if:business_type,other|nullable|string|max:255',
            'current_system' => 'nullable|string|max:100',
            'specific_needs' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Determine final business type value
        $finalBusinessType = $request->business_type;
        if ($request->business_type === 'other' && $request->other_business_type) {
            $finalBusinessType = $request->other_business_type;
        }

        $progress = [
            'organization' => null,
            'user' => null,
            'email' => null
        ];

        try {
            // Increase PHP execution time for WSO2 operations
            set_time_limit(120); // 2 minutes
            
            // Quick pre-check for duplicate organization name on WSO2 to provide immediate feedback
            $sanitizedName = $this->sanitizeOrganizationName($request->company_name);
            $leadOrgName = 'lead-' . $sanitizedName;
            try {
                // Use WSO2 filter for exact name match to avoid false positives
                $filter = 'name eq "' . $leadOrgName . '"';
                $checkResponse = Http::retry(2, 200)
                    ->timeout(10)
                    ->withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                    ->withOptions(['verify' => false])
                    ->get(env('IS_URL') . '/api/server/v1/organizations', [
                        'filter' => $filter,
                        'limit' => 1
                    ]);

                // Log response for debugging false-positives
                \Log::info('WSO2 pre-check response', ['status' => $checkResponse->status(), 'body' => $checkResponse->body()]);

                if ($checkResponse->successful()) {
                    $checkData = $checkResponse->json();
                    $exists = false;
                    if (isset($checkData['organizations']) && is_array($checkData['organizations']) && count($checkData['organizations']) > 0) {
                        $exists = true;
                    } elseif (isset($checkData['id'])) {
                        $exists = true;
                    }

                    if ($exists) {
                        $errorMessage = $this->getDuplicateOrganizationMessage($request->company_name);
                        if ($request->expectsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $errorMessage,
                                'is_duplicate' => true
                            ], 409);
                        }
                        return back()->withErrors($errorMessage)->withInput();
                    }
                }
            } catch (\Exception $e) {
                // Pre-check failed (network/timeout); proceed to queue the job and let job handle real errors
                \Log::warning('WSO2 pre-check failed: ' . $e->getMessage());
            }

            // Dispatch lead registration job which will create the organization and send notifications
            \Log::info('Dispatching ProcessLeadRegistrationJob for company', ['company' => $request->company_name]);

            // Prepare payload for processing (same shape as job expects)
            $leadPayload = $request->all();
            $leadPayload['business_type'] = $finalBusinessType;
            $leadPayload['contact_person'] = $request->contact_name;

            // Minimal file-based queue fallback: append JSON line to storage/lead_queue/queue.jsonl
            try {
                $queueDir = storage_path('lead_queue');
                if (!is_dir($queueDir)) {
                    mkdir($queueDir, 0755, true);
                }
                $queueFile = $queueDir . DIRECTORY_SEPARATOR . 'queue.jsonl';

                // Add metadata: uuid and timestamp
                $payloadToStore = $leadPayload;
                if (!isset($payloadToStore['__id'])) {
                    $payloadToStore['__id'] = (string) (Str::uuid());
                }
                $payloadToStore['__queued_at'] = now()->toISOString();

                // Append line atomically using file lock
                $line = json_encode($payloadToStore, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                file_put_contents($queueFile, $line, FILE_APPEND | LOCK_EX);

                \Log::info('Lead appended to file queue', ['queue_file' => $queueFile, 'company' => $request->company_name, 'id' => $payloadToStore['__id']]);

            } catch (\Exception $e) {
                // If file queue fails, fall back to dispatching the job directly
                \Log::warning('File queue append failed, dispatching job synchronously: ' . $e->getMessage());
                \App\Jobs\ProcessLeadRegistrationJob::dispatch($leadPayload);
            }

            // Determine if driver actually queues jobs or runs sync
            $isQueued = config('queue.default') !== 'sync';
            $progress['organization'] = ['success' => true, 'queued' => $isQueued];
            // Mark email notifications as queued (job will dispatch SendLeadNotificationsJob)
            $progress['email'] = ['success' => false, 'queued' => $isQueued];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $isQueued ? 'Registration queued. We will process and notify you shortly.' : 'Registration processed (synchronous). Notifications sent if applicable.',
                    'organization_created' => false,
                    'queued' => $isQueued
                ]);
            }

            // Check if all steps completed successfully (safe access)
            $allCompleted = ($progress['organization']['success'] ?? false) && ($progress['email']['success'] ?? false);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Registration completed',
                    'progress' => $progress,
                    'allCompleted' => $allCompleted
                ]);
            }

            $flashMessage = $isQueued
                ? 'Thank you for your interest! Registration is queued and our team will contact you within 24 hours.'
                : 'Thank you for your interest! Registration processed and notifications have been sent.';

            return redirect()->route('lead.success')->with('success', $flashMessage);

        } catch (\Exception $e) {
            \Log::error('Lead registration error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed. Please try again.',
                    'progress' => $progress ?? []
                ]);
            }
            
            return back()->withErrors('Registration failed. Please try again.')->withInput();
        }
    }

    /**
     * Create organization with PENDING status
     */
    private function createPendingOrganization(Request $request, $finalBusinessType)
    {
        try {
            // For lead organizations, use company_id 0 to indicate inactive status
            $companyId = 0;
            
            // Generate lead organization name with prefix "lead-"
            $sanitizedCompanyName = $this->sanitizeOrganizationName($request->company_name);
            $leadOrganizationName = 'lead-' . $sanitizedCompanyName;

            // Use correct WSO2 Organization API format based on example
            $payload = [
                'name' => $leadOrganizationName,
                'description' => 'Lead organization - ' . $finalBusinessType,
                'type' => 'TENANT',
                'attributes' => [
                    [
                        'key' => 'company_id',
                        'value' => (string)$companyId
                    ],
                    [
                        'key' => 'alias',
                        'value' => $request->company_name ?: 'Unknown Company'
                    ],
                    [
                        'key' => 'address',
                        'value' => $request->company_address ?: 'Address not provided'
                    ],
                    [
                        'key' => 'contact_person',
                        'value' => $request->contact_name ?: 'Contact name not provided'
                    ],
                    [
                        'key' => 'contact_position',
                        'value' => $request->contact_position ?: 'Position not specified'
                    ],
                    [
                        'key' => 'contact_phone',
                        'value' => $request->contact_phone ?: 'Phone not provided'
                    ],
                    [
                        'key' => 'business_type',
                        'value' => $finalBusinessType ?: 'Business type not specified'
                    ],
                    [
                        'key' => 'current_system',
                        'value' => !empty($request->current_system) ? $request->current_system : 'No current system specified'
                    ],
                    [
                        'key' => 'specific_needs',
                        'value' => !empty($request->specific_needs) ? $request->specific_needs : 'No specific requirements mentioned'
                    ],
                    [
                        'key' => 'contact_email',
                        'value' => $request->contact_email ?: 'Email not provided'
                    ],
                    [
                        'key' => 'lead_source',
                        'value' => 'website'
                    ],
                    [
                        'key' => 'lead_status',
                        'value' => 'new'
                    ],
                    [
                        'key' => 'onboarding_status',
                        'value' => 'new'
                    ],
                    [
                        'key' => 'registration_date',
                        'value' => now()->toISOString()
                    ],
                    [
                        'key' => 'last_contact_date',
                        'value' => 'Not contacted yet'
                    ],
                    [
                        'key' => 'conversion_date',
                        'value' => 'Not converted yet'
                    ],
                    [
                        'key' => 'converted_by',
                        'value' => 'Not converted yet'
                    ],
                    [
                        'key' => 'converted_date',
                        'value' => 'Not converted yet'
                    ],
                    [
                        'key' => 'customer_status',
                        'value' => 'pending'
                    ],
                    [
                        'key' => 'assigned_sales_rep',
                        'value' => 'Not assigned yet'
                    ],
                    [
                        'key' => 'estimated_value',
                        'value' => 'Not estimated yet'
                    ],
                    [
                        'key' => 'priority_level',
                        'value' => 'normal'
                    ],
                    [
                        'key' => 'approved_by',
                        'value' => 'Pending approval'
                    ],
                    [
                        'key' => 'approved_date',
                        'value' => 'Not approved yet'
                    ],
                    [
                        'key' => 'approval_status',
                        'value' => 'pending'
                    ],
                    [
                        'key' => 'rejection_reason',
                        'value' => 'N/A'
                    ],
                    [
                        'key' => 'notes',
                        'value' => 'No notes yet'
                    ]
                ]
            ];

            // Debug: Log request details
            \Log::info('WSO2 Organization Creation Request:', [
                'url' => env('IS_URL') . '/api/server/v1/organizations',
                'company_name' => $request->company_name,
                'lead_org_name' => $leadOrganizationName,
                'payload_size' => strlen(json_encode($payload))
            ]);

            \Log::info('Sending HTTP request to WSO2...');
            
            // Use Basic Auth admin:admin due to WSO2 bug
            $response = Http::timeout(60) // Increase timeout to 60 seconds
                ->withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->withOptions(['verify' => false])
                ->post(env('IS_URL') . '/api/server/v1/organizations', $payload);
            
            \Log::info('HTTP request completed');
            
            $statusCode = $response->status();
            $responseBody = $response->body();
            
            // Debug: Log response with more details
            \Log::info('WSO2 Organization Response:', [
                'status' => $statusCode,
                'successful' => $response->successful(),
                'body_length' => strlen($responseBody),
                'content_type' => $response->header('Content-Type'),
                'body' => $responseBody
            ]);
            

            if ($response->successful()) {
                $orgData = $response->json();
                return [
                    'success' => true,
                    'organization_id' => $orgData['id'],
                    'company_id' => $companyId
                ];
            } else {
                $statusCode = $response->status();
                $responseBody = $response->json();
                
                \Log::error('WSO2 Organization creation failed', [
                    'status' => $statusCode,
                    'body' => $responseBody,
                    'company_name' => $leadOrganizationName
                ]);
                
                // Handle specific error cases
                $errorMessage = 'Failed to create organization in WSO2';
                
                if ($statusCode === 409 || $statusCode === 400) {
                    // Check for specific WSO2 error codes
                    if (isset($responseBody['code'])) {
                        // ORG-60076: Organization name already taken
                        if ($responseBody['code'] === 'ORG-60076') {
                            $errorMessage = $this->getDuplicateOrganizationMessage($request->company_name);
                        }
                        // ORG-60016: Missing required attribute values  
                        elseif ($responseBody['code'] === 'ORG-60016') {
                            $errorMessage = "Required information is missing. Please ensure all required fields are filled out correctly and try again.";
                        }
                        // Other WSO2 error codes
                        else {
                            $errorMessage = isset($responseBody['description']) ? $responseBody['description'] : 
                                           (isset($responseBody['message']) ? $responseBody['message'] : 'Organization creation failed');
                        }
                    }
                    // Fallback to checking description text for duplicate detection
                    elseif (isset($responseBody['description']) && 
                        (strpos(strtolower($responseBody['description']), 'organization name') !== false &&
                         (strpos(strtolower($responseBody['description']), 'taken') !== false ||
                          strpos(strtolower($responseBody['description']), 'already exists') !== false ||
                          strpos(strtolower($responseBody['description']), 'duplicate') !== false))) {
                        
                        $errorMessage = $this->getDuplicateOrganizationMessage($request->company_name);
                    }
                    // Check for attribute/field validation errors
                    elseif (isset($responseBody['description']) && 
                        strpos(strtolower($responseBody['description']), 'attribute value') !== false) {
                        
                        $errorMessage = "Required information is missing. Please ensure all required fields are filled out correctly and try again.";
                    }
                    // Generic error handling
                    elseif (isset($responseBody['message'])) {
                        $errorMessage = $responseBody['message'];
                    } elseif (isset($responseBody['description'])) {
                        $errorMessage = $responseBody['description'];
                    }
                } elseif ($statusCode === 401) {
                    $errorMessage = 'Authentication failed with WSO2 system. Please try again later.';
                } elseif ($statusCode === 403) {
                    $errorMessage = 'Permission denied to create organization. Please contact support.';
                } elseif ($statusCode === 500) {
                    $errorMessage = 'WSO2 server error. Please try again later or contact support.';
                } elseif (isset($responseBody['message'])) {
                    $errorMessage = $responseBody['message'];
                } elseif (isset($responseBody['description'])) {
                    $errorMessage = $responseBody['description'];
                }
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'status_code' => $statusCode,
                    'is_duplicate' => ($statusCode === 409 || $statusCode === 400)
                ];
            }

        } catch (\Exception $e) {
            \Log::error('Organization creation exception', [
                'error' => $e->getMessage(),
                'company_name' => $leadOrganizationName,
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = $e->getMessage();
            
            // Check for specific error types
            if (strpos($e->getMessage(), 'execution time') !== false || 
                strpos($e->getMessage(), 'timeout') !== false) {
                return [
                    'success' => false,
                    'message' => 'Request timeout - organization may have been created. Please check WSO2 manually.',
                    'timeout' => true
                ];
            }
            
            // Check for connection errors
            if (strpos(strtolower($e->getMessage()), 'connection') !== false ||
                strpos(strtolower($e->getMessage()), 'curl') !== false) {
                $errorMessage = 'Unable to connect to the registration system. Please try again later or contact support.';
            }
            
            // Check for duplicate/conflict errors in exception message
            if (strpos(strtolower($e->getMessage()), 'org-60076') !== false ||
                (strpos(strtolower($e->getMessage()), 'organization name') !== false &&
                 (strpos(strtolower($e->getMessage()), 'taken') !== false ||
                  strpos(strtolower($e->getMessage()), 'already exists') !== false ||
                  strpos(strtolower($e->getMessage()), 'duplicate') !== false))) {
                
                $errorMessage = $this->getDuplicateOrganizationMessage($request->company_name);
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'is_duplicate' => true
                ];
            }
            
            // Check for missing attribute errors
            if (strpos(strtolower($e->getMessage()), 'org-60016') !== false ||
                strpos(strtolower($e->getMessage()), 'attribute value') !== false) {
                
                $errorMessage = "Required information is missing. Please ensure all required fields are filled out correctly and try again.";
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'validation_error' => true
                ];
            }
            
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        }
    }

    /**
     * Create disabled user for the lead
     * DEPRECATED: Not needed for organization-only lead approach
     */
    /*
    private function createDisabledUser(Request $request, $companyId, $organizationId)
    {
        try {
            $username = $this->generateUsername($request->contact_email);
            $randomPassword = $this->generateCompliantPassword(); // Generate WSO2-compliant password

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
            $payload = [
                'schemas' => [
                    'urn:ietf:params:scim:schemas:core:2.0:User',
                    'urn:scim:wso2:schema',
                    'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User'
                ],
                'userName' => $username,
                'password' => $randomPassword,
                'active' => false, // User disabled initially
                'emails' => [
                    [
                        'type' => 'work',
                        'value' => $request->contact_email,
                        'primary' => true
                    ]
                ],
                'name' => [
                    'givenName' => $request->contact_person,
                    'familyName' => 'Lead'
                ],
                'phoneNumbers' => [
                    [
                        'type' => 'work',
                        'value' => $request->contact_phone
                    ]
                ],
                'urn:scim:wso2:schema' => [
                    'company' => (string)$companyId,
                    'business_needs' => $request->business_needs,
                    'lead_status' => 'new',
                    'onboarding_status' => 'new'
                ],
                'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User' => [
                    'accountDisabled' => true
                ]
            ];
            
            \Log::info('WSO2 User Creation Payload:', [
                'url' => env('USER_URL'),
                'username' => $username,
                'company_id' => $companyId,
                'organization_id' => $organizationId
            ]);
            
            \Log::info('Sending user creation request to WSO2...');
            
            $response = Http::timeout(60)
                ->withToken($orgToken) // Use organization-scoped token
                ->acceptJson()
                ->withOptions(['verify' => false])
                ->post(env('USER_URL'), $payload);

            \Log::info('User creation request completed');
            
            \Log::info('WSO2 User Creation Response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $userData = $response->json();
                return [
                    'success' => true,
                    'user_id' => $userData['id'] ?? null
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

    /**
     * Send all notifications (marketing + confirmation) and track progress
     */
    private function sendNotifications($leadData)
    {
        $results = [
            'marketing' => false,
            'confirmation' => false
        ];

        try {
            // Send marketing notification
            $this->sendMarketingNotification($leadData);
            
            // Trigger real-time notification event for marketing team
            event(new NotifEvent([
                'type' => 'new_lead',
                'title' => 'New Lead Registration',
                'message' => 'New lead registered: ' . ($leadData['company_name'] ?? 'Unknown Company'),
                'data' => [
                    'company_name' => $leadData['company_name'] ?? 'Not provided',
                    'contact_name' => $leadData['contact_person'] ?? 'Not provided',
                    'contact_email' => $leadData['contact_email'] ?? 'Not provided',
                    'business_type' => $leadData['business_type'] ?? 'Not specified',
                    'registration_time' => now()->format('Y-m-d H:i:s')
                ],
                'target_role' => 'marketing',
                'created_at' => now()->toISOString()
            ]));
            
            $results['marketing'] = true;
        } catch (\Exception $e) {
            \Log::error('Marketing notification failed: ' . $e->getMessage());
        }

        try {
            // Send lead confirmation
            $this->sendLeadConfirmation($leadData['contact_email'], $leadData['contact_person']);
            $results['confirmation'] = true;
        } catch (\Exception $e) {
            \Log::error('Lead confirmation failed: ' . $e->getMessage());
        }

        return [
            'success' => $results['marketing'] || $results['confirmation'], // Success if at least one email sent
            'details' => $results,
            'message' => $results['marketing'] && $results['confirmation'] 
                ? 'All notifications sent successfully'
                : 'Some notifications failed'
        ];
    }

    /**
     * Send notification to marketing team
     */
    private function sendMarketingNotification($leadData)
    {
        try {
            $whatsappLink = "https://wa.me/" . str_replace(['+', '-', ' ', '(', ')'], '', $leadData['contact_phone']);
            
            $mailData = array_merge($leadData, [
                'whatsapp_link' => $whatsappLink,
                'registration_time' => now()->format('Y-m-d H:i:s')
            ]);

            // Get marketing team email - use MAIL_USERNAME as it's the marketing email
            $marketingEmail = env('MAIL_USERNAME', 'iqbal@swamedia.co.id');
            
            // Log the email attempt
            \Log::info('Sending marketing notification', [
                'marketing_email' => $marketingEmail,
                'lead_company' => $leadData['company_name'],
                'lead_contact' => $leadData['contact_email']
            ]);

            // Send to the marketing team email address
            Mail::to($marketingEmail)->send(
                new NewLeadNotification($mailData)
            );

            \Log::info('Marketing notification sent successfully', [
                'to' => $marketingEmail,
                'lead_company' => $leadData['company_name']
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send marketing notification: ' . $e->getMessage(), [
                'lead_data' => $leadData,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to be caught by parent method
        }
    }

    /**
     * Send confirmation email to lead
     */
    private function sendLeadConfirmation($email, $contactPerson)
    {
        try {
            Mail::to($email)->send(
                new LeadConfirmationMail($contactPerson)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send lead confirmation: ' . $e->getMessage());
            // Don't fail the registration if email fails
        }
    }

    /**
     * Generate unique company ID
     */
    private function generateCompanyId()
    {
        // Simple auto-increment logic - you might want to use database for this
        // For now, use timestamp + random for uniqueness
        return (int) (time() . rand(10, 99)) % 999999;
    }

    /**
     * Generate username from email  
     * DEPRECATED: Not needed for organization-only approach
     */
    /*
    private function generateUsername($email)
    {
        $baseUsername = explode('@', $email)[0];
        $username = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
        
        // Add lead prefix to identify leads in the system
        return 'lead-' . strtolower($username . rand(100, 999));
    }
    */

    /**
     * Sanitize organization name for WSO2
     */
    private function sanitizeOrganizationName($name)
    {
        // Remove special characters and spaces, replace with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $sanitized = preg_replace('/\s+/', '_', trim($sanitized));
        return strtolower($sanitized);
    }

    /**
     * Generate WSO2-compliant password
     * Requirements: 8-30 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
     */
    private function generateCompliantPassword()
    {
        // Define character sets
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        // Ensure at least one character from each required set
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)]; // 1 uppercase
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)]; // 1 lowercase
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];     // 1 number
        $password .= $special[random_int(0, strlen($special) - 1)];     // 1 special
        
        // Fill the rest with random characters from all sets (total length: 12 characters)
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < 12; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password to randomize the position of required characters
        return str_shuffle($password);
    }

    /**
     * Get initial access token using client credentials
     */
    private function getInitialAccessToken()
    {
        try {
            // Return cached token if available
            if (Cache::has('is_system_token')) {
                return Cache::get('is_system_token');
            }

            $clientId = env('IS_CLIENT_ID');
            $clientSecret = env('IS_CLIENT_SECRET');

            // Retry transient failures a few times
            $response = Http::retry(3, 200)
                ->timeout(60)
                ->asForm()
                ->withOptions(['verify' => false]) // Keep for staging; consider enabling in production
                ->post(env('IS_TOKEN_URL'), [
                    'grant_type' => 'client_credentials',
                    'scope' => 'SYSTEM',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                \Log::info('Initial access token obtained successfully');

                $token = $tokenData['access_token'] ?? null;
                // Use expires_in if provided to set cache TTL, minus small buffer
                $expiresIn = isset($tokenData['expires_in']) ? max(60, (int)$tokenData['expires_in'] - 60) : 3300; // default 55 minutes

                if ($token) {
                    Cache::put('is_system_token', $token, now()->addSeconds($expiresIn));
                }

                return $token;
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
     * Switch to organization context using organization_switch grant
     */
    private function switchToOrganization($initialToken, $organizationId)
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
     * Show success page
     */
    public function success()
    {
        return view('onboarding.success');
    }

    /**
     * Schedule background tasks for user creation and email notifications
     * DEPRECATED: Not needed for organization-only approach
     */
    /*
    private function scheduleBackgroundTasks($request, $finalBusinessType, $orgResponse)
    {
        // Extract serializable data from request
        $requestData = [
            'company_name' => $request->company_name,
            'contact_person' => $request->contact_name, // Use contact_name from form
            'contact_position' => $request->contact_position,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'company_address' => $request->company_address,
            'business_type' => $finalBusinessType,
            'current_system' => $request->current_system,
            'specific_needs' => !empty($request->specific_needs) ? $request->specific_needs : 'No specific requirements mentioned',
        ];

        // Call background endpoint asynchronously (fire and forget)
        $this->callBackgroundEndpointAsync($requestData, $orgResponse);
    }

    /**
     * Make async HTTP call to background processing endpoint
     */
    private function callBackgroundEndpointAsync($requestData, $orgResponse)
    {
        try {
            // Prepare payload for background processing
            $payload = [
                'request_data' => $requestData,
                'org_response' => $orgResponse,
                'timestamp' => now()->toISOString()
            ];

            // Make async HTTP call (fire and forget with very short timeout)
            Http::timeout(1)
                ->retry(0) // No retries for fire-and-forget
                ->post(url('/internal/process-lead-background'), $payload);
                
            \Log::info('Background task triggered successfully');
                
        } catch (\Exception $e) {
            // Log error but don't block main process
            \Log::warning('Failed to trigger background process: ' . $e->getMessage());
        }
    }

    /**
     * Background processing endpoint
     */
    public function processLeadBackground(Request $request)
    {
        try {
            // Increase execution time for background processing
            set_time_limit(300);
            
            $requestData = $request->input('request_data');
            $orgResponse = $request->input('org_response');
            
            \Log::info('Starting background user creation...', [
                'org_id' => $orgResponse['organization_id']
            ]);
            
            // Create disabled user
            $userResponse = $this->createDisabledUser(
                (object)$requestData, 
                $orgResponse['company_id'], 
                $orgResponse['organization_id']
            );
            \Log::info('Background user creation completed', ['success' => $userResponse['success']]);
            
            // Send notifications
            \Log::info('Starting background email notifications...');
            $emailResponse = $this->sendNotifications($requestData, $userResponse['user_id'] ?? null);
            \Log::info('Background email notifications completed', ['success' => $emailResponse['success']]);
            
            \Log::info('All background tasks completed successfully');
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            \Log::error('Background task failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
