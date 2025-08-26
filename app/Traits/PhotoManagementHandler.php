<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\ScimHelper;

trait PhotoManagementHandler
{
    /**
     * Handle profile photo upload or removal
     */
    protected function handleProfilePhotoUpdate(Request $request): array
    {
        Log::info('Profile photo update started', [
            'has_file' => $request->hasFile('profile_picture'),
            'remove_photo' => $request->input('remove_photo'),
            'request_data' => $request->all()
        ]);
        
        $userInfo = Session::get('user_info');
        $oldPhoto = $userInfo['Photo'] ?? null;
        $removePhoto = $request->input('remove_photo') == '1';
        
        Log::info('Photo update context', [
            'username' => $userInfo['username'] ?? 'unknown',
            'old_photo' => $oldPhoto,
            'remove_photo' => $removePhoto
        ]);
        
        try {
            if ($removePhoto) {
                $photoUrl = null;
                $this->deleteOldPhoto($oldPhoto);
            } elseif ($request->hasFile('profile_picture')) {
                $photoUrl = $this->processPhotoUpload($request->file('profile_picture'), $oldPhoto);
            } else {
                $photoUrl = $oldPhoto;
            }
            
            Log::info('Photo processing result', [
                'new_photo_url' => $photoUrl
            ]);
            
            // Update session
            $this->updateUserPhotoInSession($userInfo, $photoUrl);
            
            // Update SCIM if photo changed and user exists
            if ($photoUrl && !empty($userInfo['username'])) {
                $this->updatePhotoInScim($userInfo['username'], $photoUrl);
            }
            
            return [
                'success' => true,
                'Photo' => $photoUrl,
                'initial' => $this->getUserInitial($userInfo['username'] ?? ''),
            ];
            
        } catch (\Exception $e) {
            Log::error('Error updating profile photo', [
                'username' => $userInfo['username'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Process photo file upload
     */
    private function processPhotoUpload($file, ?string $oldPhoto): string
    {
        Log::info('Processing photo upload', [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'old_photo' => $oldPhoto
        ]);
        
        // Additional security validation
        $this->validateFileType($file);
        
        // Generate secure filename
        $filename = $this->generateSecureFilename($file);
        
        Log::info('Generated filename', ['filename' => $filename]);
        
        // Store file
        $path = $file->storeAs('img-users', $filename, 'public');
        
        Log::info('File stored successfully', [
            'path' => $path,
            'full_url' => '/storage/' . $path
        ]);
        
        // Delete old photo
        $this->deleteOldPhoto($oldPhoto);
        
        return '/storage/' . $path;
    }
    
    /**
     * Validate file type for additional security
     */
    private function validateFileType($file): void
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \InvalidArgumentException('Tipe file tidak diizinkan.');
        }
    }
    
    /**
     * Generate secure filename
     */
    private function generateSecureFilename($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        
        return "user_{$timestamp}_{$randomString}.{$extension}";
    }
    
    /**
     * Delete old photo file safely
     */
    private function deleteOldPhoto(?string $oldPhoto): void
    {
        if (!$oldPhoto || $this->isExternalUrl($oldPhoto)) {
            return; // Skip if no photo or external URL
        }
        
        try {
            $relativePath = $this->sanitizePhotoPath($oldPhoto);
            
            if ($this->isPathSafe($relativePath)) {
                $fullPath = storage_path('app/public/' . $relativePath);
                
                if (file_exists($fullPath) && is_file($fullPath)) {
                    unlink($fullPath);
                    Log::info('Old photo deleted successfully', ['path' => $relativePath]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete old photo', [
                'photo' => $oldPhoto,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if URL is external
     */
    private function isExternalUrl(string $url): bool
    {
        return preg_match('/^https?:\/\//', $url);
    }
    
    /**
     * Sanitize photo path for security
     */
    private function sanitizePhotoPath(string $photoPath): string
    {
        $relativePath = ltrim($photoPath, '/');
        
        if (strpos($relativePath, 'storage/') === 0) {
            $relativePath = substr($relativePath, strlen('storage/'));
        }
        
        return $relativePath;
    }
    
    /**
     * Check if path is safe (within img-users folder)
     */
    private function isPathSafe(string $path): bool
    {
        return strpos($path, 'img-users/') === 0 && 
               strpos($path, '..') === false && 
               strpos($path, '/') !== 0;
    }
    
    /**
     * Update user photo in session
     */
    private function updateUserPhotoInSession(array $userInfo, ?string $photoUrl): void
    {
        $userInfo['Photo'] = $photoUrl;
        Session::put('user_info', $userInfo);
    }
    
    /**
     * Update photo in SCIM system
     */
    private function updatePhotoInScim(string $username, string $photoUrl): void
    {
        try {
            $userId = $this->findScimUserId($username);

            if ($userId) {
                $this->patchScimUserPhoto($userId, $photoUrl);
                Log::info('Photo updated in SCIM successfully', [
                    'username' => $username,
                    'user_id' => $userId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update photo in SCIM', [
                'username' => $username,
                'photo_url' => $photoUrl,
                'error' => $e->getMessage()
            ]);
            // Don't throw - photo upload should succeed even if SCIM update fails
        }
    }
    
    /**
     * Find SCIM user ID by username
     */
    private function findScimUserId(string $username): ?string
    {
        $accessToken = Session::get('access_token');
        
        $searchResp = Http::withOptions(['verify' => app()->environment('production')])
            ->withHeaders([
                'accept' => 'application/scim+json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->get(env('USER_URL') . '?filter=userName eq "' . $username . '"');
            
        if ($searchResp->successful()) {
            $searchData = json_decode($searchResp->body());
            if (!empty($searchData->Resources) && isset($searchData->Resources[0]->id)) {
                return $searchData->Resources[0]->id;
            }
        }
        
        return null;
    }
    
    /**
     * PATCH user photo in SCIM
     */
    private function patchScimUserPhoto(string $userId, string $photoUrl): void
    {
        $accessToken = Session::get('access_token');
        $patchPayload = [
            'schemas' => ["urn:ietf:params:scim:api:messages:2.0:PatchOp"],
            'Operations' => [
                [
                    'op' => 'replace',
                    'path' => 'photos',
                    'value' => [['value' => $photoUrl]],
                ]
            ],
        ];
        
        Http::withOptions(['verify' => app()->environment('production')])
            ->withHeaders([
                'accept' => 'application/scim+json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->patch(env('ENDUSER_URL') , $patchPayload);
    }
    
    /**
     * Get user initial from username
     */
    private function getUserInitial(string $username): string
    {
        return strtoupper(substr($username, 0, 1));
    }
}