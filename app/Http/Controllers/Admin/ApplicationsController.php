<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WSO2ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApplicationsController extends Controller
{
    public function __construct(protected WSO2ApplicationService $wso2)
    {
    // Rely on route-level WSO2 role middleware (BFF pattern).
    $this->middleware('wso2.role:management,sales');
    }

    // Admin UI mount
    public function index()
    {
        return view('admin.applications');
    }

    // API: List applications (proxy)
    public function list(Request $request)
    {
        $token = session('access_token');
        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = $request->only(['limit', 'offset', 'filter']);

        $resp = $this->wso2->listApplications($token, $query);

        // Normalize upstream errors to friendly JSON responses
        $status = $resp->status();
        $contentType = $resp->header('Content-Type', 'application/json');

        if ($status === 403) {
            // Token does not have the required permission on WSO2
            Log::warning('WSO2 returned 403 for listApplications', ['status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Forbidden: WSO2 token lacks required permission'], 403);
        }

        if ($status === 401) {
            Log::warning('WSO2 returned 401 for listApplications', ['status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Unauthorized: WSO2 token invalid or expired'], 401);
        }

        if ($status >= 500) {
            Log::error('WSO2 returned server error for listApplications', ['status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Upstream error contacting WSO2'], 502);
        }

        // Try to normalize upstream JSON into a data array for DataTables-style consumption
        $body = $resp->body();
        $json = json_decode($body, true);

        $items = [];
        if (is_array($json)) {
            // Common WSO2 shapes: { applications: [...] } or { list: [...] } or direct array
            if (isset($json['applications']) && is_array($json['applications'])) {
                $items = $json['applications'];
            } elseif (isset($json['list']) && is_array($json['list'])) {
                $items = $json['list'];
            } elseif (isset($json['data']) && is_array($json['data'])) {
                $items = $json['data'];
            } else {
                // If the top-level is already an array of items
                $allArrayKeysNumeric = count($json) > 0 && array_keys($json) === range(0, count($json) - 1);
                if ($allArrayKeysNumeric) {
                    $items = $json;
                } else {
                    // fallback: try to locate an array inside the payload
                    foreach ($json as $v) {
                        if (is_array($v)) {
                            $items = $v;
                            break;
                        }
                    }
                }
            }
        }

        // Ensure items is an array
        $items = is_array($items) ? $items : [];

        return response()->json(['data' => $items], $status);
    }

    // API: Get application
    public function get(string $id)
    {
        $token = session('access_token');
        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $resp = $this->wso2->getApplication($token, $id);
        $status = $resp->status();
        $contentType = $resp->header('Content-Type', 'application/json');

        if ($status === 403) {
            Log::warning('WSO2 returned 403 for getApplication', ['id' => $id, 'status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Forbidden: WSO2 token lacks required permission'], 403);
        }

        if ($status === 401) {
            Log::warning('WSO2 returned 401 for getApplication', ['id' => $id, 'status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Unauthorized: WSO2 token invalid or expired'], 401);
        }

        if ($status >= 500) {
            Log::error('WSO2 returned server error for getApplication', ['id' => $id, 'status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Upstream error contacting WSO2'], 502);
        }

        return response($resp->body(), $status)->header('Content-Type', $contentType);
    }

    // API: Regenerate OIDC secret (example action)
    public function regenerateSecret(string $id)
    {
        $token = session('access_token');
        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $resp = $this->wso2->regenerateOidcSecret($token, $id);

        $status = $resp->status();
        $contentType = $resp->header('Content-Type', 'application/json');

        Log::info('Regenerate secret response', ['status' => $status]);

        if ($status === 403) {
            Log::warning('WSO2 returned 403 for regenerateOidcSecret', ['id' => $id, 'status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Forbidden: WSO2 token lacks required permission'], 403);
        }

        if ($status === 401) {
            Log::warning('WSO2 returned 401 for regenerateOidcSecret', ['id' => $id, 'status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Unauthorized: WSO2 token invalid or expired'], 401);
        }

        if ($status >= 500) {
            Log::error('WSO2 returned server error for regenerateOidcSecret', ['id' => $id, 'status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Upstream error contacting WSO2'], 502);
        }

        return response($resp->body(), $status)->header('Content-Type', $contentType);
    }

    // API: Import application file (multipart)
    public function import(Request $request)
    {
        $token = session('access_token');
        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');

        $resp = $this->wso2->importApplication($token, $file);

        $status = $resp->status();
        $contentType = $resp->header('Content-Type', 'application/json');

        if ($status === 403) {
            Log::warning('WSO2 returned 403 for importApplication', ['status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Forbidden: WSO2 token lacks required permission'], 403);
        }

        if ($status === 401) {
            Log::warning('WSO2 returned 401 for importApplication', ['status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Unauthorized: WSO2 token invalid or expired'], 401);
        }

        if ($status >= 500) {
            Log::error('WSO2 returned server error for importApplication', ['status' => $status, 'body' => $resp->body()]);
            return response()->json(['message' => 'Upstream error contacting WSO2'], 502);
        }

        return response($resp->body(), $status)->header('Content-Type', $contentType);
    }
}
