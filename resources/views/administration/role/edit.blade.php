@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .form-section {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #0066cc;
        box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #0066cc, #004499);
        border: none;
        border-radius: 6px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #0052a3, #003366);
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 6px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-1px);
    }
    
    .user-item {
        background: linear-gradient(135deg, #0066cc, #004499);
        color: white;
        border: none;
        border-radius: 20px;
        padding: 0.5rem 1rem;
        margin: 0.25rem;
        display: inline-flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .user-item .btn-remove-user {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        margin-left: 0.5rem;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .user-item .btn-remove-user:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .users-container {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        min-height: 80px;
        margin-bottom: 1rem;
        background: #fdfdfd;
    }
    
    .empty-users {
        text-align: center;
        color: #6c757d;
        font-style: italic;
        padding: 1rem;
    }
    
    .add-user-section {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
    }
    
    .permission-tag {
        font-size: 0.8rem;
        padding: 0.4rem 0.7rem;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #0066cc, #004499) !important;
        border: 1px solid rgba(255,255,255,0.1);
        color: white;
        margin: 0.1rem;
        font-weight: 500;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }
    
    .permission-tag:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    
    .permission-tag .btn-close {
        background: none;
        border: none;
        color: white;
        opacity: 0.7;
        font-size: 0.75rem;
        margin-left: 0.4rem;
        padding: 0;
        width: 14px;
        height: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s ease;
        line-height: 1;
    }
    
    .permission-tag .btn-close:hover {
        opacity: 1;
        background-color: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

    .remove-permission {
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        margin-left: 8px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        transition: background-color 0.2s ease;
        flex-shrink: 0;
    }

    .remove-permission:hover {
        background: rgba(255,255,255,0.3);
    }

    .permission-tag.resource-item { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .permission-tag.resource-uom { background: linear-gradient(135deg, #17a2b8, #117a8b); }
    .permission-tag.resource-partner { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
    .permission-tag.resource-coa { background: linear-gradient(135deg, #fd7e14, #e65100); }
    .permission-tag.resource-gudang { background: linear-gradient(135deg, #e83e8c, #c2185b); }
    .permission-tag.resource-price { background: linear-gradient(135deg, #20c997, #0a6e5c); }
    .permission-tag.resource-po { background: linear-gradient(135deg, #dc3545, #a71e2a); }
    .permission-tag.resource-accounting { background: linear-gradient(135deg, #6610f2, #4c0a99); }

    .empty-permissions {
        text-align: center;
        color: #6c757d;
        font-size: 0.875rem;
        font-style: italic;
        padding: 2rem;
    }

    .empty-permissions i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        opacity: 0.5;
    }
    
    .permissions-display-area {
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        min-height: 120px;
        padding: 15px;
        transition: all 0.3s ease;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .permissions-display-area:hover {
        border-color: #0066cc;
        background: #f0f8ff;
    }

    #permission-categories {
        display: block;
        max-height: 600px;
        overflow-y: auto;
        padding-right: 8px;
    }

    .permission-category-card {
        background: white;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .permission-category-header {
        background: linear-gradient(135deg, #0066cc, #004499);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .permission-category-header:hover {
        background: linear-gradient(135deg, #004499, #0066cc);
    }

    .category-toggle {
        transition: transform 0.2s ease;
    }

    .category-toggle.rotated {
        transform: rotate(180deg);
    }

    .permission-category-content {
        padding: 1rem;
        display: none;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem 1rem;
    }

    .permission-category-content.show {
        display: grid;
    }

    .permission-item {
        display: flex;
        align-items: center;
        padding: 0.4rem 0.6rem;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 0;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
        font-size: 0.95rem;
    }

    .permission-item:hover:not(.disabled) {
        border-color: #0066cc;
        box-shadow: 0 2px 4px rgba(0, 102, 204, 0.1);
        transform: translateY(-1px);
    }

    .permission-item.selected {
        background: #e3f2fd;
        border-color: #0066cc;
    }

    .permission-item.disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .permission-checkbox {
        margin-right: 0.75rem;
    }

    .permission-info {
        flex: 1;
    }

    .permission-name {
        font-weight: 500;
        color: #333;
    }

    .permission-description {
        font-size: 0.875rem;
        color: #666;
        margin-top: 0.25rem;
    }

    .permission-badge {
        background: #f8f9fa;
        color: #666;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-family: monospace;
    }

    .search-permissions {
        margin-bottom: 1rem;
    }

    .search-permissions .form-control {
        border-radius: 8px;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
    }

    .search-permissions .form-control:focus {
        border-color: #0066cc;
        box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.15);
    }
</style>
@endpush

<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Edit Role</h3>
                    <p class="text-subtitle text-muted">
                        Edit role permissions and user assignments.
                    </p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/dashboard">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('administration.role.index') }}">Role Management</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Edit Role
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        
        <section class="section">
            @include('alert.alert')
            
            <form method="POST" id="editRoleForm" action="{{ route('administration.role.update', $role['name']) }}">
                @csrf
                @method('PUT')
                
                <!-- Role Information Section -->
                <div class="form-section">
                    <h5 class="section-title">Role Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role_name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="role_name" 
                                       value="{{ old('name', $role['name']) }}" required>
                                <div class="form-text">Enter a unique role name</div>
                                <input type="hidden" name="original_name" value="{{ $role['name'] }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="audience_value" class="form-label">Organization <span class="text-danger">*</span></label>
                                <select id="audience_value" name="audienceValue" class="form-select" required>
                                    <option value="">Select organization...</option>
                                </select>
                                <div class="form-text">Select the organization for this role</div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="audienceType" value="organization">
                </div>

                <!-- Users Section -->
                <div class="form-section">
                    <h5 class="section-title">Assigned Users</h5>
                    
                    @if($role['is_protected'])
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-shield-exclamation me-2"></i>
                            <strong>Protected System Role:</strong> 
                            This is a system-protected role ({{ $role['name'] }}). Some operations may be restricted to maintain system security.
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <label class="form-label">Current Users</label>
                        <div class="users-container" id="users-container">
                            @if(isset($role['users']) && count($role['users']) > 0)
                                @foreach($role['users'] as $user)
                                    @php
                                        $userId = is_array($user) ? ($user['id'] ?? $user['value'] ?? $user['name'] ?? 'unknown') : $user;
                                        $userName = is_array($user) ? ($user['name'] ?? $user['display'] ?? $user['id'] ?? 'Unknown User') : $user;
                                    @endphp
                                    <div class="user-item" data-user-id="{{ $userId }}">
                                        <span>{{ $userName }}</span>
                                        <button type="button" class="btn-remove-user" data-user-id="{{ $userId }}">×</button>
                                        <input type="hidden" name="users[]" value="{{ $userId }}">
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-users" id="empty-users">
                                    No users assigned to this role yet.
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="add-user-section">
                        <label class="form-label">Add User</label>
                        <div class="input-group">
                            <select id="available_users" class="form-select">
                                <option value="">Select a user to add...</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary" id="add-user-btn">Add User</button>
                        </div>
                        <div class="form-text">Select users to assign to this role. Users can be removed by clicking the × button.</div>
                    </div>
                </div>

                <!-- Permissions Section -->
                <div class="form-section">
                    <h5 class="section-title">Role Permissions</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Selected Permissions</label>
                        <div class="permissions-display-area" id="permissions-display-area">
                            @if(isset($role['permissions']) && count($role['permissions']) > 0)
                                @foreach($role['permissions'] as $permission)
                                    <div class="permission-tag resource-{{ strtolower(explode(':', $permission)[0] ?? 'default') }}">
                                        <span>{{ $permission }}</span>
                                        <button type="button" class="remove-permission" data-permission-id="{{ $permission }}">×</button>
                                        <input type="hidden" name="permissions[]" value="{{ $permission }}">
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-permissions" id="empty-permissions">
                                    <i class="bi bi-shield-lock"></i><br>
                                    No permissions assigned to this role yet.
                                </div>
                            @endif
                        </div>
                        <div class="form-text">Click on permissions below to add them. Selected permissions appear as tags above.</div>
                    </div>
                    
                    <div class="search-permissions">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" id="permission-search" placeholder="Search permissions...">
                        </div>
                    </div>
                    
                    <div id="permission-categories">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading permissions...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading available permissions...</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('administration.role.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="update-role-btn">
                        <i class="bi bi-check-circle"></i> Update Role
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

@push('scripts')
<script>
// Remove any existing event handlers for this modal to prevent duplicates immediately
$(document).off('.editRoleModal');

$(document).ready(function() {
    // Global variables to store all data
    let allUsersData = [];
    let allPermissionsData = [];

    // Function definitions (moved to top for proper scoping)
    function loadExistingUsers() {
        console.log('Loading existing users for role...');
        const roleName = "{{ $role['name'] ?? '' }}";
        
        if (!roleName) {
            console.error('Role name not available');
            $('#selected-users-list').html('<small class="text-muted">Role name not available</small>');
            return;
        }
        
        $.ajax({
            url: '{{ route('administration.role.api.role-users', ':id') }}'.replace(':id', encodeURIComponent(roleName)),
            type: 'GET',
            success: function(response) {
                console.log('Existing users response:', response);
                if (response.success && response.data && response.data.length > 0) {
                    // Clear loading text
                    $('#selected-users-list').empty();
                    
                    // Add each existing user
                    response.data.forEach(function(user) {
                        const userItem = `
                            <div class="user-item d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded" data-user-id="${user.id}">
                                <span>${user.name} (${user.userName})</span>
                                <button type="button" class="btn btn-sm btn-danger remove-user-btn">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        `;
                        $('#selected-users-list').append(userItem);
                    });
                    
                    console.log('Existing users loaded successfully, count:', response.data.length);
                } else {
                    // No existing users or failed to load
                    $('#selected-users-list').html('<small class="text-muted">No users assigned</small>');
                    console.log('No existing users found or failed to load');
                }
            },
            error: function(xhr) {
                console.error('Failed to load existing users:', xhr);
                $('#selected-users-list').html('<small class="text-muted">Failed to load assigned users</small>');
            }
        });
    }

    function updateAvailableUsers(allUsers) {
        const $select = $('#available_users');
        const selectedUserIds = [];
        
        // Get currently selected user IDs
        $('#selected-users-list .user-item').each(function() {
            selectedUserIds.push($(this).data('user-id'));
        });
        
        // Clear and repopulate dropdown with available users only
        $select.empty().append('<option value="">Select user to add</option>');
        
        allUsers.forEach(function(user) {
            if (!selectedUserIds.includes(user.id)) {
                $select.append(`<option value="${user.id}">${user.name} (${user.userName})</option>`);
            }
        });
    }

    function loadAvailableOrganizations() {
        console.log('Loading available organizations...');
        
        $.ajax({
            url: '{{ route('administration.role.api.organizations') }}',
            type: 'GET',
            success: function(response) {
                console.log('Organizations response:', response);
                if (response.success && response.data) {
                    updateAvailableOrganizations(response.data);
                    
                    // Set current role's organization if editing
                    // For roles, we'll set a default organization or leave empty
                    const currentAudienceValue = "";
                    if (currentAudienceValue) {
                        $('#audience_value').val(currentAudienceValue);
                    }
                } else {
                    console.error('Failed to load organizations:', response.message || 'Unknown error');
                    // Fallback to hardcoded value if API fails
                    const fallbackOrgs = [
                        {id: "10084a8d-113f-4211-a0d5-efe36b082211", name: "Super"}
                    ];
                    updateAvailableOrganizations(fallbackOrgs);
                    
                    // Set current role's organization if editing
                    // For roles, we'll set a default organization or leave empty
                    const currentAudienceValue = "";
                    if (currentAudienceValue) {
                        $('#audience_value').val(currentAudienceValue);
                    }
                }
            },
            error: function(xhr) {
                console.error('Failed to load organizations:', xhr);
                // Fallback to hardcoded value if API fails
                const fallbackOrgs = [
                    {id: "10084a8d-113f-4211-a0d5-efe36b082211", name: "Super"}
                ];
                updateAvailableOrganizations(fallbackOrgs);
                
                // Set current role's organization if editing
                // For roles, we'll set a default organization or leave empty
                const currentAudienceValue = "";
                if (currentAudienceValue) {
                    $('#audience_value').val(currentAudienceValue);
                }
            }
        });
    }

    function updateAvailableOrganizations(organizations) {
        const $select = $('#audience_value');
        $select.empty();
        
        if (organizations && organizations.length > 0) {
            $select.append('<option value="">Select organization</option>');
            organizations.forEach(org => {
                $select.append(`<option value="${org.id}">${org.name}</option>`);
            });
        } else {
            $select.append('<option value="">No organizations available</option>');
        }
    }

    function loadAvailableUsers() {
        console.log('Loading available users...');
        $.ajax({
            url: '{{ route('administration.role.api.users') }}',
            type: 'GET',
            success: function(response) {
                console.log('Users response:', response);
                if (response.success && response.data) {
                    // Store all users data globally
                    allUsersData = response.data;
                    
                    // Update the available users dropdown
                    updateAvailableUsers(allUsersData);
                    
                    console.log('Users loaded successfully, count:', response.data.length);
                } else {
                    console.error('Failed to load users:', response.message || 'Unknown error');
                }
            },
            error: function(xhr) {
                console.error('Failed to load users:', xhr);
                console.error('Status:', xhr.status, 'Response:', xhr.responseText);
            }
        });
    }

    function loadAvailablePermissions() {
        console.log('Loading available permissions...');
        // Load permissions from Permission Controller (using same data as permission listing page)
        $.ajax({
            url: '{{ route('administration.permission.api.available') }}',
            type: 'GET',
            success: function(response) {
                console.log('Permissions response:', response);
                if (response.success && response.data) {
                    allPermissionsData = response.data; // Store all permissions globally
                    updateAvailablePermissions(allPermissionsData);
                    console.log('Permissions loaded successfully, count:', response.data.length);
                } else {
                    console.error('Failed to load permissions:', response.message || 'Unknown error');
                    // Fallback to placeholder data if API fails
                    loadPlaceholderPermissions();
                }
            },
            error: function(xhr) {
                console.error('Failed to load permissions:', xhr);
                console.error('Status:', xhr.status, 'Response:', xhr.responseText);
                // Fallback to placeholder data if API fails
                loadPlaceholderPermissions();
            }
        });
    }

    function updateAvailablePermissions(permissions) {
        renderPermissionGrid(permissions);
    }

    function reorganizePermissionsDisplay() {
        const $container = $('#selected-permissions-container');
        const permissions = [];
        
        // Collect all selected permissions
        $('#editRoleForm input[name="permissions[]"]').each(function() {
            const id = $(this).val();
            const parts = id.split(':');
            const resource = parts[0] || 'unknown';
            const action = parts[1] || 'unknown';
            
            permissions.push({
                id: id,
                resource: resource,
                action: action,
                displayText: formatPermissionDisplay(resource, action)
            });
        });
        
        if (permissions.length === 0) {
            $container.html('<div class="empty-permissions"><i class="bi bi-shield-lock"></i><br>No permissions selected</div>');
            return;
        }
        
        // Group by resource
        const grouped = permissions.reduce((groups, permission) => {
            const resource = permission.resource;
            if (!groups[resource]) groups[resource] = [];
            groups[resource].push(permission);
            return groups;
        }, {});
        
        // Clear container and rebuild
        $container.empty();
        
        // Sort resources alphabetically
        const sortedResources = Object.keys(grouped).sort();
        
        sortedResources.forEach(resource => {
            const categoryDiv = $(`
                <div class="permission-category">
                    <div class="category-header">${formatResourceName(resource)}</div>
                    <div class="category-permissions"></div>
                </div>
            `);
            
            const $categoryPermissions = categoryDiv.find('.category-permissions');
            
            // Sort permissions within category
            grouped[resource].sort((a, b) => a.action.localeCompare(b.action));
            
            grouped[resource].forEach(permission => {
                const resourceClass = getResourceClass(resource);
                const tag = $(`
                    <span class="permission-tag ${resourceClass}" data-permission-id="${permission.id}">
                        ${permission.displayText}
                        <button type="button" class="remove-permission" aria-label="Remove permission" title="Remove permission">
                            ×
                        </button>
                    </span>
                `);
                $categoryPermissions.append(tag);
            });
            
            $container.append(categoryDiv);
        });
    }

    // Helper functions for permission formatting and display
    function formatPermissionDisplay(resource, action) {
        const resourceNames = {
            'item': 'Item',
            'uom': 'UoM',
            'partner': 'Partner',
            'coa': 'COA',
            'gudang': 'Warehouse',
            'price': 'Price',
            'po': 'PO',
            'po_invoice': 'PO Invoice',
            'accounting': 'Accounting',
            'accountingasset': 'Asset'
        };
        
        const actionNames = {
            'create': 'Create',
            'read': 'View',
            'update': 'Edit',
            'delete': 'Delete',
            'detail': 'Detail',
            'export': 'Export'
        };
        
        const resourceName = resourceNames[resource.toLowerCase()] || resource;
        const actionName = actionNames[action.toLowerCase()] || action;
        
        return `${actionName} ${resourceName}`;
    }

    function formatResourceName(resource) {
        const resourceNames = {
            'item': 'Item Management',
            'uom': 'Unit of Measure',
            'partner': 'Partner Management',
            'coa': 'Chart of Accounts',
            'gudang': 'Warehouse Management',
            'price': 'Price Management',
            'po': 'Purchase Orders',
            'po_invoice': 'PO Invoices',
            'accounting': 'Accounting',
            'accountingasset': 'Accounting Assets'
        };
        
        return resourceNames[resource.toLowerCase()] || resource.charAt(0).toUpperCase() + resource.slice(1);
    }

    function getResourceClass(resource) {
        const classMap = {
            'item': 'resource-item',
            'uom': 'resource-uom',
            'partner': 'resource-partner',
            'coa': 'resource-coa',
            'gudang': 'resource-gudang',
            'price': 'resource-price',
            'po': 'resource-po',
            'po_invoice': 'resource-po',
            'accounting': 'resource-accounting',
            'accountingasset': 'resource-accounting'
        };
        
        return classMap[resource.toLowerCase()] || 'resource-default';
    }

    function renderPermissionGrid(permissions) {
        // Preserve open/closed state of categories
        const openCategories = {};
        $('.permission-category-content').each(function() {
            const cat = $(this).data('category');
            if ($(this).is(':visible')) {
                openCategories[cat] = true;
            }
        });

        const $container = $('#permission-categories');
        const selectedPermissionIds = [];
        
        // Get currently selected permission IDs
        $('#editRoleForm input[name="permissions[]"]').each(function() {
            selectedPermissionIds.push($(this).val());
        });
        
        // Group permissions by category
        const groupedPermissions = permissions.reduce((groups, permission) => {
            const category = permission.category || 'Other';
            if (!groups[category]) groups[category] = [];
            groups[category].push(permission);
            return groups;
        }, {});
        
        console.log('Grouped permissions:', Object.keys(groupedPermissions).length, 'categories');
        
        // Clear container
        $container.empty();
        
        if (Object.keys(groupedPermissions).length === 0) {
            $container.html('<div class="text-center text-muted p-4"><p>No permissions available or failed to load</p></div>');
            return;
        }
        
        // Render each category
        Object.keys(groupedPermissions).sort().forEach(category => {
            const categoryPermissions = groupedPermissions[category];
            const categoryCard = `
                <div class="permission-category-card">
                    <div class="permission-category-header" data-category="${category}">
                        <span>${category}</span>
                        <i class="bi bi-chevron-down category-toggle"></i>
                    </div>
                    <div class="permission-category-content" data-category="${category}" style="display: none;">
                        ${categoryPermissions.map(permission => {
                            const isSelected = selectedPermissionIds.includes(permission.name);
                            const displayName = permission.displayName || permission.name;
                            const description = permission.description || 'No description available';
                            
                            return `
                                <div class="permission-item ${isSelected ? 'selected disabled' : ''}" 
                                     data-permission-id="${permission.name}" 
                                     data-permission-name="${displayName}">
                                    <input type="checkbox" class="permission-checkbox" 
                                           ${isSelected ? 'checked disabled' : ''}>
                                    <div class="permission-info">
                                        <div class="permission-name">${displayName}</div>
                                        <div class="permission-description">${description}</div>
                                    </div>
                                    <span class="permission-badge">${permission.name}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
            $container.append(categoryCard);
        });

        // Restore open/closed state
        Object.keys(openCategories).forEach(cat => {
            if (openCategories[cat]) {
                $(`.permission-category-content[data-category="${cat}"]`).show();
                $(`.permission-category-header[data-category="${cat}"] .category-toggle`).addClass('rotated');
            }
        });
    }

    function loadPlaceholderPermissions() {
        // Fallback data in case API is not available
        const placeholderPermissions = [
            { id: 'item:create', name: 'item:create', displayName: 'Create Items', category: 'Item Management', description: 'Create new items' },
            { id: 'item:read', name: 'item:read', displayName: 'Read Items', category: 'Item Management', description: 'View items' },
            { id: 'item:update', name: 'item:update', displayName: 'Update Items', category: 'Item Management', description: 'Modify items' },
            { id: 'item:delete', name: 'item:delete', displayName: 'Delete Items', category: 'Item Management', description: 'Remove items' },
            { id: 'user:create', name: 'user:create', displayName: 'Create Users', category: 'User Management', description: 'Create new users' },
            { id: 'user:read', name: 'user:read', displayName: 'Read Users', category: 'User Management', description: 'View users' },
            { id: 'user:update', name: 'user:update', displayName: 'Update Users', category: 'User Management', description: 'Modify users' },
            { id: 'user:delete', name: 'user:delete', displayName: 'Delete Users', category: 'User Management', description: 'Remove users' },
            { id: 'role:create', name: 'role:create', displayName: 'Create Roles', category: 'Role Management', description: 'Create new roles' },
            { id: 'role:read', name: 'role:read', displayName: 'Read Roles', category: 'Role Management', description: 'View roles' },
            { id: 'role:update', name: 'role:update', displayName: 'Update Roles', category: 'Role Management', description: 'Modify roles' },
            { id: 'role:delete', name: 'role:delete', displayName: 'Delete Roles', category: 'Role Management', description: 'Remove roles' }
        ];
        
        allPermissionsData = placeholderPermissions; // Store fallback data
        updateAvailablePermissions(placeholderPermissions);
    }

    function addUserToRole(userId, userName) {
        // Check if user already added
        if ($('#selected-users-list').find(`[data-user-id="${userId}"]`).length > 0) {
            alert('User already added');
            return;
        }

        // Clear placeholder text
        if ($('#selected-users-list').find('.text-muted').length > 0) {
            $('#selected-users-list').empty();
        }

        const userItem = `
            <div class="user-item d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded" data-user-id="${userId}">
                <span>${userName}</span>
                <button type="button" class="btn btn-sm btn-danger remove-user-btn">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;
        
        $('#selected-users-list').append(userItem);
        $('#available_users').val('');
        
        // Refresh available users dropdown to remove the selected user
        updateAvailableUsers(allUsersData);
    }

    function addPermissionAndReorganize(permissionId, permissionText) {
        // Add hidden input for form submission
        $('#editRoleForm').append(`<input type="hidden" name="permissions[]" value="${permissionId}" data-permission-id="${permissionId}">`);
        
        // Reorganize display
        reorganizePermissionsDisplay();
        
        // Refresh available permissions grid to remove the selected permission
        updateAvailablePermissions(allPermissionsData);
    }

    function applyPermissionSearch(searchTerm) {
        $('.permission-item').each(function() {
            if (searchTerm === '') {
                // If search is empty, show all items
                $(this).show();
            } else {
                // Get data attributes safely
                const permissionName = ($(this).data('permission-name') || '').toString().toLowerCase();
                const permissionId = ($(this).data('permission-id') || '').toString().toLowerCase();
                const description = $(this).find('.permission-description').text().toLowerCase() || '';
                
                const matches = permissionName.includes(searchTerm) || 
                              permissionId.includes(searchTerm) || 
                              description.includes(searchTerm);
                
                $(this).toggle(matches);
            }
        });
        
        // Show/hide category cards based on visible items (but don't auto-expand/collapse)
        $('.permission-category-card').each(function() {
            const $categoryCard = $(this);
            const categoryName = $categoryCard.find('.permission-category-header').text().toLowerCase().trim();
            
            if (searchTerm === '') {
                // If search is empty, show all categories
                $categoryCard.show();
            } else {
                const visibleItems = $categoryCard.find('.permission-item:visible').length;
                const categoryMatches = categoryName.includes(searchTerm);
                
                if (categoryMatches || visibleItems > 0) {
                    // Show category if it has matches or visible items
                    $categoryCard.show();
                } else {
                    // Hide category if no matches
                    $categoryCard.hide();
                }
            }
        });
    }

    // Load available data
    loadAvailableOrganizations();
    loadAvailableUsers();
    loadAvailablePermissions();
    loadExistingUsers();
    
    // Organize existing permissions display
    reorganizePermissionsDisplay();

    // Event handlers
    // Handle permission tag removal
    $(document).on('click.editRoleModal', '.remove-permission', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const permissionId = $(this).closest('.permission-tag').data('permission-id');
        
        // Remove from hidden inputs
        $(`#editRoleForm input[name="permissions[]"][value="${permissionId}"]`).remove();
        
        // Reorganize display
        reorganizePermissionsDisplay();
        
        // Refresh available permissions grid to add the removed permission back
        updateAvailablePermissions(allPermissionsData);
    });

    // Handle add user
    $('#add-user-btn').on('click', function() {
        const userId = $('#available_users').val();
        const userName = $('#available_users option:selected').text();
        
        if (userId && userName !== 'Select user to add') {
            addUserToRole(userId, userName);
        }
    });

    // Handle remove user
    $(document).on('click.editRoleModal', '.remove-user-btn', function() {
        $(this).closest('.user-item').remove();
        
        // Show placeholder if no users left
        if ($('#selected-users-list .user-item').length === 0) {
            $('#selected-users-list').html('<small class="text-muted">No users assigned</small>');
        }
        
        // Refresh available users dropdown to add the removed user back
        updateAvailableUsers(allUsersData);
    });

    // Handle permission category toggle
    $(document).on('click.editRoleModal', '.permission-category-header', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const category = $(this).data('category');
        const $content = $(`.permission-category-content[data-category="${category}"]`);
        const $toggle = $(this).find('.category-toggle');
        
        $content.slideToggle(200);
        $toggle.toggleClass('rotated');
    });

    // Handle permission item click
    $(document).on('click.editRoleModal', '.permission-item:not(.disabled)', function(e) {
        e.stopPropagation(); // Prevent collapse on permission pick
        const permissionId = $(this).data('permission-id');
        const permissionName = $(this).data('permission-name');
        const $checkbox = $(this).find('.permission-checkbox');
        
        if (!permissionId) return;
        
        // Check if permission already added
        if ($('#editRoleForm input[name="permissions[]"][value="' + permissionId + '"]').length > 0) {
            return; // Already selected
        }
        
        // Add permission
        addPermissionAndReorganize(permissionId, permissionName);
        
        // Update the item appearance
        $(this).addClass('selected disabled');
        $checkbox.prop('checked', true).prop('disabled', true);
    });

    // Handle form submission
    $('#editRoleForm').on('submit', function(e) {
        e.preventDefault();
        
        const $submitBtn = $('#update-role-btn');
        $submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Updating...');
        
        // Collect selected users
        const selectedUsers = [];
        $('#selected-users-list .user-item').each(function() {
            selectedUsers.push($(this).data('user-id'));
        });
        
        // Add users to form data
        $('input[name="users[]"]').remove();
        selectedUsers.forEach(function(userId) {
            $('#editRoleForm').append('<input type="hidden" name="users[]" value="' + userId + '">');
        });
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'PUT',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Role updated successfully!');
                    window.location.href = '{{ route('administration.role.index') }}';
                } else {
                    alert('Failed to update role: ' + response.message);
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to update role. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMsg = Object.values(errors).flat().join(', ');
                }
                alert(errorMsg);
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="bi bi-pencil"></i> Update Role');
            }
        });
    });

    // Handle permission search with debouncing
    let searchTimeout;
    $('#permission-search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            const searchTerm = $('#permission-search').val().toLowerCase().trim();
            applyPermissionSearch(searchTerm);
        }, 150); // 150ms debounce delay
    });
});
</script>
@endpush
@endsection
