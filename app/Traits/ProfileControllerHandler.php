<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ProfilePhotoRequest;

/**
 * ProfileControllerHandler - Manages profile controller operations
 * 
 * This trait handles:
 * - Profile photo update controller logic
 * - Profile page display controller logic
 * - Integration with existing profile management traits
 */
use App\Traits\PhotoManagementHandler;

trait ProfileControllerHandler
{
    /**
     * Handle profile photo upload or removal from profile page
     *
     * @param ProfilePhotoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleProfilePhotoUpdateRequest(ProfilePhotoRequest $request)
    {
        try {
            $result = $this->handleProfilePhotoUpdate($request);
            
            Log::info('Profile photo updated successfully', [
                'user' => Session::get('user_info.username', 'unknown'),
                'action' => $request->action,
                'photo_url' => $result['photo_url'] ?? null
            ]);
            
            return $this->successResponse($result, 'Foto profil berhasil diperbarui.');
            
        } catch (\InvalidArgumentException $e) {
            Log::warning('Profile photo update validation failed', [
                'user' => Session::get('user_info.username', 'unknown'),
                'error' => $e->getMessage()
            ]);
            
            return $this->validationErrorResponse($e->getMessage());
            
        } catch (\Exception $e) {
            Log::error('Profile photo update failed', [
                'error' => $e->getMessage(),
                'user' => Session::get('user_info.username', 'unknown'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Terjadi kesalahan saat mengupload foto.', 500);
        }
    }

    /**
     * Show profile page with user detail data from SCIM
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    protected function handleShowProfilePage()
    {
        // Validate user access and token
        $accessCheck = $this->validateUserAccess();
        if ($accessCheck !== null) {
            return $accessCheck;
        }
        
        // Prepare profile data
        $profileData = $this->prepareProfilePageData();
        $routesConfig = $this->getRoutesConfig();
        
        Log::info('Profile page accessed', [
            'username' => $profileData['user']['username'] ?? 'unknown',
            'has_user_detail' => !empty($profileData['userDetail'])
        ]);
        
        return view($routesConfig['profile_view'], $profileData);
    }

    /**
     * Validate profile photo update request
     *
     * @param ProfilePhotoRequest $request
     * @return array
     */
    protected function validateProfilePhotoRequest(ProfilePhotoRequest $request): array
    {
        $action = $request->input('action', 'upload');
        $hasFile = $request->hasFile('profile_picture');
        $removePhoto = $request->input('remove_photo') == '1';

        return [
            'action' => $action,
            'has_file' => $hasFile,
            'remove_photo' => $removePhoto,
            'is_valid' => ($action === 'upload' && $hasFile) || ($action === 'remove' && $removePhoto)
        ];
    }

    /**
     * Log profile operation
     *
     * @param string $operation
     * @param bool $success
     * @param array $context
     */
    protected function logProfileOperation(string $operation, bool $success, array $context = []): void
    {
        $username = Session::get('user_info.username', 'unknown');
        
        $logData = array_merge([
            'username' => $username,
            'operation' => $operation,
            'success' => $success
        ], $context);

        if ($success) {
            Log::info('Profile operation successful', $logData);
        } else {
            Log::error('Profile operation failed', $logData);
        }
    }

    /**
     * Prepare common profile response data
     *
     * @param array $result
     * @param string $message
     * @return array
     */
    protected function prepareProfileResponse(array $result, string $message): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $result,
            'timestamp' => now()->toISOString()
        ];
    }
}
