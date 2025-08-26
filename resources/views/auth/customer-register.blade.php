@extends('layouts.guest')

@section('content')

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow-sm w-100" style="max-width: 600px; margin: 2rem auto;">
        <div class="card-body p-4">
            <!-- Header -->
            <div class="text-center mb-4">
                <h2 style="color:#2574fa; font-weight:700;">Complete Your Registration</h2>
                <p class="text-muted mb-2">Welcome to {{ $prefilledData['company_name'] }}!</p>
                <p class="text-muted small">Create your personal account to access the Accenix platform</p>
            </div>

            <!-- Company Info Banner -->
            <div class="alert alert-info" role="alert">
                <i class="bi bi-building me-2"></i>
                <strong>Company:</strong> {{ $prefilledData['company_name'] }}<br>
                <i class="bi bi-envelope me-2"></i>
                <strong>Contact Email:</strong> {{ $prefilledData['email'] }}
            </div>

            <form method="POST" action="{{ route('customer.register.submit', $prefilledData['token']) }}" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="organization_id" value="{{ $prefilledData['organization_id'] }}">
                <input type="hidden" name="company_name" value="{{ $prefilledData['company_name'] }}">
                
                <!-- Personal Information -->
                <div class="mb-4">
                    <h5 class="text-secondary mb-3">Personal Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   required value="{{ old('first_name', $prefilledData['contact_person']) }}" placeholder="First name">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="{{ old('last_name') }}" placeholder="Last name">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               required value="{{ old('email', $prefilledData['email'] ?? '') }}" readonly
                               style="background-color: #f8f9fa;">
                        <small class="form-text text-muted">This must match your registered contact email.</small>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                               required value="{{ old('phone') }}" placeholder="e.g. +628123456789" 
                               pattern="^\+\d{8,15}$" title="Phone number must start with + and include country code">
                        <small class="form-text text-muted">Include country code, e.g. <b>+628123456789</b></small>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="mb-4">
                    <h5 class="text-secondary mb-3">Account Information</h5>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               required value="{{ old('username') }}" placeholder="Choose a username"
                               pattern="^[a-zA-Z0-9_]+$" title="Username can only contain letters, numbers, and underscores">
                        <small class="form-text text-muted">Must be 3-50 characters. Letters, numbers, and underscores only.</small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                   required placeholder="Create a strong password" aria-describedby="passwordHelp">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <ul class="list-unstyled mt-2 mb-0" id="password-checklist">
                            <li id="pw-length" class="text-danger"><i class="bi bi-x-circle"></i> Must be between 8 and 30 characters</li>
                            <li id="pw-case" class="text-danger"><i class="bi bi-x-circle"></i> At least 1 uppercase and 1 lowercase letters</li>
                            <li id="pw-number" class="text-danger"><i class="bi bi-x-circle"></i> At least 1 number(s)</li>
                            <li id="pw-symbol" class="text-danger"><i class="bi bi-x-circle"></i> At least 1 special character(s)</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="password_confirmation" 
                               name="password_confirmation" required placeholder="Repeat password">
                        <div id="pw-match-message" class="mt-1 small"></div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a> *
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="register-btn" disabled>
                    <i class="bi bi-person-plus me-2"></i>Complete Registration
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    Need help? Contact our support team at 
                    <a href="mailto:support@loccana.com">support@loccana.com</a>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
// Password checklist logic (same as self-register)
const passwordInput = document.getElementById('password');
const passwordConfirmInput = document.getElementById('password_confirmation');
const pwLength = document.getElementById('pw-length');
const pwCase = document.getElementById('pw-case');
const pwNumber = document.getElementById('pw-number');
const pwSymbol = document.getElementById('pw-symbol');
const togglePassword = document.getElementById('togglePassword');
const togglePasswordIcon = document.getElementById('togglePasswordIcon');
const pwMatchMessage = document.getElementById('pw-match-message');
const registerBtn = document.getElementById('register-btn');
const termsCheckbox = document.getElementById('terms');

const requiredFields = [
    document.getElementById('first_name'),
    document.getElementById('username'),
    document.getElementById('email'),
    document.getElementById('phone'),
    passwordInput,
    passwordConfirmInput
];

function checkPasswordMatch() {
    if (!passwordConfirmInput.value) {
        pwMatchMessage.textContent = '';
        return false;
    }
    if (passwordInput.value === passwordConfirmInput.value) {
        pwMatchMessage.textContent = 'Passwords match';
        pwMatchMessage.className = 'mt-1 small text-success';
        return true;
    } else {
        pwMatchMessage.textContent = 'Passwords do not match';
        pwMatchMessage.className = 'mt-1 small text-danger';
        return false;
    }
}

function checkPasswordStrength() {
    const val = passwordInput.value;
    let valid = true;
    // Length
    if (val.length >= 8 && val.length <= 30) {
        pwLength.classList.remove('text-danger');
        pwLength.classList.add('text-success');
        pwLength.querySelector('i').className = 'bi bi-check-circle';
    } else {
        pwLength.classList.add('text-danger');
        pwLength.classList.remove('text-success');
        pwLength.querySelector('i').className = 'bi bi-x-circle';
        valid = false;
    }
    // Case
    if (/[a-z]/.test(val) && /[A-Z]/.test(val)) {
        pwCase.classList.remove('text-danger');
        pwCase.classList.add('text-success');
        pwCase.querySelector('i').className = 'bi bi-check-circle';
    } else {
        pwCase.classList.add('text-danger');
        pwCase.classList.remove('text-success');
        pwCase.querySelector('i').className = 'bi bi-x-circle';
        valid = false;
    }
    // Number
    if (/[0-9]/.test(val)) {
        pwNumber.classList.remove('text-danger');
        pwNumber.classList.add('text-success');
        pwNumber.querySelector('i').className = 'bi bi-check-circle';
    } else {
        pwNumber.classList.add('text-danger');
        pwNumber.classList.remove('text-success');
        pwNumber.querySelector('i').className = 'bi bi-x-circle';
        valid = false;
    }
    // Symbol
    if (/[^A-Za-z0-9]/.test(val)) {
        pwSymbol.classList.remove('text-danger');
        pwSymbol.classList.add('text-success');
        pwSymbol.querySelector('i').className = 'bi bi-check-circle';
    } else {
        pwSymbol.classList.add('text-danger');
        pwSymbol.classList.remove('text-success');
        pwSymbol.querySelector('i').className = 'bi bi-x-circle';
        valid = false;
    }
    return valid;
}

function checkRequiredFields() {
    return requiredFields.every(f => f.value && f.value.trim().length > 0);
}

function updateRegisterButton() {
    const pwValid = checkPasswordStrength();
    const pwMatch = checkPasswordMatch();
    const fieldsValid = checkRequiredFields();
    const termsAccepted = termsCheckbox.checked;
    
    if (pwValid && pwMatch && fieldsValid && termsAccepted) {
        registerBtn.disabled = false;
    } else {
        registerBtn.disabled = true;
    }
}

passwordInput.addEventListener('input', updateRegisterButton);
passwordConfirmInput.addEventListener('input', updateRegisterButton);
termsCheckbox.addEventListener('change', updateRegisterButton);
requiredFields.forEach(f => f.addEventListener('input', updateRegisterButton));

togglePassword.addEventListener('click', function() {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    togglePasswordIcon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

// Initial state
updateRegisterButton();
</script>
@endsection
