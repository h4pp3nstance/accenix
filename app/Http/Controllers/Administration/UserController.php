<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
// use function App\Helpers\buildUserPatchOperations;
// Refactored: use ScimHelper::buildUserPatchOperations instead.
use App\Helpers\ScimHelper;
use App\Traits\HandlesAjaxErrors;

class UserController extends Controller
{
    use HandlesAjaxErrors;
    public function ajax(Request $request)
    {
        try {
            $length = $request->input('length', 10);
            $start = $request->input('start', 0);
            $apiResponse = getUserWithToken(env('USER_URL'));
            if ($apiResponse->status() === 403) {
                return $this->ajaxForbidden();
            }
            if ($apiResponse->successful()) {
                $data = $apiResponse->json();
                return response()->json([
                    'draw' => $request->input('draw'),
                    'recordsTotal' => $data['totalResults'],
                    'recordsFiltered' => count($data['Resources'] ?? []),
                    'data' => $data['Resources'] ?? [],
                ]);
            }
            return $this->ajaxError($apiResponse->json()['message'] ?? 'Unknown error', $apiResponse->status());
        } catch (\Exception $e) {
            return $this->ajaxError($e->getMessage(), 500);
        }
    }

    public function index(Request $request)
    {
        $permissions = [
            'create' => hasPermission('internal_org_user_mgt_create'),
            'read' => hasPermission('internal_org_user_mgt_view'),
            'update' => hasPermission('internal_org_user_mgt_update'),
            'delete' => hasPermission('internal_org_user_mgt_delete'),
            'detail' => hasPermission('internal_org_user_mgt_view'),
        ];

        return view('administration.user.index', compact('permissions'));
    }
    public function create()
    {
        // Fetch roles and regions for the form
        $rolesResponse = getRolesApi();
        $roles = $rolesResponse->successful() ? $rolesResponse->json() : [];
        // Defensive: flatten if roles are nested under 'Resources' or similar
        if (isset($roles['Resources'])) {
            $roles = $roles['Resources'];
        }
        // Defensive: filter out non-arrays/objects
        $roles = array_filter($roles, function($role) {
            return is_array($role) || is_object($role);
        });
        $regions = [];
        // Example: $regions = Region::all();
        return view('administration.user.add', compact('roles', 'regions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Ambil data utama dari form
        $username = $request->input('username');
        $password = $request->input('password');
        $first_name = $request->input('first_name');
        $family_name = $request->input('family_name', '');
        $email = $request->input('email');
        $company = session('user_info.company'); // Get company from session instead of form
        $country = $request->input('country', '');
        $preferredChannel = 'EMAIL';
        $roles = normalizeRoles($request->input('roles', []));

        $phoneNumbers = ScimHelper::buildPhoneNumbers($request->all());
        $addressesArr = ScimHelper::buildAddresses($request->all());

        // Handle image upload & resize (Intervention Image v3.x)
        $photoUrl = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'user_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $manager = new \Intervention\Image\ImageManager(\Intervention\Image\Drivers\Gd\Driver::class);
            $img = $manager->read($image->getRealPath())
                ->cover(256, 256)
                ->toJpeg(80); // 80% quality, use toPng() for PNG
            $path = 'img-users/' . $filename;
            \Storage::disk('public')->put($path, $img);
            $photoUrl = '/storage/' . $path;
        }

        // Tambahkan role everyone jika belum ada
        $rolesList = getRolesApi();
        $rolesArr = $rolesList->successful() ? $rolesList->json() : [];
        if (isset($rolesArr['Resources'])) {
            $rolesArr = $rolesArr['Resources'];
        }
        $everyoneRole = collect($rolesArr)->first(function($r) {
            return strtolower($r['displayName'] ?? $r['name']) === 'everyone';
        });
        if ($everyoneRole && !in_array($everyoneRole['id'], $roles)) {
            $roles[] = $everyoneRole['id'];
        }

        // SCIM2 Bulk Payload
        $bulkId = 'user1';
        $operations = [];
        $operations[] = [
            'method' => 'POST',
            'path' => '/Users',
            'bulkId' => $bulkId,
            'data' => [
                'schemas' => [
                    'urn:ietf:params:scim:schemas:core:2.0:User',
                    'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User',
                    'urn:scim:wso2:schema'
                ],
                'userName' => $username,
                'password' => $password,
                'name' => [
                    'givenName' => $first_name,
                    'familyName' => $family_name,
                ],
                'emails' => [ [ 'value' => $email ] ],
                'phoneNumbers' => $phoneNumbers,
                'addresses' => $addressesArr,
                'photos' => $photoUrl ? [ [ 'value' => $photoUrl, 'type' => 'photo' ] ] : [],
                'urn:scim:wso2:schema' => [
                    'company' => $company
                ],
                'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User' => [
                    'country' => is_array($country) ? ($country[0] ?? '') : $country,
                    'preferredChannel' => $preferredChannel
                ]
            ]
        ];

        // PATCH ke setiap role
        foreach ($roles as $roleId) {
            $operations[] = [
                'method' => 'PATCH',
                'path' => "/Roles/$roleId",
                'data' => [
                    'schemas' => ["urn:ietf:params:scim:api:messages:2.0:PatchOp"],
                    'Operations' => [
                        [
                            'op' => 'add',
                            'path' => 'users',
                            'value' => [
                                [
                                    'display' => $username,
                                    'value' => "bulkId:$bulkId"
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $bulkPayload = [
            'schemas' => ["urn:ietf:params:scim:api:messages:2.0:BatchRequest"],
            'Operations' => $operations
        ];

        // Kirim request ke /scim2/Bulk
        $access_token = Session::get('access_token');
        $bulkResponse = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
            ->withHeaders([
                'accept' => 'application/scim+json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ])
            ->post(env('BULK_URL'), $bulkPayload);

        if ($bulkResponse->status() === 403) {
            return back()->withErrors('Operation is not permitted. You do not have permissions to make this request.');
        }
        if (!$bulkResponse->successful()) {
            return back()->withErrors('Failed to create user: ' . $bulkResponse->body());
        }
        // dd($bulkResponse->json());
        return redirect()->route('administration.user.index')->with('success', 'User berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Using token-based authentication
            $apiResponse = ScimHelper::getUserDetailWithToken(env('USER_URL'), $id);
            $data = json_decode($apiResponse->body());
            return view('administration.user.ajax.detail', compact('data'));
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $rolesResponse = getRolesApi();
            $userResponse = ScimHelper::getUserDetailWithToken(env('USER_URL'), $id);

            if ($rolesResponse->successful() && $userResponse->successful()) {
                $roles = $rolesResponse->json();
                $data = json_decode($userResponse->body());
                // Pass user_id explicitly for the view
                return view('administration.user.edit', [
                    'data' => $data,
                    'roles' => $roles,
                    'user_id' => $id,
                ]);
            } else {
                $errors = [];
                if (!$rolesResponse->successful()) $errors[] = $rolesResponse->json()['message'] ?? 'Failed to fetch roles';
                if (!$userResponse->successful()) $errors[] = $userResponse->json()['message'] ?? 'Failed to fetch user';
                return back()->withErrors($errors);
            }
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $first_name = $request->input('first_name');
        $family_name = $request->input('family_name', '');
        $roles = normalizeRoles($request->input('roles', []));

        $phoneNumbers = ScimHelper::buildPhoneNumbers($request->all());
        $addressesArr = ScimHelper::buildAddresses($request->all());

        // Get user detail
        $userDetail = ScimHelper::getUserDetailWithToken(env('USER_URL'), $id);
        if (!$userDetail->successful()) {
            return back()->withErrors($userDetail->json()['message'] ?? 'Failed to fetch user');
        }
        $userData = json_decode($userDetail->body());
        $username = $userData->userName ?? null;

        // Get roles from API (for validation or current roles)
        $rolesResponse = getRolesApi();
        if (!$rolesResponse->successful()) {
            return back()->withErrors($rolesResponse->json()['message'] ?? 'Failed to fetch roles');
        }

        // Get current roles from user data
        $currentRoleIds = extractRoleIds($userData->roles ?? []);
        $newRoles = array_diff($roles, $currentRoleIds);
        $removedRoles = array_diff($currentRoleIds, $roles);

        // Get access token for SCIM2 (use getTokenISApi, not getTokenApi)
        $access_token = Session::get('access_token');
        if (!$access_token) {
            return back()->withErrors('Failed to obtain SCIM2 access token');
        }

        // Build SCIM2 Bulk PATCH payload
        $operations = [];
        $operations[] = $this->patchUserOperation($id, $first_name, $family_name, $phoneNumbers, $addressesArr);
        foreach ($newRoles as $roleId) {
            $operations[] = $this->patchRoleAddOperation($roleId, $username, $id);
        }
        foreach ($removedRoles as $roleId) {
            $operations[] = $this->patchRoleRemoveOperation($roleId, $id);
        }
        $bulkPayload = [
            'schemas' => ["urn:ietf:params:scim:api:messages:2.0:BatchRequest"],
            'Operations' => $operations,
        ];
        // Send PATCH request to /scim2/Bulk
        $bulkResponse = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
            ->withHeaders([
                'accept' => 'application/scim+json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ])
            ->post(env('BULK_URL'), $bulkPayload);

        if ($bulkResponse->status() === 403) {
            return back()->withErrors('Operation is not permitted. You do not have permissions to make this request.');
        }
        if (!$bulkResponse->successful()) {
            return back()->withErrors('Failed to update user: ' . $bulkResponse->body());
        }

        return redirect()->route('administration.user.index')->with('success', 'User updated successfully!');
    }

    /**
     * Build PATCH operation for user update.
     */
    private function patchUserOperation($id, $first_name, $family_name, $phoneNumbers, $addressesArr)
    {
        $ops = ScimHelper::buildUserPatchOperations([
            'first_name' => $first_name,
            'family_name' => $family_name,
            'email' => null, // Email diambil dari logic lain jika perlu
            'phoneNumbers' => $phoneNumbers,
            'addresses' => $addressesArr,
        ]);
        return [
            'method' => 'PATCH',
            'path' => "/Users/$id",
            'data' => [
                'schemas' => ["urn:ietf:params:scim:api:messages:2.0:PatchOp"],
                'Operations' => $ops,
            ],
        ];
    }

    /**
     * Build PATCH operation for adding a user to a role.
     */
    private function patchRoleAddOperation($roleId, $username, $userId)
    {
        return [
            'method' => 'PATCH',
            'path' => "/Roles/$roleId",
            'data' => [
                'schemas' => ["urn:ietf:params:scim:api:messages:2.0:PatchOp"],
                'Operations' => [
                    [
                        'op' => 'add',
                        'path' => 'users',
                        'value' => [
                            [
                                'display' => $username,
                                'value' => $userId,
                            ]
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build PATCH operation for removing a user from a role.
     */
    private function patchRoleRemoveOperation($roleId, $userId)
    {
        return [
            'method' => 'PATCH',
            'path' => "/Roles/$roleId",
            'data' => [
                'schemas' => ["urn:ietf:params:scim:api:messages:2.0:PatchOp"],
                'Operations' => [
                    [
                        'op' => 'remove',
                        'path' => 'users[value eq "' . $userId . '"]',
                    ],
                ],
            ],
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
