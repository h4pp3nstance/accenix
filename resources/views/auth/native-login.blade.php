<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Native Login - Accenix' }}</title>
    <link rel="icon" href="{{ asset('/metronic/assets/media/logos/loccana-logos1.png') }}" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-dark: #2563eb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --error-color: #ef4444;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .login-left-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 500px;
        }
        
        .login-right {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            padding: 3rem;
            position: relative;
        }
        
        .top-logo {
            position: absolute;
            top: 2rem;
            right: 2rem;
            max-width: 100px;
            height: auto;
        }
        
        .login-card {
            width: 100%;
            max-width: 400px;
            margin: auto;
            position: relative;
        }
        
        .logo {
            max-width: 60px;
            height: auto;
            margin-bottom: 1.5rem;
        }
        
        .brand-logo {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 2rem;
            letter-spacing: -2px;
        }
        
        .feature-list {
            text-align: left;
            margin-top: 3rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .feature-item i {
            margin-right: 1rem;
            font-size: 1.5rem;
            opacity: 0.8;
        }
        
        .company-tagline {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 1rem;
            font-weight: 300;
        }
        
        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 0.5rem 0;
            text-align: center;
        }
        
        .login-subtitle {
            font-size: 1rem;
            color: #6b7280;
            margin: 0 0 3rem 0;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .form-control {
            width: 100%;
            height: 3.25rem;
            padding: 0.875rem;
            font-size: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-control::placeholder {
            color: #9ca3af;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            width: 100%;
            height: 3.25rem;
            background-color: #3b82f6;
            border: 1px solid #3b82f6;
            border-radius: 6px;
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 1.5rem;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            border-radius: 8px;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid transparent;
            border-top: 2px solid var(--primary-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        .alert-danger {
            background-color: #fef2f2;
            color: var(--error-color);
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            color: var(--success-color);
            border: 1px solid #bbf7d0;
        }
        
        .alert-info {
            background-color: #eff6ff;
            color: var(--primary-blue);
            border: 1px solid #bfdbfe;
        }
        
        .error-message {
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .auth-step {
            display: none;
        }
        
        .auth-step.active {
            display: block;
        }
        
        .mfa-code-input {
            text-align: center;
            font-size: 1.125rem;
            letter-spacing: 0.1em;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: var(--text-muted);
        }
        
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }
        
        .mt-3 { margin-top: 0.75rem; }
        .mt-4 { margin-top: 1rem; }
        
        .d-none { display: none; }
        
        .btn-link {
            background: none;
            border: none;
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 0.875rem;
            cursor: pointer;
            padding: 0;
        }
        
        .btn-link:hover {
            text-decoration: underline;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 2rem;
        }
        
        .footer-links p {
            margin-bottom: 0.5rem;
        }
        
        .footer-links hr {
            margin: 2rem 0 1.5rem 0;
            border: 0;
            border-top: 1px solid #e5e7eb;
        }
        
        .register-section {
            margin-top: 1rem;
        }
        
        .register-section p {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .btn-success {
            background-color: #10b981;
            border-color: #10b981;
            color: white !important;
            border-radius: 6px;
            padding: 0.875rem 1rem;
            font-weight: 700;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
            text-transform: none;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            letter-spacing: 0.025em;
        }
        
        .btn-success:hover {
            background-color: #059669;
            border-color: #059669;
            color: white !important;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .btn-success span {
            color: white !important;
            font-weight: 700;
        }
        
        .btn-success i {
            color: white !important;
        }
        
        .btn-link {
            background: none;
            border: none;
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
            cursor: pointer;
            padding: 0;
            font-weight: 400;
        }
        
        .btn-link:hover {
            text-decoration: underline;
            color: #2563eb;
        }
        
        .footer-links a {
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .footer-links .separator {
            margin: 0 0.5rem;
            color: var(--text-muted);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                padding: 2rem 1rem;
                text-align: center;
                min-height: 40vh;
            }
            
            .login-right {
                padding: 2rem 1rem;
                min-height: 60vh;
            }
            
            .top-logo {
                position: static;
                margin: 0 auto 2rem auto;
                display: block;
                max-width: 80px;
            }
            
            .brand-logo {
                font-size: 2rem;
            }
            
            .feature-list {
                margin-top: 1.5rem;
                display: none; /* Hide features on mobile */
            }
            
            .feature-item {
                font-size: 1rem;
                margin-bottom: 1rem;
            }
        }
        
        .progress {
            width: 100%;
            height: 4px;
            background-color: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: var(--success-color);
            transition: width 0.3s ease;
        }
        
        .success-icon {
            font-size: 3rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <!-- Left Side - Product Information -->
        <div class="login-left">
            <div class="login-left-content">
                <div class="brand-logo">ACCENIX</div>
                <p class="company-tagline">
                    Solusi Identity and Access Management (IAM) terpadu untuk
                    mengelola identitas, akses, dan kebijakan keamanan organisasi
                    dengan teknologi modern dan antarmuka yang intuitif.
                </p>
                
                <!-- <div class="feature-list">
                    <div class="feature-item">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Single Sign-On (SSO) — Dukungan OIDC & SAML</span>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-people"></i>
                        <span>Provisioning pengguna terpusat (SCIM)</span>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-gear-fill"></i>
                        <span>RBAC — Manajemen peran dan izin</span>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-shield-lock"></i>
                        <span>Multi-Factor Authentication (MFA) & autentikasi adaptif</span>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Audit & laporan kepatuhan</span>
                    </div>
                </div> -->
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-card">
            <!-- Loading Overlay -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="text-center">
                    <div class="spinner-border mb-2"></div>
                    <div class="text-muted">Authenticating...</div>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to access Accenix IAM</p>
            </div>

            <!-- Error Alert -->
            <div class="alert alert-danger error-message" id="errorAlert" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <span id="errorMessage"></span>
            </div>

            <!-- Success Alert -->
            <div class="alert alert-success d-none" id="successAlert" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <span id="successMessage"></span>
            </div>

            <!-- Step 1: Username and Password -->
            <div class="auth-step active" id="stepCredentials">
                <form id="loginForm" method="POST" action="javascript:void(0);">
                    @csrf
                    <!-- Hidden fields for client info -->
                    <input type="hidden" id="clientUserAgent" name="client_user_agent" value="">
                    <input type="hidden" id="clientPlatform" name="client_platform" value="">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter your username" required>
                    </div>

                    <div class="form-group">

                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="organization">Organization <small class="text-muted">(leave empty for admin access)</small></label>
                        <input type="text" class="form-control" id="organization" name="organization" 
                               placeholder="Enter your organization or leave empty for admin">
                    </div>

                    <div class="form-group mb-4">
                        <button type="submit" class="btn btn-primary" id="loginBtn">
                            <span class="button-text">SIGN IN</span>
                            <span class="spinner-border d-none" role="status"></span>
                        </button>
                    </div>
                    
                    <div class="text-center mb-3">
                        <a href="/forgot-password" class="btn-link">Forgot Password?</a>
                    </div>
                </form>
            </div>

            <!-- Step 2: Multi-Factor Authentication -->
            <div class="auth-step" id="stepMfa">
                <div class="text-center mb-4">
                    <div class="alert alert-info">
                        <i class="bi bi-shield-check me-2"></i>
                        Two-Factor Authentication Required
                    </div>
                    <p class="text-muted mb-0" id="mfaMessage">Enter the 6-digit code from your authenticator app</p>
                </div>

                <form id="mfaForm" method="POST" action="javascript:void(0);">
                    @csrf
                    <!-- Hidden fields for client info (MFA step, if needed) -->
                    <input type="hidden" id="mfaClientUserAgent" name="client_user_agent" value="">
                    <input type="hidden" id="mfaClientPlatform" name="client_platform" value="">
                    <div class="form-group">
                        <label class="form-label" for="mfaCode">Verification Code</label>
                        <input type="text" class="form-control mfa-code-input" id="mfaCode" 
                               name="code" placeholder="000000" maxlength="6" required>
                    </div>

                    <input type="hidden" id="mfaAuthenticator" name="authenticator" value="">

                    <div class="form-group mb-3">
                        <button type="submit" class="btn btn-primary" id="mfaBtn">
                            <span class="button-text">Verify</span>
                            <span class="spinner-border d-none" role="status"></span>
                        </button>
                    </div>

                    <div class="text-center">
                        <button type="button" class="btn-link" id="backToLogin">
                            ← Back to Login
                        </button>
                    </div>
                </form>
            </div>

            <!-- Step 3: Success -->
            <div class="auth-step" id="stepSuccess">
                <div class="text-center">
                    <i class="bi bi-check-circle-fill success-icon"></i>
                    <h4 class="mb-3">Login Successful!</h4>
                    <p class="text-muted mb-4">Redirecting to dashboard...</p>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="footer-links">
                <hr>
                
                <div class="register-section">
                    <p class="text-muted">
                        <i class="bi bi-building"></i>
                        <span>Belum memiliki akun?</span>
                    </p>
                    <a href="{{ route('lead.register') }}" class="btn btn-success w-100">
                        <i class="bi bi-arrow-right-circle"></i>
                        <span>Register Organization</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Right Side End -->
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>

    <script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();

            const $loginBtn = $('#loginBtn');
            const $loadingOverlay = $('#loadingOverlay');
            const $errorAlert = $('#errorAlert');
            const $errorMessage = $('#errorMessage');

            // Show loading state
            $loadingOverlay.addClass('show');
            $loginBtn.prop('disabled', true);
            $errorAlert.removeClass('show');

            $.ajax({
                url: '{{ route('native.login.submit') }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        // Show success step
                        $('#stepCredentials').removeClass('active');
                        $('#stepSuccess').addClass('active');

                        // Animate progress bar and redirect
                        let progress = 0;
                        const interval = setInterval(function() {
                            progress += 10;
                            $('.progress-bar').css('width', progress + '%');
                            if (progress >= 100) {
                                clearInterval(interval);
                                window.location.href = response.redirect || '/';
                            }
                        }, 150);
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON && xhr.responseJSON.error 
                        ? xhr.responseJSON.error 
                        : 'An unexpected error occurred. Please try again.';
                    
                    $errorMessage.text(error);
                    $errorAlert.addClass('show');
                },
                complete: function() {
                    // Hide loading state
                    $loadingOverlay.removeClass('show');
                    $loginBtn.prop('disabled', false);
                }
            });
        });

        // Handle back button from MFA step if you implement it later
        $('#backToLogin').on('click', function() {
            $('#stepMfa').removeClass('active');
            $('#stepCredentials').addClass('active');
        });
    });
    </script>
</body>
    <script>
    // Collect client info and inject into hidden fields before form submit
    document.addEventListener('DOMContentLoaded', function() {
        var loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function() {
                document.getElementById('clientUserAgent').value = navigator.userAgent;
                document.getElementById('clientPlatform').value = navigator.platform || '';
            });
        }
        var mfaForm = document.getElementById('mfaForm');
        if (mfaForm) {
            mfaForm.addEventListener('submit', function() {
                document.getElementById('mfaClientUserAgent').value = navigator.userAgent;
                document.getElementById('mfaClientPlatform').value = navigator.platform || '';
            });
        }
        
        // Organization field hint functionality
        var orgField = document.getElementById('organization');
        if (orgField) {
            orgField.addEventListener('focus', function() {
                if (this.value === '') {
                    this.placeholder = 'Leave empty for admin access to carbon.super';
                }
            });
            
            orgField.addEventListener('blur', function() {
                if (this.value === '') {
                    this.placeholder = 'Enter your organization or leave empty for admin';
                }
            });
        }
    });
    </script>
</body>
</html>
