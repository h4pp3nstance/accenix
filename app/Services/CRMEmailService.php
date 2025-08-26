<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CRMEmailService
{
    /**
     * Send organization conversion notification
     */
    public function sendConversionNotification($organization, $users = [])
    {
        try {
            $data = [
                'organization' => $organization,
                'users' => $users,
                'conversion_date' => now()->format('M d, Y H:i'),
                'activated_users_count' => count($users)
            ];

            // Send to admin email
            $adminEmail = env('CRM_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS'));
            
            Mail::send('emails.crm.organization-converted', $data, function ($message) use ($organization, $adminEmail) {
                $message->to($adminEmail)
                        ->subject("Organization Converted: {$organization['name']}");
            });

            Log::info('Organization conversion notification sent', [
                'org_id' => $organization['id'],
                'org_name' => $organization['name'],
                'admin_email' => $adminEmail
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send conversion notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome email to newly activated users
     */
    public function sendWelcomeEmail($user, $organization)
    {
        try {
            $userEmail = $user['emails'][0]['value'] ?? null;
            if (!$userEmail) {
                Log::warning('No email address found for user', ['user_id' => $user['id']]);
                return false;
            }

            $data = [
                'user' => $user,
                'organization' => $organization,
                'login_url' => env('APP_URL') . '/login',
                'support_email' => env('CRM_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS'))
            ];

            Mail::send('emails.crm.welcome-user', $data, function ($message) use ($userEmail, $user, $organization) {
                $userName = ($user['name']['givenName'] ?? '') . ' ' . ($user['name']['familyName'] ?? '');
                $message->to($userEmail, $userName)
                        ->subject("Welcome to {$organization['name']} - Account Activated");
            });

            Log::info('Welcome email sent to user', [
                'user_id' => $user['id'],
                'user_email' => $userEmail,
                'org_name' => $organization['name']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send weekly CRM report to administrators
     */
    public function sendWeeklyCRMReport($analyticsData)
    {
        try {
            $adminEmail = env('CRM_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS'));
            
            $data = [
                'statistics' => $analyticsData['statistics'] ?? [],
                'funnel' => $analyticsData['funnel'] ?? [],
                'insights' => $analyticsData['insights'] ?? [],
                'report_period' => now()->subWeek()->format('M d') . ' - ' . now()->format('M d, Y')
            ];

            Mail::send('emails.crm.weekly-report', $data, function ($message) use ($adminEmail) {
                $message->to($adminEmail)
                        ->subject('Weekly CRM Report - ' . now()->format('M d, Y'));
            });

            Log::info('Weekly CRM report sent', [
                'admin_email' => $adminEmail,
                'report_date' => now()->format('Y-m-d')
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send weekly CRM report: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send lead registration notification
     */
    public function sendLeadRegistrationNotification($leadData)
    {
        try {
            $adminEmail = env('CRM_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS'));
            
            $data = [
                'lead' => $leadData,
                'registration_date' => now()->format('M d, Y H:i'),
                'crm_url' => env('APP_URL') . '/admin/crm/leads'
            ];

            Mail::send('emails.crm.new-lead-notification', $data, function ($message) use ($adminEmail, $leadData) {
                $message->to($adminEmail)
                        ->subject("New Lead Registration: {$leadData['company_name']}");
            });

            Log::info('Lead registration notification sent', [
                'company_name' => $leadData['company_name'],
                'admin_email' => $adminEmail
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send lead registration notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send bulk conversion notification
     */
    public function sendBulkConversionNotification($convertedOrganizations)
    {
        try {
            $adminEmail = env('CRM_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS'));
            
            $data = [
                'organizations' => $convertedOrganizations,
                'total_converted' => count($convertedOrganizations),
                'conversion_date' => now()->format('M d, Y H:i')
            ];

            Mail::send('emails.crm.bulk-conversion-notification', $data, function ($message) use ($adminEmail, $convertedOrganizations) {
                $count = count($convertedOrganizations);
                $message->to($adminEmail)
                        ->subject("Bulk Conversion Complete: {$count} Organizations Converted");
            });

            Log::info('Bulk conversion notification sent', [
                'converted_count' => count($convertedOrganizations),
                'admin_email' => $adminEmail
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send bulk conversion notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send monthly performance report
     */
    public function sendMonthlyPerformanceReport($performanceData)
    {
        try {
            $adminEmail = env('CRM_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS'));
            
            $data = [
                'performance' => $performanceData,
                'report_month' => now()->subMonth()->format('F Y'),
                'generated_date' => now()->format('M d, Y')
            ];

            Mail::send('emails.crm.monthly-performance-report', $data, function ($message) use ($adminEmail) {
                $month = now()->subMonth()->format('F Y');
                $message->to($adminEmail)
                        ->subject("Monthly CRM Performance Report - {$month}");
            });

            Log::info('Monthly performance report sent', [
                'admin_email' => $adminEmail,
                'report_month' => now()->subMonth()->format('Y-m')
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send monthly performance report: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration()
    {
        try {
            $testEmail = env('CRM_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS'));
            
            Mail::send('emails.crm.test-email', [], function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('CRM Email Configuration Test');
            });

            Log::info('Test email sent successfully', [
                'test_email' => $testEmail
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Email configuration test failed: ' . $e->getMessage());
            return false;
        }
    }
}
