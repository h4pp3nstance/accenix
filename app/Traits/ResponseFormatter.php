<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait ResponseFormatter
{
    /**
     * Format successful JSON response
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Format error JSON response
     *
     * @param string $message
     * @param int $status
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Operation failed', int $status = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Format validation error response
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Format redirect response with flash message
     *
     * @param string $route
     * @param string $message
     * @param string $type
     * @return RedirectResponse
     */
    protected function redirectWithMessage(string $route, string $message, string $type = 'success'): RedirectResponse
    {
        // Check if route is a URL or route name
        if (filter_var($route, FILTER_VALIDATE_URL) || str_starts_with($route, '/')) {
            return redirect($route)->with($type, $message);
        }
        
        return redirect()->route($route)->with($type, $message);
    }

    /**
     * Format redirect response with error
     *
     * @param string $route
     * @param string $message
     * @return RedirectResponse
     */
    protected function redirectWithError(string $route, string $message): RedirectResponse
    {
        return $this->redirectWithMessage($route, $message, 'error');
    }
}
