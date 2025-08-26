<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register Your Organization - Accenix IAM</title>
    <link rel="icon" href="{{ asset('/metronic/assets/media/logos/loccana-logos1.png') }}" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom Desktop-First Styles -->
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-dark: #2563eb;
            --primary-blue-light: #60a5fa;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --error-color: #ef4444;
            --bg-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Desktop Split Layout */
        .register-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Left Side - Branding & Info */
        .brand-section {
            flex: 1;
            background: var(--bg-gradient);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .brand-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .brand-content {
            text-align: center;
            z-index: 2;
            max-width: 500px;
        }
        
        .brand-logo {
            width: 180px;
            height: auto;
            margin-bottom: 2rem;
        }
        
        .brand-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .brand-subtitle {
            font-size: 1.125rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .benefit-list {
            text-align: left;
            max-width: 400px;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .benefit-item i {
            font-size: 1.25rem;
            margin-right: 1rem;
            color: #a7f3d0;
        }
        
        /* Right Side - Registration Form */
        .form-section {
            flex: 1.2;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 2rem 3rem;
            background: white;
            overflow-y: auto;
            max-height: 100vh;
        }
        
        .form-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-top: 1rem;
        }
        
        .form-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .form-subtitle {
            font-size: 1rem;
            color: var(--text-muted);
        }
        
        /* Form Elements */
        .form-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--success-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            width: 100%;
            min-height: 2.75rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: white;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--success-color);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            transform: translateY(-1px);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
            font-style: italic;
        }
        
        .form-control:invalid {
            border-color: var(--error-color);
        }
        
        .form-control:valid {
            border-color: var(--success-color);
        }
        
        .form-control.is-invalid {
            border-color: var(--error-color);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .form-control.is-valid {
            border-color: var(--success-color);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        textarea.form-control {
            min-height: 4rem;
            resize: vertical;
        }
        
        select.form-control {
            cursor: pointer;
        }
        
        .btn-register {
            width: 100%;
            height: 3rem;
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            border: none;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        /* Footer Links */
        .form-footer {
            text-align: center;
            margin-top: 2rem;
            margin-bottom: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .form-footer a {
            color: var(--success-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .form-footer a:hover {
            color: #059669;
            text-decoration: underline;
        }
        
        /* Required field indicator */
        .text-danger {
            color: var(--error-color);
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .register-wrapper {
                flex-direction: column;
            }
            
            .brand-section {
                min-height: 40vh;
                padding: 2rem;
            }
            
            .brand-title {
                font-size: 2rem;
            }
            
            .form-section {
                padding: 2rem;
                max-height: none;
            }
        }
        
        @media (max-width: 768px) {
            .brand-section {
                min-height: 30vh;
                padding: 1.5rem;
            }
            
            .form-section {
                padding: 1.5rem;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .brand-title {
                font-size: 1.75rem;
            }
        }
        
        /* Utilities */
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }
        .me-2 { margin-right: 0.5rem; }
    </style>
</head>

<body>
    <div class="register-wrapper">
        <!-- Left Side - Brand & Benefits -->
        <div class="brand-section">
            <div class="brand-content">
                <img src="{{ asset('/metronic/assets/media/logos/loccana.png') }}" 
                     alt="Accenix Logo" class="brand-logo">
                
                <h1 class="brand-title">Start Your IAM Journey</h1>
                <p class="brand-subtitle">
                    Bergabunglah dengan organisasi yang menggunakan Accenix untuk mengelola identitas, akses, dan kepatuhan secara terpusat.
                </p>
                
                <div class="benefit-list">
                    <div class="benefit-item">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Single Sign-On (SSO) untuk aplikasi internal & cloud</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-people"></i>
                        <span>Provisioning & deprovisioning otomatis (SCIM)</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-shield-lock"></i>
                        <span>Pengelolaan peran (RBAC) dan kebijakan akses</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-shield-check"></i>
                        <span>Multi-Factor Authentication (MFA) dan kebijakan adaptif</span>
                    </div>
                    <div class="benefit-item">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Audit, logging, dan laporan kepatuhan</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Registration Form -->
        <div class="form-section">
            <div class="form-container">
                <!-- Form Header -->
                <div class="form-header">
                    <h1 class="form-title">Register Your Business</h1>
                    <p class="form-subtitle">Tell us about your business and we'll get back to you ASAP</p>
                </div>

                <!-- Error Alert -->
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <div class="mb-0">
                            @foreach ($errors->all() as $error)
                                @if (strpos($error, 'already registered') !== false)
                                    <div style="margin-bottom: 1rem;">
                                        <strong><i class="bi bi-building-exclamation me-1"></i>Organization Name Already Exists</strong>
                                        <div class="mt-2" style="font-size: 0.95rem;">
                                            Organization name <strong>'{{ old('company_name', 'PT. Aneka Tambang') }}'</strong> is already registered.
                                        </div>
                                    </div>
                                    <div class="alert alert-info mt-2 mb-0" style="font-size: 0.875rem; border-left: 4px solid #17a2b8;">
                                        <strong><i class="bi bi-lightbulb me-1"></i>Try these alternatives:</strong>
                                        <ul class="mb-0 mt-1" style="padding-left: 1.2rem;">
                                            <li>Add your city or branch name: <code>"{{ old('company_name', 'PT. Aneka Tambang') }} Jakarta"</code></li>
                                            <li>Use your complete legal entity name</li>
                                            <li>Contact our support team if this is genuinely your organization</li>
                                        </ul>
                                    </div>
                                @else
                                    <div class="mb-2">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $error }}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Registration Form -->
                <form method="POST" action="{{ route('lead.submit') }}" id="registrationForm">
                    @csrf
                    
                    <!-- Company Information -->
                    <div class="mb-4">
                        <h5 class="form-section-title">Company Information</h5>
                        
                        <div class="form-group">
                            <label class="form-label" for="company_name">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   placeholder="e.g., PT. ABC Manufacturing" value="{{ old('company_name') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="company_address">Company Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="company_address" name="company_address" rows="3" 
                                      placeholder="Enter your complete company address including city and postal code" required>{{ old('company_address') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="business_type">Business Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="business_type" name="business_type" required>
                                <option value="">Select your business type</option>
                                <option value="manufacturing" {{ old('business_type') == 'manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                                <option value="trading" {{ old('business_type') == 'trading' ? 'selected' : '' }}>Trading</option>
                                <option value="retail" {{ old('business_type') == 'retail' ? 'selected' : '' }}>Retail</option>
                                <option value="wholesale" {{ old('business_type') == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                                <option value="service" {{ old('business_type') == 'service' ? 'selected' : '' }}>Service</option>
                                <option value="construction" {{ old('business_type') == 'construction' ? 'selected' : '' }}>Construction</option>
                                <option value="other" {{ old('business_type') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <!-- Additional field for "Other" business type -->
                        <div class="form-group" id="other_business_type_group" style="display: none;">
                            <label class="form-label" for="other_business_type">Please specify your business type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="other_business_type" name="other_business_type" 
                                   placeholder="e.g., Agriculture, Mining, Technology" value="{{ old('other_business_type') }}">
                        </div>
                    </div>

                    <!-- Contact Person -->
                    <div class="mb-4">
                        <h5 class="form-section-title">Contact Person</h5>
                        
                        <div class="form-group">
                            <label class="form-label" for="contact_name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                   placeholder="e.g., John Doe" value="{{ old('contact_name') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contact_position">Position <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_position" name="contact_position" 
                                   placeholder="e.g., CEO, Manager, Director" value="{{ old('contact_position') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contact_email">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                   placeholder="e.g., john.doe@company.com" value="{{ old('contact_email') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contact_phone">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                   placeholder="e.g., +62 812-3456-7890" value="{{ old('contact_phone') }}" required>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="mb-4">
                        <h5 class="form-section-title">Additional Information</h5>
                        
                        <div class="form-group">
                            <label class="form-label" for="current_system">Current System Used</label>
                            <select class="form-control" id="current_system" name="current_system">
                                <option value="">-- Select current system (optional) --</option>
                                <option value="manual" {{ old('current_system') == 'manual' ? 'selected' : '' }}>Manual/Excel Spreadsheets</option>
                                <option value="accounting_software" {{ old('current_system') == 'accounting_software' ? 'selected' : '' }}>Accounting Software (Accurate, MYOB, etc.)</option>
                                <option value="erp_system" {{ old('current_system') == 'erp_system' ? 'selected' : '' }}>Other ERP System</option>
                                <option value="custom_system" {{ old('current_system') == 'custom_system' ? 'selected' : '' }}>Custom/In-house System</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="specific_needs">Specific Needs or Requirements</label>
                            <textarea class="form-control" id="specific_needs" name="specific_needs" rows="4" 
                                      placeholder="Tell us about your specific business challenges, pain points, or what you hope to achieve with an ERP system. For example: inventory tracking issues, financial reporting needs, integration requirements, etc.">{{ old('specific_needs') }}</textarea>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <button type="submit" class="btn-register" id="registerBtn">
                            <span class="button-text">
                                <i class="bi bi-rocket-takeoff me-2"></i>
                                Start Your ERP Journey
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Footer Links -->
                <div class="form-footer">
                    <a href="{{ route('native.login.form') }}">
                        ← Already have an account? Sign in here
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>

    <script>
    $(document).ready(function() {
        // Handle business type "Other" selection
        function toggleOtherBusinessType() {
            const businessType = $('#business_type').val();
            const otherGroup = $('#other_business_type_group');
            const otherInput = $('#other_business_type');
            
            if (businessType === 'other') {
                otherGroup.slideDown(300);
                otherInput.prop('required', true);
            } else {
                otherGroup.slideUp(300);
                otherInput.prop('required', false);
                otherInput.val(''); // Clear the value when hidden
            }
        }
        
        // Initial check on page load (for old values)
        toggleOtherBusinessType();
        
        // Handle change event
        $('#business_type').on('change', toggleOtherBusinessType);
        
        // Handle form submission
        $('#registrationForm').on('submit', function(e) {
            const $registerBtn = $('#registerBtn');
            
            // Validate "Other" field if business type is "other"
            const businessType = $('#business_type').val();
            const otherBusinessType = $('#other_business_type').val().trim();
            
            if (businessType === 'other' && otherBusinessType === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please specify your business type when selecting "Other".',
                    confirmButtonColor: '#10b981'
                });
                $('#other_business_type').focus();
                return false;
            }
            
            // Basic validation for required fields
            let hasError = false;
            const requiredFields = [
                {field: '#company_name', name: 'Company Name'},
                {field: '#company_address', name: 'Company Address'},
                {field: '#business_type', name: 'Business Type'},
                {field: '#contact_name', name: 'Full Name'},
                {field: '#contact_position', name: 'Position'},
                {field: '#contact_email', name: 'Email Address'},
                {field: '#contact_phone', name: 'Phone Number'}
            ];
            
            requiredFields.forEach(function(item) {
                const value = $(item.field).val().trim();
                if (value === '') {
                    hasError = true;
                    $(item.field).addClass('is-invalid');
                } else {
                    $(item.field).removeClass('is-invalid');
                }
            });
            
            if (hasError) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Please Complete All Required Fields',
                    text: 'All fields marked with (*) are required.',
                    confirmButtonColor: '#10b981'
                });
                return false;
            }
            
            // Show loading state
            $registerBtn.prop('disabled', true);
            $registerBtn.html('<i class="bi bi-hourglass-split me-2"></i>Processing Your Registration...');
            
            // Form will submit normally, but with loading state
        });
        
        // Real-time validation
        $('input[required], select[required], textarea[required]').on('blur', function() {
            const value = $(this).val().trim();
            if (value === '') {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
    });
    </script>
</body>
</html>
