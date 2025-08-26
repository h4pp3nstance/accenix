<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WSO2OrganizationService;
use App\Services\CRMAnalyticsService;
use Exception; // Import Exception for clarity

class CRMDashboardController extends Controller
{
    private $wso2Service;
    private $analyticsService;

    /**
     * Constructor to inject WSO2OrganizationService and CRMAnalyticsService.
     *
     * @param WSO2OrganizationService $wso2Service
     * @param CRMAnalyticsService $analyticsService
     */
    public function __construct(WSO2OrganizationService $wso2Service, CRMAnalyticsService $analyticsService)
    {
        $this->wso2Service = $wso2Service;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display CRM dashboard with comprehensive metrics and analytics.
     * This method fetches various data points from WSO2 and CRM analytics services
     * to populate the dashboard view. It includes error handling to ensure
     * a graceful fallback if data fetching fails.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Get comprehensive dashboard statistics from the analytics service.
            $dashboardStats = $this->analyticsService->getDashboardStatistics();
            
            // Get conversion funnel data, illustrating lead progression.
            $conversionFunnel = $this->analyticsService->getLeadConversionFunnel();
            
            // Get organization performance metrics, providing insights into overall health.
            $performanceMetrics = $this->analyticsService->getOrganizationPerformance();
            
            // Get automated insights and recommendations for CRM improvements.
            $insights = $this->analyticsService->getAutomatedInsights();
            
            // Fetch recent activities from organizations (leads and activated).
            $recentActivities = $this->getRecentActivities();
            
            // Get pending user activations that require administrative action.
            $pendingActivations = $this->getPendingActivations();
            
            // Format lead-related statistics for compatibility with the dashboard view.
            // Default to 0 if data is not available from dashboardStats.
            $leadStats = [
                'total_leads' => $dashboardStats['lead_organizations'] ?? 0,
                'converted_leads' => $dashboardStats['active_organizations'] ?? 0,
                'new_leads' => $dashboardStats['lead_organizations'] ?? 0, // Assuming new leads are part of lead organizations
                'active_leads' => $dashboardStats['lead_organizations'] ?? 0, // Active leads might be defined as lead organizations still in the funnel
                'this_month' => $dashboardStats['lead_organizations'] ?? 0 // Placeholder, integrate actual 'this month' data from analytics if available
            ];
            
            // Format organization-related statistics for the dashboard view.
            // Default to 0 if data is not available from dashboardStats.
            $orgStats = [
                'active_organizations' => $dashboardStats['active_organizations'] ?? 0,
                'pending_organizations' => $dashboardStats['lead_organizations'] ?? 0 // Assuming lead organizations are considered pending
            ];
            
            // Return the dashboard view with all collected and formatted data.
            return view('admin.crm.dashboard.index', compact(
                'dashboardStats',
                'conversionFunnel', 
                'performanceMetrics',
                'insights',
                'recentActivities',
                'pendingActivations',
                'leadStats', 
                'orgStats' 
            ));
            
        } catch (Exception $e) { 
            // Log any exceptions that occur during data fetching for debugging.
            \Log::error('CRM Dashboard Data Error: ' . $e->getMessage());
            
            // Define default/empty data for all dashboard components in case of an error.
            // This ensures the dashboard can still render, albeit with no data.
            $dashboardStats = [
                'total_organizations' => 0,
                'lead_organizations' => 0,
                'active_organizations' => 0,
                'active_users' => 0,
                'conversion_rate' => 0 
            ];

            $conversionFunnel = [];
            $performanceMetrics = [];
            $insights = [];
            $recentActivities = [];
            $pendingActivations = [];
            
            // Re-format stats with default values for the error view.
            $leadStats = [
                'total_leads' => $dashboardStats['lead_organizations'] ?? 0,
                'converted_leads' => $dashboardStats['active_organizations'] ?? 0,
                'new_leads' => $dashboardStats['lead_organizations'] ?? 0,
                'active_leads' => $dashboardStats['lead_organizations'] ?? 0,
                'this_month' => $dashboardStats['lead_organizations'] ?? 0
            ];

            $orgStats = [
                'active_organizations' => $dashboardStats['active_organizations'] ?? 0,
                'pending_organizations' => $dashboardStats['lead_organizations'] ?? 0
            ];

            // Return the dashboard view with default data after an error.
            return view('admin.crm.dashboard.index', compact(
                'dashboardStats',
                'conversionFunnel', 
                'performanceMetrics',
                'insights',
                'recentActivities',
                'pendingActivations',
                'leadStats',
                'orgStats'
            ));
        }
    }

    /**
     * Helper method to get recent activities for the dashboard.
     * It fetches a limited number of recent organizations and formats them
     * as activities (lead registrations or organization activations).
     *
     * @return array An array of recent activity records.
     */
    private function getRecentActivities(): array
    {
        try {
            // Get recent organizations (both lead and active) with a limit.
            $organizations = $this->wso2Service->getOrganizations(['limit' => 20]);
            $allOrgs = $organizations['organizations'] ?? [];
            
            // Sort organizations by creation date in descending order (most recent first).
            usort($allOrgs, function($a, $b) {
                // Convert 'created' timestamp/date string to a comparable format.
                $timeA = isset($a['created']) ? strtotime($a['created']) : 0;
                $timeB = isset($b['created']) ? strtotime($b['created']) : 0;
                return $timeB <=> $timeA; // Spaceship operator for comparison.
            });
            
            $activities = [];
            // Take only the top 10 most recent organizations for activities.
            $recentOrgs = array_slice($allOrgs, 0, 10); 
            
            // Iterate through recent organizations to create activity records.
            foreach ($recentOrgs as $org) {
                // Determine if the organization is a lead based on its name prefix.
                $isLead = str_starts_with($org['name'], 'lead-');
                // Set activity type and display name accordingly.
                $activityType = $isLead ? 'Lead Registration' : 'Organization Activated';
                $displayName = $isLead ? str_replace('lead-', '', $org['name']) : $org['name'];
                
                // Add the activity details to the activities array.
                $activities[] = [
                    'type' => $activityType,
                    'title' => $activityType,
                    'description' => "Organization '{$displayName}' " . ($isLead ? 'registered as lead' : 'is now active'),
                    'organization' => $displayName,
                    'date' => $org['created'] ?? null, // Use the organization's creation date.
                    'status' => $org['status'] ?? ($isLead ? 'LEAD' : 'ACTIVE'), // Default status.
                ];
            }
            return $activities;

        } catch (Exception $e) {
            // Log errors and return an empty array if recent activities cannot be fetched.
            \Log::error('CRM Dashboard Recent Activities Error: ' . $e->getMessage());
            return []; 
        }
    }

    /**
     * Handles the activation of a user within a specific organization.
     * This method receives organization and user IDs, along with update data,
     * and attempts to update the user status via the WSO2 service.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request containing org_id, user_id, and update_data.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating success or failure of the activation.
     */
    public function activateUser(Request $request)
    {
        // Validate the incoming request data.
        $request->validate([
            'org_id' => 'required|string',
            'user_id' => 'required|string',
            'update_data' => 'required|array', // Expecting an array for update data
        ]);

        $orgId = $request->input('org_id');
        $userId = $request->input('user_id');
        $updateData = $request->input('update_data');

        try {
            // Attempt to update the organization user status via the WSO2 service.
            $success = $this->wso2Service->updateOrganizationUser($orgId, $userId, $updateData);

            if ($success) {
                // Log successful activation.
                \Log::info('User activated successfully via CRM dashboard', [
                    'user_id' => $userId,
                    'org_id' => $orgId
                ]);

                // Return a success JSON response.
                return response()->json([
                    'success' => true,
                    'message' => 'User activated successfully'
                ]);
            }

            // Return a failure JSON response if WSO2 service indicates failure.
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate user'
            ], 500);

        } catch (Exception $e) {
            // Log any exceptions that occur during user activation.
            \Log::error('Dashboard User Activation Error: ' . $e->getMessage());
            
            // Return an error JSON response with the exception message.
            return response()->json([
                'success' => false,
                'message' => 'Error activating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quick metrics for dashboard widgets.
     * This method provides a condensed set of key performance indicators
     * often displayed prominently on a dashboard.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing quick metrics.
     */
    public function getQuickMetrics()
    {
        try {
            // Fetch dashboard statistics from the analytics service.
            $stats = $this->analyticsService->getDashboardStatistics();
            
            // Return a success JSON response with the extracted quick metrics.
            // Default to 0 if any statistic is missing.
            return response()->json([
                'success' => true,
                'data' => [
                    'total_organizations' => $stats['total_organizations'] ?? 0,
                    'lead_organizations' => $stats['lead_organizations'] ?? 0,
                    'active_organizations' => $stats['active_organizations'] ?? 0,
                    'conversion_rate' => $stats['conversion_rate'] ?? 0,
                    'total_users' => $stats['total_users'] ?? 0,
                    'active_users' => $stats['active_users'] ?? 0
                ]
            ]);

        } catch (Exception $e) {
            // Log errors and return a failure JSON response.
            \Log::error('Quick Metrics Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to load metrics'
            ], 500);
        }
    }

    /**
     * Refreshes dashboard data by clearing relevant cache entries.
     * This forces the system to refetch data from source services on next request.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating refresh status.
     */
    public function refreshData()
    {
        try {
            // Clear specific cache keys related to CRM dashboard data.
            \Cache::forget('crm_dashboard_stats');
            \Cache::forget('crm_conversion_funnel');
            \Cache::forget('crm_org_performance_30_days');

            // Return a success JSON response.
            return response()->json([
                'success' => true,
                'message' => 'Dashboard data refreshed successfully'
            ]);

        } catch (Exception $e) {
            // Log errors and return a failure JSON response.
            \Log::error('Refresh Data Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh data'
            ], 500);
        }
    }

    /**
     * Helper method to get pending user activations.
     * This method is optimized for performance by caching its result and
     * limiting the number of organizations to check for pending users.
     * It assumes 'PENDING' status for users requiring activation.
     *
     * @return array An array of pending user activation records.
     */
    private function getPendingActivations(): array
    {
        try {
            // Cache the result for 10 minutes (600 seconds) to prevent excessive API calls.
            return \Cache::remember('pending_activations', 600, function () {
                \Log::info('Fetching pending activations (optimized)');
                
                // Fetch a limited number of recent organizations to optimize API calls.
                // The limit (e.g., 20) should be tuned based on typical data volume.
                $organizations = $this->wso2Service->getOrganizations(['limit' => 20]); 
                $pendingActivations = [];

                if (isset($organizations['organizations'])) {
                    \Log::info('Processing ' . count($organizations['organizations']) . ' organizations for pending activations (limited)');
                    
                    // Filter for active organizations, assuming lead organizations don't have
                    // users pending activation in this specific context.
                    $activeOrgs = array_filter($organizations['organizations'], function($org) {
                        return !str_starts_with($org['name'], 'lead-');
                    });
                    
                    $processedCount = 0;
                    // Iterate through a maximum of 5 active organizations to check for pending users.
                    // This further optimizes performance for dashboards.
                    foreach ($activeOrgs as $org) {
                        if ($processedCount >= 5) { 
                            break;
                        }
                        
                        try {
                            // Fetch users for the current organization.
                            // Assume getOrganizationUsers can filter by user status, or we filter client-side.
                            $usersData = $this->wso2Service->getOrganizationUsers($org['id']);
                            $orgUsers = $usersData['users'] ?? [];

                            // Iterate through users to find those with 'PENDING' status.
                            foreach ($orgUsers as $user) {
                                // The 'status' field and 'PENDING' value might need adjustment
                                // based on the actual WSO2 user status conventions.
                                if (isset($user['status']) && $user['status'] === 'PENDING') { 
                                    $pendingActivations[] = [
                                        'user_id' => $user['id'],
                                        'user_name' => $user['username'] ?? ($user['name'] ?? 'N/A'), // Provide fallback for name
                                        'organization_id' => $org['id'],
                                        'organization_name' => $org['name'],
                                        'status' => $user['status'],
                                        'email' => $user['emails'][0] ?? null, // Assuming the first email is the primary one
                                    ];
                                }
                            }
                            $processedCount++;
                        } catch (Exception $e) {
                            // Log a warning if users for a specific organization cannot be fetched,
                            // but continue processing other organizations.
                            \Log::warning("Could not fetch users for organization {$org['id']}: " . $e->getMessage());
                        }
                    }
                }
                return $pendingActivations;
            });
        } catch (Exception $e) {
            // Log errors and return an empty array if pending activations cannot be retrieved.
            \Log::error('Pending Activations Error: ' . $e->getMessage());
            return []; 
        }
    }
}
