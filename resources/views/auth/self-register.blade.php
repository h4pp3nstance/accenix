
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
    <div class="card shadow-sm w-100" style="max-width: 500px; margin: 2rem auto;">
        <div class="card-body p-4">
            <h2 class="text-center mb-2" style="color:#2574fa; font-weight:700;">Create Your Account</h2>
            <p class="text-center mb-4 text-muted">Sign up to access Accenix. It's fast and easy!</p>
            <form method="POST" action="{{ route('self-register.submit') }}" class="needs-validation" novalidate>
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required value="{{ old('first_name') }}" placeholder="First name">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Last name">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" class="form-control" id="username" name="username" required value="{{ old('username') }}" placeholder="Username">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" class="form-control" id="email" name="email" required value="{{ old('email') }}" placeholder="you@email.com">
                    <small class="form-text text-muted">Enter an active email address that can be contacted.</small>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number *</label>
                    <input type="text" class="form-control" id="phone" name="phone" required value="{{ old('phone') }}" placeholder="e.g. +628123456789" pattern="^\+\d{8,15}$" title="Phone number must start with + and include country code, e.g. +628123456789">
                    <small class="form-text text-muted">Include country code, e.g. <b>+628123456789</b></small>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password *</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Password" aria-describedby="passwordHelp">
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
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password">
                    <div id="pw-match-message" class="mt-1 small"></div>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="register-btn" disabled>Register</button>
            </form>
        </div>
    </div>
</div>

<script>
// Password checklist logic
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
    if (pwValid && pwMatch && fieldsValid) {
        registerBtn.disabled = false;
    } else {
        registerBtn.disabled = true;
    }
}

passwordInput.addEventListener('input', updateRegisterButton);
passwordConfirmInput.addEventListener('input', updateRegisterButton);
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
