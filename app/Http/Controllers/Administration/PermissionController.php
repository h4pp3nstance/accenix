<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\RolePermissionService;

class PermissionController extends Controller
{
    protected $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    public function ajax(Request $request)
    {
        try {
            $allPermissions = [];
            $rolePermissions = $this->rolePermissionService->getRolePermissions();
            
            // Collect all unique permissions
            foreach ($rolePermissions as $roleName => $roleData) {
                $permissions = $roleData['permissions'] ?? [];
                foreach ($permissions as $permission) {
                    // Handle both string and object permission formats
                    $permissionId = is_array($permission) ? ($permission['id'] ?? $permission['name'] ?? '') : $permission;
                    
                    if (!isset($allPermissions[$permissionId])) {
                        $parts = explode(':', $permissionId);
                        $resource = $parts[0] ?? 'unknown';
                        $action = $parts[1] ?? 'unknown';
                        
                        $allPermissions[$permissionId] = [
                            'id' => $permissionId,
                            'name' => $permissionId,
                            'display_name' => $this->getPermissionDisplayName($permissionId),
                            'resource' => $resource,
                            'action' => $action,
                            'description' => $this->getPermissionDescription($permissionId),
                            'assigned_roles' => []
                        ];
                    }
                    
                    // Add role to the assigned roles list
                    $allPermissions[$permissionId]['assigned_roles'][] = $roleName;
                }
            }

            // Convert to indexed array
            $permissions = array_values($allPermissions);

            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => count($permissions),
                'recordsFiltered' => count($permissions),
                'data' => $permissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function index(Request $request)
    {
        return view('administration.permission.index');
    }

    public function create()
    {
        return view('administration.permission.create');
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Permission creation is managed by WSO2 Identity Server'
        ]);
    }

    public function edit($permissionName)
    {
        $rolePermissions = $this->rolePermissionService->getRolePermissions();
        $assignedRoles = [];
        
        // Find which roles have this permission
        foreach ($rolePermissions as $roleName => $roleData) {
            $permissions = $roleData['permissions'] ?? [];
            if (in_array($permissionName, $permissions)) {
                $assignedRoles[] = $roleName;
            }
        }
        
        $parts = explode(':', $permissionName);
        $permission = [
            'name' => $permissionName,
            'display_name' => $this->getPermissionDisplayName($permissionName),
            'resource' => $parts[0] ?? 'unknown',
            'action' => $parts[1] ?? 'unknown',
            'description' => $this->getPermissionDescription($permissionName),
            'assigned_roles' => $assignedRoles
        ];
        
        return view('administration.permission.edit', compact('permission'));
    }

    public function update(Request $request, $permissionName)
    {
        // Note: Since WSO2 manages permissions, this would be a placeholder
        // In a real implementation, you'd make API calls to WSO2 to update permissions
        return response()->json([
            'success' => true,
            'message' => 'Permission updates are managed by WSO2 Identity Server'
        ]);
    }

    public function show($permissionName)
    {
        $rolePermissions = $this->rolePermissionService->getRolePermissions();
        $assignedRoles = [];
        
        // Find which roles have this permission
        foreach ($rolePermissions as $roleName => $roleData) {
            $permissions = $roleData['permissions'] ?? [];
            if (in_array($permissionName, $permissions)) {
                $assignedRoles[] = $roleName;
            }
        }
        
        $parts = explode(':', $permissionName);
        $permission = [
            'name' => $permissionName,
            'display_name' => $this->getPermissionDisplayName($permissionName),
            'resource' => $parts[0] ?? 'unknown',
            'action' => $parts[1] ?? 'unknown',
            'description' => $this->getPermissionDescription($permissionName),
            'assigned_roles' => $assignedRoles
        ];
        
        return view('administration.permission.show', compact('permission'));
    }

    public function destroy($permissionName)
    {
        // Note: Since WSO2 manages permissions, this would be a placeholder
        // In a real implementation, you'd make API calls to WSO2 to delete permissions
        return response()->json([
            'success' => true,
            'message' => 'Permission deletion is managed by WSO2 Identity Server'
        ]);
    }

    /**
     * Get available permissions for role assignment
     */
    public function getAvailablePermissions()
    {
        try {
            $allPermissions = [];
            $rolePermissions = $this->rolePermissionService->getRolePermissions();
            
            // Collect all unique permissions
            foreach ($rolePermissions as $roleName => $roleData) {
                $permissions = $roleData['permissions'] ?? [];
                foreach ($permissions as $permission) {
                    // Handle both string and object permission formats
                    $permissionId = is_array($permission) ? ($permission['id'] ?? $permission['name'] ?? '') : $permission;
                    
                    if (!isset($allPermissions[$permissionId])) {
                        $parts = explode(':', $permissionId);
                        $resource = $parts[0] ?? 'unknown';
                        $action = $parts[1] ?? 'unknown';
                        
                        $allPermissions[$permissionId] = [
                            'id' => $permissionId,
                            'name' => $permissionId,
                            'displayName' => $this->getPermissionDisplayName($permissionId),
                            'description' => $this->getPermissionDescription($permissionId),
                            'category' => ucwords(str_replace(['_', '-'], ' ', $resource))
                        ];
                    }
                }
            }

            // Convert to indexed array
            $permissions = array_values($allPermissions);

            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getPermissionDisplayName($permission)
    {
        $parts = explode(':', $permission);
        $resource = ucfirst($parts[0] ?? '');
        $action = ucfirst($parts[1] ?? '');
        
        return $action . ' ' . $resource;
    }

    private function getPermissionDescription($permission)
    {
        $parts = explode(':', $permission);
        $resource = $parts[0] ?? 'resource';
        $action = $parts[1] ?? 'action';
        
        $actionDescriptions = [
            'read' => 'View and access',
            'create' => 'Create new',
            'update' => 'Modify existing',
            'delete' => 'Remove or delete',
            'approve' => 'Approve or authorize',
            'export' => 'Export data from',
            'detail' => 'View detailed information of'
        ];
        
        $actionText = $actionDescriptions[$action] ?? $action;
        $resourceText = str_replace(['_', '-'], ' ', $resource);
        
        return "Allows user to {$actionText} {$resourceText}";
    }
}
