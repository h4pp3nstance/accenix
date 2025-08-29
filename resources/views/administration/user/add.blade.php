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
</style>
@endpush

<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Create New User</h3>
                    <p class="text-subtitle text-muted">
                        Create a new user account with roles and permissions.
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
                                Create User
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        
        <section class="section">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="mb-1">{{ $error }}</p>
                    @endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <form action="{{ route('administration.user.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h5 class="section-title">Personal Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" id="first_name" 
                                       placeholder="Enter first name" value="{{ old('first_name') }}" required>
                                @error('first_name')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="family_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="family_name" id="family_name" 
                                       placeholder="Enter last name" value="{{ old('family_name') }}">
                                @error('family_name')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="email" 
                                       placeholder="email@domain.com" value="{{ old('email') }}" required>
                                <div class="form-text">Enter an active email address that can be contacted.</div>
                                @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="image" class="form-label">Profile Picture</label>
                                <input class="form-control" id="image" name="image" type="file" accept=".jpg,.jpeg,.png">
                                <div class="form-text">Only .jpg, .jpeg, .png files. Maximum 2MB.</div>
                                @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Numbers</label>
                        <div id="phone-group">
                            @php $old_phones = old('phone', []); @endphp
                            @if (!empty($old_phones))
                                @foreach ($old_phones as $i => $phone)
                                    <div class="row mb-2 phone-item">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="phone[]" 
                                                   placeholder="Phone number" value="{{ $phone }}">
                                        </div>
                                        <div class="col-md-4">
                                            <select class="form-select phone-type-select" name="phone_type[]">
                                                <option value="mobile" {{ (old('phone_type.' . $i) == 'mobile') ? 'selected' : '' }}>Mobile</option>
                                                <option value="home" {{ (old('phone_type.' . $i) == 'home') ? 'selected' : '' }}>Home</option>
                                                <option value="work" {{ (old('phone_type.' . $i) == 'work') ? 'selected' : '' }}>Work</option>
                                                <option value="fax" {{ (old('phone_type.' . $i) == 'fax') ? 'selected' : '' }}>Fax</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-danger btn-remove-phone {{ $i == 0 ? 'd-none' : '' }}">Remove</button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="row mb-2 phone-item">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="phone[]" placeholder="Phone number">
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-select phone-type-select" name="phone_type[]">
                                            <option value="mobile">Mobile</option>
                                            <option value="home">Home</option>
                                            <option value="work">Work</option>
                                            <option value="fax">Fax</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-danger btn-remove-phone d-none">Remove</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="add-phone">Add Phone Number</button>
                        <div class="form-text">Enter active phone numbers that can be contacted. Select appropriate type as needed.</div>
                        @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
                        @error('phone_type')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Addresses</label>
                        <div id="address-group">
                            @php $old_addresses = old('street_address', []); @endphp
                            @if (!empty($old_addresses))
                                @foreach ($old_addresses as $i => $addr)
                                    <div class="row mb-2 address-item">
                                        <div class="col-md-6">
                                            <textarea class="form-control" name="street_address[]" 
                                                      placeholder="Complete address" rows="2">{{ $addr }}</textarea>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="address_type[]">
                                                <option value="home" {{ (old('address_type.' . $i) == 'home') ? 'selected' : '' }}>Home</option>
                                                <option value="work" {{ (old('address_type.' . $i) == 'work') ? 'selected' : '' }}>Work</option>
                                                <option value="billing" {{ (old('address_type.' . $i) == 'billing') ? 'selected' : '' }}>Billing</option>
                                                <option value="shipping" {{ (old('address_type.' . $i) == 'shipping') ? 'selected' : '' }}>Shipping</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-danger btn-sm remove-address-btn {{ $i == 0 ? 'd-none' : '' }}">Remove</button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="row mb-2 address-item">
                                    <div class="col-md-6">
                                        <textarea class="form-control" name="street_address[]" 
                                                  placeholder="Complete address" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" name="address_type[]">
                                            <option value="home">Home</option>
                                            <option value="work">Work</option>
                                            <option value="billing">Billing</option>
                                            <option value="shipping">Shipping</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-danger btn-sm remove-address-btn d-none">Remove</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="add-address">Add Address</button>
                        @error('address_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        @error('address_type_other')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- Account Information Section -->
                <div class="form-section">
                    <h5 class="section-title">Account Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" id="username" 
                                       placeholder="Enter username" value="{{ old('username') }}" required>
                                <div class="form-text">No spaces, minimum 4 characters.</div>
                                @error('username')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password" 
                                           placeholder="Enter password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <ul class="mb-0 small" id="password-checklist">
                                        <li id="pw-length" class="text-danger">Minimum 8 characters</li>
                                        <li id="pw-upper" class="text-danger">Uppercase letter</li>
                                        <li id="pw-lower" class="text-danger">Lowercase letter</li>
                                        <li id="pw-number" class="text-danger">Number</li>
                                        <li id="pw-symbol" class="text-danger">Symbol</li>
                                    </ul>
                                </div>
                                @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Roles Section -->
                <div class="form-section">
                    <h5 class="section-title">User Roles</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Assigned Roles</label>
                        <div class="roles-container" id="roles-container">
                            @php
                                $selectedRoles = old('roles', []);
                                $selectedRoles = is_array($selectedRoles) ? $selectedRoles : [];
                                $everyoneRole = collect($roles)->first(function($r) {
                                    return strtolower($r['displayName'] ?? $r['name']) === 'everyone';
                                });
                                $hasRoles = false;
                            @endphp
                            
                            @if ($everyoneRole)
                                @php $hasRoles = true; @endphp
                                <div class="role-item" data-role-id="{{ $everyoneRole['id'] }}">
                                    <span>{{ $everyoneRole['displayName'] ?? $everyoneRole['name'] }}</span>
                                    <span class="badge bg-light text-dark ms-2">Default</span>
                                    <input type="hidden" name="roles[]" value="{{ $everyoneRole['id'] }}">
                                </div>
                            @endif
                            
                            @if (old('roles'))
                                @foreach (old('roles') as $roleId)
                                    @php
                                        $roleObj = collect($roles)->firstWhere('id', $roleId);
                                    @endphp
                                    @if ($roleObj && (strtolower($roleObj['displayName'] ?? $roleObj['name']) !== 'everyone'))
                                        @php $hasRoles = true; @endphp
                                        <div class="role-item" data-role-id="{{ $roleId }}">
                                            <span>{{ $roleObj['displayName'] ?? $roleObj['name'] ?? $roleId }}</span>
                                            <button type="button" class="btn-remove-role" data-role-id="{{ $roleId }}">×</button>
                                            <input type="hidden" name="roles[]" value="{{ $roleId }}">
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                            
                            @if (!$hasRoles)
                                <div class="empty-roles" id="empty-roles">
                                    No roles assigned yet. Use the dropdown below to add roles.
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="add-role-section">
                        <label for="roles" class="form-label">Add Role</label>
                        <select id="roles" class="form-select" name="roles_select">
                            <option value="" selected disabled>Select a role to add</option>
                            @foreach ($roles as $role)
                                @if (!in_array($role['id'], $selectedRoles) && (strtolower($role['displayName'] ?? $role['name']) !== 'everyone'))
                                    <option value="{{ $role['id'] }}">{{ $role['displayName'] ?? $role['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <div class="form-text">Select additional roles for this user. The "Everyone" role is automatically assigned to all users.</div>
                        @error('roles')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('administration.user.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
// User Create Page JavaScript
(function() {
    'use strict';
    
    // Password validation
    const passwordInput = document.getElementById('password');
    const checklist = {
        length: document.getElementById('pw-length'),
        upper: document.getElementById('pw-upper'),
        lower: document.getElementById('pw-lower'),
        number: document.getElementById('pw-number'),
        symbol: document.getElementById('pw-symbol')
    };
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = passwordInput.value;
            
            // Length check
            if (val.length >= 8) {
                checklist.length.classList.remove('text-danger');
                checklist.length.classList.add('text-success');
            } else {
                checklist.length.classList.remove('text-success');
                checklist.length.classList.add('text-danger');
            }
            
            // Uppercase check
            if (/[A-Z]/.test(val)) {
                checklist.upper.classList.remove('text-danger');
                checklist.upper.classList.add('text-success');
            } else {
                checklist.upper.classList.remove('text-success');
                checklist.upper.classList.add('text-danger');
            }
            
            // Lowercase check
            if (/[a-z]/.test(val)) {
                checklist.lower.classList.remove('text-danger');
                checklist.lower.classList.add('text-success');
            } else {
                checklist.lower.classList.remove('text-success');
                checklist.lower.classList.add('text-danger');
            }
            
            // Number check
            if (/[0-9]/.test(val)) {
                checklist.number.classList.remove('text-danger');
                checklist.number.classList.add('text-success');
            } else {
                checklist.number.classList.remove('text-success');
                checklist.number.classList.add('text-danger');
            }
            
            // Symbol check
            if (/[^A-Za-z0-9]/.test(val)) {
                checklist.symbol.classList.remove('text-danger');
                checklist.symbol.classList.add('text-success');
            } else {
                checklist.symbol.classList.remove('text-success');
                checklist.symbol.classList.add('text-danger');
            }
        });
    }
    
    // Password show/hide toggle
    const toggleBtn = document.getElementById('togglePassword');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    
    // Role management
    const roleSelect = document.getElementById('roles');
    const rolesContainer = document.getElementById('roles-container');
    const emptyRoles = document.getElementById('empty-roles');
    
    if (roleSelect && rolesContainer) {
        roleSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedOption = this.options[this.selectedIndex];
                const roleId = selectedOption.value;
                const roleName = selectedOption.text;
                
                // Check if role already exists
                const existingRole = rolesContainer.querySelector(`[data-role-id="${roleId}"]`);
                if (existingRole) {
                    this.value = '';
                    return;
                }
                
                // Hide empty state
                if (emptyRoles) {
                    emptyRoles.style.display = 'none';
                }
                
                // Create role element
                const roleElement = document.createElement('div');
                roleElement.className = 'role-item';
                roleElement.setAttribute('data-role-id', roleId);
                roleElement.innerHTML = `
                    <span>${roleName}</span>
                    <button type="button" class="btn-remove-role" data-role-id="${roleId}">×</button>
                    <input type="hidden" name="roles[]" value="${roleId}">
                `;
                
                rolesContainer.appendChild(roleElement);
                
                // Remove from select
                selectedOption.remove();
                
                // Reset select
                this.value = '';
            }
        });
        
        // Remove role functionality
        rolesContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-role')) {
                const roleElement = e.target.closest('.role-item');
                const roleId = e.target.getAttribute('data-role-id');
                const roleName = roleElement.querySelector('span').textContent;
                
                // Add back to select (except for Everyone role)
                if (roleName.toLowerCase() !== 'everyone') {
                    const option = document.createElement('option');
                    option.value = roleId;
                    option.textContent = roleName;
                    roleSelect.appendChild(option);
                }
                
                // Remove role element
                roleElement.remove();
                
                // Show empty state if no roles left
                const remainingRoles = rolesContainer.querySelectorAll('.role-item');
                if (remainingRoles.length === 0 && emptyRoles) {
                    emptyRoles.style.display = 'block';
                }
            }
        });
    }
    
    // Phone number management
    const phoneGroup = document.getElementById('phone-group');
    const addPhoneBtn = document.getElementById('add-phone');
    
    if (addPhoneBtn && phoneGroup) {
        addPhoneBtn.addEventListener('click', function() {
            const phoneItems = phoneGroup.querySelectorAll('.phone-item');
            const firstItem = phoneItems[0];
            const newItem = firstItem.cloneNode(true);
            
            // Clear values
            newItem.querySelector('input[name="phone[]"]').value = '';
            newItem.querySelector('select[name="phone_type[]"]').selectedIndex = 0;
            
            // Show remove button
            newItem.querySelector('.btn-remove-phone').classList.remove('d-none');
            
            phoneGroup.appendChild(newItem);
            updatePhoneTypeOptions();
        });
        
        phoneGroup.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-phone')) {
                e.target.closest('.phone-item').remove();
                updatePhoneTypeOptions();
            }
        });
        
        phoneGroup.addEventListener('change', function(e) {
            if (e.target.classList.contains('phone-type-select')) {
                updatePhoneTypeOptions();
            }
        });
    }
    
    // Address management
    const addressGroup = document.getElementById('address-group');
    const addAddressBtn = document.getElementById('add-address');
    
    if (addAddressBtn && addressGroup) {
        addAddressBtn.addEventListener('click', function() {
            const addressItems = addressGroup.querySelectorAll('.address-item');
            const firstItem = addressItems[0];
            const newItem = firstItem.cloneNode(true);
            
            // Clear values
            newItem.querySelector('textarea[name="street_address[]"]').value = '';
            newItem.querySelector('select[name="address_type[]"]').selectedIndex = 0;
            
            // Show remove button
            newItem.querySelector('.remove-address-btn').classList.remove('d-none');
            
            addressGroup.appendChild(newItem);
        });
        
        addressGroup.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-address-btn')) {
                e.target.closest('.address-item').remove();
            }
        });
    }
    
    // Phone type unique selection
    function updatePhoneTypeOptions() {
        const selects = document.querySelectorAll('.phone-type-select');
        const selectedValues = Array.from(selects).map(select => select.value);
        
        selects.forEach(select => {
            const currentValue = select.value;
            Array.from(select.options).forEach(option => {
                if (option.value && option.value !== currentValue && selectedValues.includes(option.value)) {
                    option.disabled = true;
                    option.style.display = 'none';
                } else {
                    option.disabled = false;
                    option.style.display = 'block';
                }
            });
        });
    }
    
    // Initialize phone type options
    updatePhoneTypeOptions();
})();
</script>
@endpush
