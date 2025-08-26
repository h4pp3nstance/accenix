<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuthLogger
{
    /**
     * Log aktivitas autentikasi
     *
     * @param string $activity Jenis aktivitas (login, logout, dll)
     * @param string|null $user Username atau identifier pengguna
     * @param string|null $status Status aktivitas (success, failed)
     * @param string|null $message Pesan tambahan
     * @param Request|null $request Request untuk mendapatkan IP dan user agent
     * @return void
     */
    public static function log(string $activity, ?string $user = null, ?string $status = 'success', ?string $message = null, ?Request $request = null)
    {
        $request = $request ?? request();
        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'activity' => $activity,
            'user' => $user,
            'status' => $status,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'message' => $message
        ];
        
        // Log ke channel auth
        Log::channel('auth')->info(json_encode($logData));
        
        // Jika status failed, log juga sebagai warning
        if ($status === 'failed') {
            Log::channel('auth')->warning(json_encode($logData));
        }
    }
    
    /**
     * Log aktivitas login
     */
    public static function logLogin(string $user, bool $success = true, ?string $message = null, ?Request $request = null)
    {
        $status = $success ? 'success' : 'failed';
        self::log('login', $user, $status, $message, $request);
    }
    
    /**
     * Log aktivitas logout
     */
    public static function logLogout(string $user, ?string $message = null, ?Request $request = null)
    {
        self::log('logout', $user, 'success', $message, $request);
    }
    
    /**
     * Log aktivitas akses tidak sah
     */
    public static function logUnauthorizedAccess(string $user, string $resource, ?string $message = null, ?Request $request = null)
    {
        self::log('unauthorized_access', $user, 'failed', "Resource: $resource. $message", $request);
    }
    
    /**
     * Log aktivitas token refresh
     */
    public static function logTokenRefresh(string $user, bool $success = true, ?string $message = null, ?Request $request = null)
    {
        $status = $success ? 'success' : 'failed';
        self::log('token_refresh', $user, $status, $message, $request);
    }
}
