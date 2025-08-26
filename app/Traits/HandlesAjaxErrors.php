<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait HandlesAjaxErrors
{
	/**
	 * Universal AJAX error response for forbidden (403)
	 */
	protected function ajaxForbidden(string $message = 'Operation is not permitted. You do not have permissions to make this request.') : JsonResponse
	{
		return response()->json([
			'success' => false,
			'error' => $message,
			'code' => 403,
			'timestamp' => now()->toISOString()
		], 403);
	}

	/**
	 * Universal AJAX error response for any error
	 */
	protected function ajaxError(string $message = 'Operation failed', int $code = 400, $errors = null) : JsonResponse
	{
		$response = [
			'success' => false,
			'error' => $message,
			'code' => $code,
			'timestamp' => now()->toISOString()
		];
		if ($errors !== null) {
			$response['errors'] = $errors;
		}
		return response()->json($response, $code);
	}
}
