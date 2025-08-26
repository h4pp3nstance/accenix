<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Onboarding\LeadController;
use App\Http\Controllers\Auth\NativeLoginController;
use App\Http\Controllers\Auth\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [LeadController::class, 'showRegistrationForm'])->name('lead.register');
Route::post('/register', [LeadController::class, 'register'])->name('lead.submit');
Route::get('/register/success', [LeadController::class, 'success'])->name('lead.success');

Route::get('/native-login', [NativeLoginController::class, 'showLoginForm'])->name('native.login.form');
Route::post('/native-login', [NativeLoginController::class, 'login'])->name('native.login.submit');
Route::post('/native-logout', [NativeLoginController::class, 'logout'])->name('native.logout');
Route::prefix('/')->name('oauth.')->group(function () {
    Route::get('redirect', [AuthController::class, 'redirectToIdentityServer'])->name('redirect');
    Route::get('callback', [AuthController::class, 'handleCallback'])->name('callback');
    Route::get('logout', [NativeLoginController::class, 'logout'])->name('logout');
});

Route::prefix('/')->name('oauth.')->group(function () {
    Route::get('redirect', [AuthController::class, 'redirectToIdentityServer'])->name('redirect');
    Route::get('callback', [AuthController::class, 'handleCallback'])->name('callback');
    Route::get('logout', [NativeLoginController::class, 'logout'])->name('logout');
});

Route::middleware('wso2.role:superadmin,sales')->group(function () {
    // Admin console and system settings
    Route::prefix('/admin')->name('admin.')->middleware('wso2.role:sales')->group(function () {
        // Admin Dashboard (main dashboard redirect to CRM)
        Route::get('/dashboard', function () {
        return redirect()->route('admin.crm.dashboard');
        })->name('dashboard');
        
        // ==========================================
        // CRM MANAGEMENT
        // ==========================================
        Route::prefix('/crm')->name('crm.')->group(function () {
        // CRM Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Admin\CRM\CRMDashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/dashboard/data', [App\Http\Controllers\Admin\CRM\CRMDashboardController::class, 'getData'])
            ->name('dashboard.data');
        Route::get('/dashboard/metrics', [App\Http\Controllers\Admin\CRM\CRMDashboardController::class, 'getQuickMetrics'])
            ->name('dashboard.metrics');
        Route::post('/dashboard/refresh', [App\Http\Controllers\Admin\CRM\CRMDashboardController::class, 'refreshData'])
            ->name('dashboard.refresh');
        Route::post('/dashboard/activate-user', [App\Http\Controllers\Admin\CRM\CRMDashboardController::class, 'activateUser'])
            ->name('dashboard.activate.user');
        
        // Lead Management
        Route::prefix('/leads')->name('leads.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'index'])
            ->name('index');
            Route::get('/data', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'getLeadsData'])
            ->name('data');
            Route::get('/export', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'export'])
            ->name('export');
            Route::post('/bulk-activate', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'bulkActivate'])
            ->name('bulk.activate');
            Route::get('/{id}', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'show'])
            ->name('show');
            Route::post('/{id}/status', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'updateStatus'])
            ->name('update.status');
            Route::post('/{id}/activate', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'activate'])
            ->name('activate');
            Route::post('/{id}/convert', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'convertLead'])
            ->name('convert');
            Route::post('/{id}/followup', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'scheduleFollowup'])
            ->name('schedule.followup');
            Route::post('/{id}/update-status', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'updateLeadStatus'])
            ->name('update.status.full');
            Route::post('/{id}/revoke-invitation', [App\Http\Controllers\Admin\CRM\LeadManagementController::class, 'revokeInvitationToken'])
            ->name('revoke.invitation');
        });
        
        // Organization Management
        Route::prefix('/organizations')->name('organizations.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\CRM\OrganizationManagementController::class, 'index'])
            ->name('index');
            Route::get('/data', [App\Http\Controllers\Admin\CRM\OrganizationManagementController::class, 'getOrganizationsData'])
            ->name('data');
            Route::get('/{id}', [App\Http\Controllers\Admin\CRM\OrganizationManagementController::class, 'show'])
            ->name('show');
            Route::get('/{id}/convert', [App\Http\Controllers\Admin\CRM\OrganizationManagementController::class, 'convert'])
            ->name('convert');
            Route::post('/{id}/convert', [App\Http\Controllers\Admin\CRM\OrganizationManagementController::class, 'processConvert'])
            ->name('process-convert');
            Route::post('/{id}/activate-users', [App\Http\Controllers\Admin\CRM\OrganizationManagementController::class, 'activateOrganizationUsers'])
            ->name('activate-users');
            Route::patch('/{id}', [App\Http\Controllers\Admin\CRM\OrganizationManagementController::class, 'update'])
            ->name('update');
        });
        });
    });
});

Route::get('/validate-token', function() {
    if (!session()->has('access_token')) {
        return response()->json(['valid' => false, 'message' => 'No token found'], 401);
    }
    
    try {
        $accessToken = session()->get('access_token');
        $username = session()->get('user_info.username', 'unknown');
        $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
        
        // Use WSO2 userinfo endpoint like WSO2 console does
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
            'User-Agent' => 'Laravel-App/1.0'
        ])
        ->withOptions([
            'verify' => false,
            'timeout' => 3,  // Very short timeout like WSO2 console
            'connect_timeout' => 2
        ])
        ->get($baseUrl . '/oauth2/userinfo');

        if ($response->successful()) {
            // Token masih valid - return minimal response untuk speed
            return response()->json([
                'valid' => true, 
                'user' => $username
            ]);
        } else {
            // Token invalid, clear session and log
            \Illuminate\Support\Facades\Log::warning('Token validation failed - invalid token', [
                'user' => $username,
                'ip' => request()->ip(),
                'status_code' => $response->status()
            ]);
            
            session()->flush();
            return response()->json([
                'valid' => false, 
                'message' => 'Token invalid',
                'redirect' => '/'
            ], 401);
        }
    } catch (\Exception $e) {
        // For any connection issues, assume session is invalid for security
        \Illuminate\Support\Facades\Log::error('Token validation error - clearing session', [
            'user' => session()->get('user_info.username', 'unknown'),
            'ip' => request()->ip(),
            'error' => $e->getMessage()
        ]);
        
        session()->flush();
        return response()->json([
            'valid' => false, 
            'message' => 'Validation error',
            'redirect' => '/'
        ], 401);
    }
})->name('validate.token');