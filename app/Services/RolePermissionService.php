<?php

namespace App\Services;

use App\Helpers\ScimHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class RolePermissionService
{
    private const CACHE_KEY = 'wso2_role_permissions';
    private const CACHE_TTL = 3600; // 1 jam
    private const SCIM_ENDPOINT = '/scim2/v2/Roles';
    private const DEFAULT_TENANT = 'carbon.super';

    private ScimHelper $scimHelper;

    public function __construct(ScimHelper $scimHelper)
    {
        $this->scimHelper = $scimHelper;
    }

    /**
     * Ambil mapping role-permission dari cache atau WSO2.
     *
     * Struktur cache:
     * [
     *   'role_name' => [
     *     'id' => 'uuid-role-dari-scim',
     *     'permissions' => ['item:create', ...],
     *     'user_count' => 3,
     *     'users' => [['id' => '...', 'name' => '...'], ...]
     *   ]
     * ]
     *
     * @param bool $forceRefresh
     * @return array
     * @throws Exception
     */
    public function getRolePermissions(bool $forceRefresh = false): array
    {
        if (!$forceRefresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if ($cached !== null) {
                Log::info('RolePermissionService: Using cached role permissions');
                return $cached;
            }
        }

        try {
            Log::info('RolePermissionService: Fetching role permissions from WSO2');
            $rolePermissions = $this->fetchRolePermissionsFromWSO2();

            Cache::put(self::CACHE_KEY, $rolePermissions, self::CACHE_TTL);

            return $rolePermissions;
        } catch (Exception $e) {
            Log::error('RolePermissionService: Failed to fetch role permissions from WSO2', [
                'error' => $e->getMessage()
            ]);

            $cached = Cache::get(self::CACHE_KEY);
            if ($cached !== null) {
                Log::warning('RolePermissionService: Using stale cache as fallback');
                return $cached;
            }

            Log::critical('RolePermissionService: No cache available. Permission system is down.');
            throw new Exception('Unable to retrieve role permissions: WSO2 API unavailable and no cached data');
        }
    }

    /**
     * Ambil data role dari WSO2 SCIM2 API.
     *
     * @return array
     * @throws Exception
     */
    private function fetchRolePermissionsFromWSO2(): array
    {
        $accessToken = $this->scimHelper->getTokenISApi();
        $baseUrl = rtrim(env('IS_URL', 'https://172.18.1.111:9443'), '/');
        $endpoint = "{$baseUrl}/t/" . self::DEFAULT_TENANT . self::SCIM_ENDPOINT;

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->withOptions(['verify' => false, 'timeout' => 30])
                ->get($endpoint);

            if (!$response->successful()) {
                throw new Exception("HTTP {$response->status()}: " . $response->body());
            }

            $data = $response->json();
            if (!isset($data['Resources']) || !is_array($data['Resources'])) {
                throw new Exception('Invalid or empty role data from WSO2');
            }

            return $this->processRolePermissions($data['Resources']);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new Exception("HTTP request failed: " . $e->getMessage());
        }
    }

    /**
     * Proses data mentah dari WSO2 menjadi struktur yang konsisten.
     *
     * @param array $roles
     * @return array
     */
    private function processRolePermissions(array $roles): array
    {
        $result = [];

        foreach ($roles as $role) {
            $displayName = $role['displayName'] ?? null;
            $roleId = $role['id'] ?? null; // Ini adalah ID role yang benar!

            if (!$displayName || !$roleId) {
                Log::warning('Skipping invalid role data', ['role' => $role]);
                continue;
            }

            // Ekstrak permissions
            $permissions = [];
            if (!empty($role['permissions']) && is_array($role['permissions'])) {
                foreach ($role['permissions'] as $perm) {
                    if (isset($perm['value'])) {
                        $permissions[] = [
                            'id' => $perm['value'],
                            'name' => $perm['value']
                        ];
                    }
                }
            }

            // Ekstrak users
            $users = [];
            $userCount = 0;

            if (!empty($role['users']) && is_array($role['users'])) {
                $userCount = count($role['users']);
                foreach ($role['users'] as $user) {
                    if (is_string($user)) {
                        $users[] = ['id' => $user, 'name' => $user];
                    } elseif (is_array($user)) {
                        $users[] = [
                            'id' => $user['value'] ?? $user['id'] ?? 'unknown',
                            'name' => $user['display'] ?? $user['name'] ?? $user['value'] ?? 'Unknown User'
                        ];
                    }
                }
            }

            $result[$displayName] = [
                'id' => $roleId, // 🔥 Penting: simpan roleId
                'permissions' => $permissions,
                'user_count' => $userCount,
                'users' => $users
            ];
        }

        Log::info('RolePermissionService: Processed roles', [
            'total_roles' => count($result),
            'role_names' => array_keys($result)
        ]);

        return $result;
    }

    /**
     * Cek apakah user memiliki izin tertentu.
     *
     * @param array $userRoles
     * @param string $permission
     * @return bool
     */
    public function hasPermission(array $userRoles, string $permission): bool
    {
        if (empty($userRoles)) {
            return false;
        }

        $rolePermissions = $this->getRolePermissions();
        $lowerMap = array_change_key_case($rolePermissions);

        foreach ($userRoles as $role) {
            $roleLower = strtolower($role);
            if (!isset($lowerMap[$roleLower]['permissions'])) {
                continue;
            }

            foreach ($lowerMap[$roleLower]['permissions'] as $perm) {
                $permId = is_array($perm) ? $perm['id'] : $perm;
                if ($permId === $permission) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Cek apakah user memiliki minimal satu dari daftar izin.
     *
     * @param array $userRoles
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $userRoles, array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($userRoles, $perm)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek apakah user memiliki semua izin yang diminta.
     *
     * @param array $userRoles
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $userRoles, array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if (!$this->hasPermission($userRoles, $perm)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Dapatkan semua izin dari role user.
     *
     * @param array $userRoles
     * @return array
     */
    public function getUserPermissions(array $userRoles): array
    {
        if (empty($userRoles)) {
            return [];
        }

        $rolePermissions = $this->getRolePermissions();
        $lowerMap = array_change_key_case($rolePermissions);
        $perms = [];

        foreach ($userRoles as $role) {
            $roleLower = strtolower($role);
            if (!isset($lowerMap[$roleLower]['permissions'])) {
                continue;
            }

            foreach ($lowerMap[$roleLower]['permissions'] as $perm) {
                $perms[] = is_array($perm) ? $perm['id'] : $perm;
            }
        }

        return array_values(array_unique($perms));
    }

    /**
     * Alias untuk backward compatibility.
     */
    public function getPermissionsForRoles(array $userRoles): array
    {
        return $this->getUserPermissions($userRoles);
    }

    /**
     * Dapatkan roleId dari nama role.
     *
     * @param string $roleName
     * @return string|null
     */
    public function getRoleId(string $roleName): ?string
    {
        $roles = $this->getRolePermissions();
        return $roles[$roleName]['id'] ?? null;
    }

    /**
     * Dapatkan detail role (termasuk permissions dan users).
     *
     * @param string $roleName
     * @return array|null
     */
    public function getRoleDetails(string $roleName): ?array
    {
        $roles = $this->getRolePermissions();
        return $roles[$roleName] ?? null;
    }

    /**
     * Hapus cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Log::info('RolePermissionService: Cache cleared');
    }

    /**
     * Refresh cache dari WSO2.
     *
     * @return array
     */
    public function refreshCache(): array
    {
        $this->clearCache();
        return $this->getRolePermissions(forceRefresh: true);
    }
}