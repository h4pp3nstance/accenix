<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registration Successful - Loccana ERP System</title>
    <link rel="icon" href="{{ asset('/metronic/assets/media/logos/loccana-logos1.png') }}" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Custom Desktop-First Styles -->
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-dark: #2563eb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --success-light: #34d399;
            --success-bg: #ecfdf5;
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
        .success-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Left Side - Celebration & Branding */
        .celebration-section {
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
        
        .celebration-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .celebration-content {
            text-align: center;
            z-index: 2;
            max-width: 500px;
        }
        
        .success-icon-large {
            font-size: 6rem;
            color: #a7f3d0;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .celebration-logo {
            width: 120px;
            height: auto;
            margin-bottom: 2rem;
        }
        
        .celebration-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .celebration-subtitle {
            font-size: 1.125rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .stats-list {
            text-align: left;
            max-width: 400px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .stat-item i {
            font-size: 1.25rem;
            margin-right: 1rem;
            color: #a7f3d0;
        }
        
        /* Right Side - Success Content */
        .content-section {
            flex: 1.2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            background: white;
        }
        
        .content-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        
        .content-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .content-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .content-subtitle {
            font-size: 1rem;
            color: var(--text-muted);
        }
        
        /* Success Messages */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            border: none;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-info {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: var(--primary-blue);
            border-left: 4px solid var(--primary-blue);
        }
        
        /* Next Steps */
        .next-steps {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .next-steps h5 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .step-item:last-child {
            margin-bottom: 0;
        }
        
        .step-number {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-content h6 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .step-content p {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
        }
        
        /* Contact Information */
        .contact-info {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #f59e0b;
        }
        
        .contact-info h6 {
            font-size: 1rem;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 1rem;
        }
        
        .contact-info p {
            font-size: 0.875rem;
            color: #92400e;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .contact-info p:last-child {
            margin-bottom: 0;
        }
        
        .contact-info a {
            color: #92400e;
            text-decoration: none;
            font-weight: 500;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
        
        /* Footer Links */
        .footer-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .footer-links a {
            color: var(--success-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0 1rem;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: #059669;
            text-decoration: underline;
        }
        
        .footer-links .separator {
            margin: 0 0.5rem;
            color: var(--text-muted);
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .success-wrapper {
                flex-direction: column;
            }
            
            .celebration-section {
                min-height: 40vh;
                padding: 2rem;
            }
            
            .celebration-title {
                font-size: 2rem;
            }
            
            .content-section {
                padding: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .celebration-section {
                min-height: 30vh;
                padding: 1.5rem;
            }
            
            .content-section {
                padding: 1.5rem;
            }
            
            .content-title {
                font-size: 1.5rem;
            }
            
            .celebration-title {
                font-size: 1.75rem;
            }
            
            .success-icon-large {
                font-size: 4rem;
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
    <div class="success-wrapper">
        <!-- Left Side - Celebration & Stats -->
        <div class="celebration-section">
            <div class="celebration-content">
                <i class="bi bi-check-circle-fill success-icon-large"></i>
                
                <img src="{{ asset('/metronic/assets/media/logos/loccana.png') }}" 
                     alt="Loccana Logo" class="celebration-logo">
                
                <h1 class="celebration-title">Welcome to Loccana!</h1>
                <p class="celebration-subtitle">
                    Terima kasih telah mempercayai Loccana ERP. Anda telah bergabung dengan komunitas bisnis yang berkembang pesat!
                </p>
                
                <div class="stats-list">
                    <div class="stat-item">
                        <i class="bi bi-buildings"></i>
                        <span>500+ Perusahaan Telah Bergabung</span>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>40% Peningkatan Efisiensi Rata-rata</span>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-clock-history"></i>
                        <span>Setup Rata-rata < 2 Minggu</span>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-award"></i>
                        <span>99.9% Customer Satisfaction</span>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-headset"></i>
                        <span>24/7 Expert Support</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Success Content -->
        <div class="content-section">
            <div class="content-container">
                <!-- Content Header -->
                <div class="content-header">
                    <h1 class="content-title">Registration Successful!</h1>
                    <p class="content-subtitle">Thank you for your interest in our ERP System</p>
                </div>

                <!-- Success Messages -->
                <div class="alert alert-success text-center">
                    <i class="bi bi-check-circle me-2"></i>
                    Your registration has been processed successfully
                </div>

                <div class="alert alert-info text-center">
                    <i class="bi bi-gear-fill me-2"></i>
                    We're setting up your account and sending notifications in the background
                </div>

                <!-- Next Steps -->
                <div class="next-steps">
                    <h5 class="mb-3">What happens next?</h5>
                    
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h6>Account Setup (In Progress)</h6>
                            <p>Your organization and user account are being created automatically in our system.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h6>Email Confirmation</h6>
                            <p>You'll receive confirmation emails within the next few minutes.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h6>Team Contact</h6>
                            <p>Our business development team will contact you within <strong>24 hours</strong> to discuss your requirements.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h6>System Demo</h6>
                            <p>We'll schedule a personalized demo of our ERP system based on your business needs.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h6>Full Account Activation</h6>
                            <p>Once approved, your complete account will be activated with access credentials.</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="contact-info">
                    <h6><i class="bi bi-headset me-2"></i>Need immediate assistance?</h6>
                    <p>
                        <i class="bi bi-envelope me-2"></i>
                        Email: <a href="mailto:marketing@perusahaan.com">marketing@perusahaan.com</a>
                    </p>
                    <p>
                        <i class="bi bi-whatsapp me-2"></i>
                        WhatsApp: <a href="https://wa.me/6281234567890" target="_blank">+62 812-3456-7890</a>
                    </p>
                </div>

                <!-- Footer Links -->
                <div class="footer-links">
                    <a href="/">← Back to Home</a>
                    <span class="separator">•</span>
                    <a href="/about">Learn More About Us</a>
                    <span class="separator">•</span>
                    <a href="{{ route('native.login.form') }}">Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
</body>
</html>
