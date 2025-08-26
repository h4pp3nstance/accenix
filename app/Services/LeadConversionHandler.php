<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadConversionHandler
{
    /**
     * Convert lead to customer with complete workflow
     * 
     * @param string $leadId
     * @param array $organization
     * @param array $attributeMap
     * @return array
     */
    public function handle($leadId, $organization, $attributeMap)
    {
        try {
            // Step 1: Validate lead data
            $validation = $this->validateLeadForConversion($organization, $attributeMap);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validation['errors'])
                ];
            }

            // Step 2: Update lead status and attributes
            $statusUpdateResult = $this->updateLeadStatus($leadId, $attributeMap);
            if (!$statusUpdateResult['success']) {
                return $statusUpdateResult;
            }

            // Step 3: Transform organization name (remove "lead-" prefix)
            $nameTransformResult = $this->transformOrganizationName($leadId, $organization);
            if (!$nameTransformResult['success']) {
                // Rollback status update if name transformation fails
                $this->rollbackLeadStatusUpdate($leadId, $attributeMap);
                return $nameTransformResult;
            }

            // Step 4: Create user account automatically
            $userCreationHandler = new UserCreationHandler();
            $userCreationResult = $userCreationHandler->createUserAccount($organization, $attributeMap);
            if (!$userCreationResult['success']) {
                // Rollback previous changes if user creation fails
                $this->rollbackLeadStatusUpdate($leadId, $attributeMap);
                $this->rollbackOrganizationNameTransform($leadId, $organization);
                return $userCreationResult;
            }

            // Step 5: Send welcome email with credentials
            $emailResult = $this->sendWelcomeEmailWithCredentials($organization, $attributeMap, $userCreationResult);
            if (!$emailResult['success']) {
                // Rollback all changes including user creation if email fails
                $this->rollbackLeadStatusUpdate($leadId, $attributeMap);
                $this->rollbackOrganizationNameTransform($leadId, $organization);
                // Note: We should also consider deleting the created user, but this could be complex
                // For now, we'll log this for manual cleanup
                Log::warning('User created but email failed - manual cleanup required', [
                    'user_id' => $userCreationResult['user_id'] ?? null,
                    'organization_id' => $leadId
                ]);
                return $emailResult;
            }

            // Step 6: Log successful conversion
            $this->logSuccessfulConversion($leadId, $organization, $nameTransformResult, $emailResult, $userCreationResult);

            return [
                'success' => true,
                'message' => 'Lead successfully converted to customer. Welcome email with login credentials has been sent to ' . ($attributeMap['contact_email'] ?? 'customer'),
                'data' => [
                    'organization_id' => $leadId,
                    'old_name' => $organization['name'],
                    'new_name' => $nameTransformResult['new_name'],
                    'user_created' => true,
                    'user_id' => $userCreationResult['user_id'],
                    'username' => $userCreationResult['username'],
                    'email_sent_to' => $emailResult['email_sent_to'] ?? null,
                    'account_ready' => true
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Lead Conversion Handler Error: ' . $e->getMessage(), [
                'lead_id' => $leadId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred during lead conversion: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update lead status to converted
     * 
     * @param string $leadId
     * @param array $attributeMap
     * @return array
     */
    protected function updateLeadStatus($leadId, $attributeMap)
    {
        try {
            $attributesToUpdate = [
                'lead_status' => 'converted',
                'conversion_date' => now()->toISOString(),
                'converted_by' => auth()->user()->name ?? 'System',
                'converted_date' => now()->toISOString(),
                'customer_status' => 'invitation_sent',
                'onboarding_status' => 'awaiting_registration',
                'invitation_sent_date' => now()->toISOString(),
                'approved_by' => session('user_info.username') ?? (auth()->user()->name ?? 'System'),
                'approved_date' => now()->toISOString(),
                'approval_status' => 'approved',
                'assigned_sales_rep' => session('user_info.username') ?? (auth()->user()->name ?? 'System'),
                'last_contact_date' => now()->toISOString(),
                'account_setup_completed' => 'pending_password_change',
                'notes' => $attributeMap['conversion_notes'] ?? ($attributeMap['notes'] ?? ''),
                // Anda bisa menambah attribute lain sesuai kebutuhan
            ];

            $patchData = $this->buildAttributePatchData($attributeMap, $attributesToUpdate);
            
            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $leadId, $patchData);

            if ($response->successful()) {
                Log::info('Lead status updated successfully', ['lead_id' => $leadId]);
                return ['success' => true];
            }

            Log::error('Failed to update lead status', [
                'lead_id' => $leadId,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update lead status'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating lead status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Transform organization name by removing "lead-" prefix
     * 
     * @param string $leadId
     * @param array $organization
     * @return array
     */
    protected function transformOrganizationName($leadId, $organization)
    {
        try {
            // Check if organization has "lead-" prefix
            if (!str_starts_with($organization['name'], 'lead-')) {
                return [
                    'success' => false,
                    'message' => 'Organization is not a lead (no "lead-" prefix found)'
                ];
            }

            // Generate new name from organization name or use custom name from request
            $newName = request('organization_name') 
                ? preg_replace('/\s+/', '', strtolower(trim(request('organization_name'))))
                : str_replace('lead-', '', $organization['name']);
            // Hilangkan underscore, gunakan huruf kecil tanpa spasi
            $newName = str_replace('_', '', $newName);
            
            $newDescription = str_replace(
                'Lead organization', 
                'Active organization', 
                $organization['description'] ?? ''
            );

            // Prepare PATCH operations for WSO2 API - use correct format from curl example
            $patchOperations = [
                [
                    'operation' => 'REPLACE',
                    'path' => '/name',
                    'value' => $newName
                ]
            ];

            Log::info('Organization name transformation payload', [
                'lead_id' => $leadId,
                'old_name' => $organization['name'],
                'new_name' => $newName,
                'payload' => $patchOperations
            ]);

            $response = Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $leadId, $patchOperations);

            if ($response->successful()) {
                Log::info('Organization name transformed successfully', [
                    'lead_id' => $leadId,
                    'old_name' => $organization['name'],
                    'new_name' => $newName
                ]);

                return [
                    'success' => true,
                    'old_name' => $organization['name'],
                    'new_name' => $newName
                ];
            }

            Log::error('Failed to transform organization name', [
                'lead_id' => $leadId,
                'old_name' => $organization['name'],
                'new_name' => $newName,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update organization name in WSO2'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error transforming organization name: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send customer registration invitation email
     * 
     * @param array $organization
     * @param array $attributeMap
     * @return array
     */
    /**
     * Send welcome email with login credentials to customer
     * 
     * @param array $organization
     * @param array $attributeMap
     * @param array $userCreationResult
     * @return array
     */
    protected function sendWelcomeEmailWithCredentials($organization, $attributeMap, $userCreationResult)
    {
        try {
            $contactEmail = $userCreationResult['email'];
            $contactName = $userCreationResult['full_name'];
            $companyName = $attributeMap['company_name_original'] ?? $organization['name'] ?? '';
            $username = $userCreationResult['username'];
            $temporaryPassword = $userCreationResult['temporary_password'];

            // Clean company name (remove lead- prefix if still present)
            $cleanCompanyName = str_starts_with($companyName, 'lead-') 
                ? substr($companyName, 5) 
                : $companyName;

            // Create login URL
            $loginUrl = config('app.url') . '/login';

            // Prepare email data for the template
            $emailData = [
                'fullName' => $contactName,
                'companyName' => $cleanCompanyName,
                'username' => $username,
                'temporaryPassword' => $temporaryPassword,
                'loginUrl' => $loginUrl,
                'contactEmail' => $contactEmail,
                'organizationId' => $organization['id']
            ];

            Log::info('Sending welcome email with credentials', [
                'organization_id' => $organization['id'],
                'email' => $contactEmail,
                'username' => $username
            ]);

            // Send email using Laravel Mail with new account-created template
            Mail::send('emails.crm.account-created', $emailData, function($message) use ($contactEmail, $contactName, $cleanCompanyName) {
                $message->to($contactEmail, $contactName)
                        ->subject('Welcome to ' . config('app.name') . ' - Your Account is Ready!');
            });

            // Update organization with account creation tracking
            $accountTrackingData = [
                'account_created_date' => now()->toISOString(),
                'credentials_sent_to' => $contactEmail,
                'account_setup_method' => 'auto_with_credentials',
                'primary_user_id' => $userCreationResult['user_id']
            ];

            $trackingPatchData = $this->buildAttributePatchData($attributeMap, $accountTrackingData);
            
            Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $organization['id'], $trackingPatchData);

            Log::info('Welcome email with credentials sent successfully', [
                'contact_email' => $contactEmail,
                'contact_name' => $contactName,
                'company_name' => $cleanCompanyName,
                'username' => $username,
                'user_id' => $userCreationResult['user_id']
            ]);

            return [
                'success' => true,
                'email_sent' => true,
                'email_sent_to' => $contactEmail,
                'username_provided' => $username,
                'account_ready' => true
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send welcome email with credentials: ' . $e->getMessage(), [
                'organization_id' => $organization['id'] ?? 'unknown',
                'email' => $userCreationResult['email'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send welcome email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate WSO2 invitation token
     * 
     * @param string $organizationId
     * @return array
     */
    protected function generateWSO2InvitationToken($organizationId)
    {
        try {
            // Create organization-scoped access token for customer registration
            $tokenData = [
                'grant_type' => 'client_credentials',
                'scope' => 'openid profile email SYSTEM'
            ];

            $response = Http::withBasicAuth(env('IS_CLIENT_ID'), env('IS_CLIENT_SECRET'))
                ->asForm()
                ->withOptions(['verify' => false])
                ->post(env('IS_URL') . '/oauth2/token', $tokenData);

            if ($response->successful()) {
                $tokenResponse = $response->json();
                
                return [
                    'success' => true,
                    'access_token' => $tokenResponse['access_token'],
                    'expires_at' => now()->addSeconds($tokenResponse['expires_in'] ?? 3600)->toISOString()
                ];
            }

            Log::error('Failed to generate WSO2 invitation token', [
                'organization_id' => $organizationId,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate WSO2 token'
            ];

        } catch (\Exception $e) {
            Log::error('Error generating WSO2 invitation token', [
                'organization_id' => $organizationId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error generating token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build attribute patch data for WSO2 API
     * Automatically determines whether to ADD or REPLACE based on existing attributes
     * 
     * @param array $existingAttributes
     * @param array $newAttributes
     * @return array
     */
    protected function buildAttributePatchData($existingAttributes, $newAttributes)
    {
        $patchOperations = [];
        
        foreach ($newAttributes as $key => $value) {
            // Check if attribute already exists
            $operation = isset($existingAttributes[$key]) ? 'REPLACE' : 'ADD';
            
            $patchOperations[] = [
                'operation' => $operation,
                'path' => "/attributes/{$key}",
                'value' => $value
            ];
        }
        
        Log::info('Building patch operations for attributes', [
            'existing_keys' => array_keys($existingAttributes),
            'new_keys' => array_keys($newAttributes),
            'operations' => array_map(function($op) {
                return $op['operation'] . ': ' . $op['path'];
            }, $patchOperations)
        ]);
        
        return $patchOperations;
    }

    /**
     * Rollback lead status update
     */
    protected function rollbackLeadStatusUpdate($leadId, $originalAttributeMap)
    {
        try {
            $rollbackData = [
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/lead_status",
                    "value" => $originalAttributeMap['lead_status'] ?? 'qualified'
                ],
                [
                    "operation" => "REPLACE", 
                    "path" => "/attributes/customer_status",
                    "value" => "pending"
                ],
                [
                    "operation" => "REPLACE",
                    "path" => "/attributes/onboarding_status", 
                    "value" => "lead"
                ]
            ];

            Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $leadId, $rollbackData);

            Log::info('Lead status rollback completed', ['lead_id' => $leadId]);

        } catch (\Exception $e) {
            Log::error('Failed to rollback lead status', [
                'lead_id' => $leadId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rollback organization name transformation
     */
    protected function rollbackOrganizationNameTransform($leadId, $originalOrganization)
    {
        try {
            $rollbackOperations = [
                [
                    'operation' => 'REPLACE',
                    'path' => '/name',
                    'value' => $originalOrganization['name']
                ],
                [
                    'operation' => 'REPLACE',
                    'path' => '/description',
                    'value' => $originalOrganization['description'] ?? ''
                ]
            ];

            Http::withBasicAuth(env('WSO2_ADMIN_USERNAME'), env('WSO2_ADMIN_PASSWORD'))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch(env('IS_URL') . '/api/server/v1/organizations/' . $leadId, $rollbackOperations);

            Log::info('Organization name rollback completed', [
                'lead_id' => $leadId,
                'restored_name' => $originalOrganization['name']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to rollback organization name', [
                'lead_id' => $leadId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate lead data before conversion
     */
    protected function validateLeadForConversion($organization, $attributeMap)
    {
        $errors = [];

        if (!str_starts_with($organization['name'], 'lead-')) {
            $errors[] = 'Organization is not a lead';
        }

        if (empty($attributeMap['contact_email'])) {
            $errors[] = 'Contact email is required for conversion';
        }

        if (empty($attributeMap['contact_person'])) {
            $errors[] = 'Contact person name is required for conversion';
        }

        if (!empty($attributeMap['contact_email']) && !filter_var($attributeMap['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Contact email format is invalid';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Log successful conversion details
     */
    protected function logSuccessfulConversion($leadId, $organization, $nameTransformResult, $emailResult, $userCreationResult)
    {
        Log::info('Lead Converted Successfully with Complete Workflow', [
            'lead_id' => $leadId,
            'converted_by' => auth()->user()->name ?? 'System',
            'converted_date' => now()->toISOString(),
            'old_organization_name' => $organization['name'],
            'new_organization_name' => $nameTransformResult['new_name'],
            'user_created' => true,
            'user_id' => $userCreationResult['user_id'],
            'username' => $userCreationResult['username'],
            'email_sent' => $emailResult['email_sent'] ?? false,
            'email_sent_to' => $emailResult['email_sent_to'] ?? null,
            'account_ready' => $emailResult['account_ready'] ?? false
        ]);
    }
}
