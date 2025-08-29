@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .user-info-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .role-item {
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
    
    .role-item .btn-remove-role {
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
    
    .role-item .btn-remove-role:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .roles-container {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        min-height: 80px;
        margin-bottom: 1rem;
        background: #fdfdfd;
    }
    
    .empty-roles {
        text-align: center;
        color: #6c757d;
        font-style: italic;
        padding: 1rem;
    }
    
    .add-role-section {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
    }
</style>
@endpush

<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Edit User</h3>
                    <p class="text-subtitle text-muted">
                        Edit user account details and permissions.
                    </p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/dashboard">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('administration.user.index') }}">User Management</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Edit User
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        
        <section class="section">
            <div class="card">
                <div class="card-body">
                    @include('alert.alert')
                    
                    <form method="POST" action="{{ route('administration.user.update', $user_id) }}">
                        @csrf
                        @method('PUT')
                        
                        <!-- User Information Section -->
                        <div class="user-info-section">
                            <h5 class="mb-3"><i class="bi bi-person-circle"></i> User Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold mb-0">Full Name</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control"
                                        value="{{ isset($data->name->givenName) ? $data->name->givenName : '' }} {{ isset($data->name->familyName) ? $data->name->familyName : '' }}" disabled>
                                    <small class="form-text text-muted">Complete name (read-only)</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label fw-bold">First Name</label>
                                    <input type="text" class="form-control" name="first_name" id="first_name"
                                        value="{{ isset($data->name->givenName) ? $data->name->givenName : '' }}">
                                    <small class="form-text text-muted">User's first name</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="family_name" class="form-label fw-bold">Family Name</label>
                                    <input type="text" class="form-control" name="family_name" id="family_name"
                                        value="{{ isset($data->name->familyName) ? $data->name->familyName : '' }}">
                                    <small class="form-text text-muted">User's family name</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Username</label>
                                    <input type="text" class="form-control" value="{{ $data->userName ?? '' }}" disabled>
                                    <small class="form-text text-muted">Username (read-only)</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="text" class="form-control" value="{{ isset($data->emails[0]) ? $data->emails[0] : 'No email set' }}" disabled>
                                    <small class="form-text text-muted">Email address (read-only)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Roles Section -->
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-shield-lock"></i> User Roles</h5>
                            
                            <div class="roles-container" id="roles-container">
                                @if(isset($data->roles) && count($data->roles) > 0)
                                    @foreach ($data->roles as $role)
                                        <span class="role-item" data-role-id="{{ $role->id ?? $role->value ?? $role['id'] ?? '' }}">
                                            {{ $role->display ?? 'Unknown Role' }}
                                            <button type="button" class="btn-remove-role" title="Remove role">×</button>
                                            <input type="hidden" name="roles[]" value="{{ $role->id ?? $role->value ?? $role['id'] ?? '' }}">
                                        </span>
                                    @endforeach
                                @else
                                    <div class="empty-roles" id="empty-roles">
                                        <i class="bi bi-shield-lock"></i><br>
                                        No roles assigned yet
                                    </div>
                                @endif
                            </div>
                            
                            <div class="add-role-section">
                                <div class="row align-items-end">
                                    <div class="col-md-8">
                                        <label for="roles" class="form-label fw-bold">Add Role</label>
                                        <select id="roles" class="form-select" name="new_role">
                                            <option value="" selected disabled>Select Role</option>
                                            @php
                                                $assignedRoles = isset($data->roles) ? collect($data->roles)->pluck('display')->map(function($v){ return strtolower($v); })->toArray() : [];
                                            @endphp
                                            @foreach ($roles['Resources'] as $rl)
                                                @if (!in_array(strtolower($rl['displayName']), $assignedRoles))
                                                    <option value="{{ $rl['id'] }}">{{ $rl['displayName'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button id="addRoleBtn" type="button" class="btn btn-primary w-100" style="display:none;">
                                            <i class="bi bi-plus-circle"></i> Add Role
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('administration.user.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Users
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Update User
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const roleSelect = document.getElementById('roles');
    const addRoleBtn = document.getElementById('addRoleBtn');
    const rolesContainer = document.getElementById('roles-container');
    const emptyRoles = document.getElementById('empty-roles');

    if (roleSelect && addRoleBtn && rolesContainer) {
        // Show/hide add button based on selection
        roleSelect.addEventListener('change', function() {
            if (roleSelect.value) {
                addRoleBtn.style.display = 'block';
            } else {
                addRoleBtn.style.display = 'none';
            }
        });

        // Add role functionality
        addRoleBtn.addEventListener('click', function() {
            const selectedOption = roleSelect.options[roleSelect.selectedIndex];
            const roleName = selectedOption.text;
            const roleId = selectedOption.value;
            
            if (!roleId) return;
            
            // Check if role already exists
            const existingRoles = rolesContainer.querySelectorAll('.role-item');
            const exists = Array.from(existingRoles).some(item => 
                item.dataset.roleId === roleId
            );
            
            if (exists) {
                alert('Role already assigned to this user');
                return;
            }
            
            // Remove empty state if present
            if (emptyRoles) {
                emptyRoles.remove();
            }
            
            // Create new role item
            const roleItem = document.createElement('span');
            roleItem.className = 'role-item';
            roleItem.dataset.roleId = roleId;
            roleItem.innerHTML = `
                ${roleName}
                <button type="button" class="btn-remove-role" title="Remove role">×</button>
                <input type="hidden" name="roles[]" value="${roleId}">
            `;
            
            rolesContainer.appendChild(roleItem);
            
            // Remove from dropdown
            selectedOption.remove();
            
            // Reset selection
            roleSelect.value = '';
            addRoleBtn.style.display = 'none';
        });

        // Remove role functionality
        rolesContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-role')) {
                const roleItem = e.target.closest('.role-item');
                const roleId = roleItem.dataset.roleId;
                const roleName = roleItem.textContent.trim().replace('×', '');
                
                // Add back to dropdown
                const option = document.createElement('option');
                option.value = roleId;
                option.textContent = roleName;
                roleSelect.appendChild(option);
                
                // Remove role item
                roleItem.remove();
                
                // Show empty state if no roles left
                const remainingRoles = rolesContainer.querySelectorAll('.role-item');
                if (remainingRoles.length === 0) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-roles';
                    emptyDiv.id = 'empty-roles';
                    emptyDiv.innerHTML = '<i class="bi bi-shield-lock"></i><br>No roles assigned yet';
                    rolesContainer.appendChild(emptyDiv);
                }
            }
        });
    }
})();
</script>
@endpush
@endsection