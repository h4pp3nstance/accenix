@extends('layouts.guest')

@section('content')

<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow-sm w-100" style="max-width: 600px; margin: 2rem auto;">
        <div class="card-body p-5 text-center">
            <!-- Success Icon -->
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            </div>

            <!-- Success Message -->
            <h2 class="text-success mb-3">Registration Completed!</h2>
            <p class="lead mb-4">
                Welcome to Accenix, <strong>{{ $username }}</strong>!
            </p>

            <!-- Account Details -->
            <div class="alert alert-info text-start">
                <h5 class="alert-heading">Your Account Details</h5>
                <hr>
                <p class="mb-1"><strong>Company:</strong> {{ $company_name }}</p>
                <p class="mb-1"><strong>Username:</strong> {{ $username }}</p>
                <p class="mb-1"><strong>User ID:</strong> {{ $user_id }}</p>
                <p class="mb-0"><strong>Registration Date:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
            </div>

            <!-- Next Steps -->
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <h5 class="card-title text-primary">🚀 What's Next?</h5>
                    <div class="text-start">
                        <ol class="mb-0">
                            <li class="mb-2">Check your email for a welcome message with important information</li>
                            <li class="mb-2">Log in to your account using your new credentials</li>
                            <li class="mb-2">Complete your profile setup</li>
                            <li class="mb-0">Start exploring Accenix's features</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2">
                <a href="{{ route('native.login.form') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log In to Your Account
                </a>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-house me-2"></i>Back to Homepage
                </a>
            </div>

            <!-- Support Information -->
            <div class="mt-4 pt-4 border-top">
                <p class="text-muted small mb-2">
                    <strong>Need help getting started?</strong>
                </p>
                <p class="text-muted small mb-0">
                    Contact our support team at 
                    <a href="mailto:support@loccana.com">support@loccana.com</a> 
                    or call +62 21 1234 5678
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
