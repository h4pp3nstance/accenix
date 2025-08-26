@extends('layouts.guest')

@section('content')

<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow-sm w-100" style="max-width: 600px; margin: 2rem auto;">
        <div class="card-body p-5 text-center">
            <!-- Error Icon -->
            <div class="mb-4">
                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem;"></i>
            </div>

            <!-- Error Message -->
            <h2 class="text-warning mb-3">Registration Link Issue</h2>
            <div class="alert alert-warning text-start">
                <p class="mb-0">{{ $error_message }}</p>
            </div>

            <!-- Common Solutions -->
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <h5 class="card-title text-primary">💡 What can you do?</h5>
                    <div class="text-start">
                        <ul class="mb-0">
                            <li class="mb-2">Check if you clicked the correct link from your email</li>
                            <li class="mb-2">Make sure the link hasn't expired (valid for 72 hours)</li>
                            <li class="mb-2">Check if registration has already been completed</li>
                            <li class="mb-0">Contact our support team for assistance</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2">
                <a href="mailto:support@loccana.com?subject=Registration%20Link%20Issue" class="btn btn-primary">
                    <i class="bi bi-envelope me-2"></i>Contact Support Team
                </a>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-house me-2"></i>Back to Homepage
                </a>
            </div>

            <!-- Support Information -->
            <div class="mt-4 pt-4 border-top">
                <p class="text-muted small mb-2">
                    <strong>Need immediate assistance?</strong>
                </p>
                <p class="text-muted small mb-1">
                    📧 Email: <a href="mailto:support@loccana.com">support@loccana.com</a>
                </p>
                <p class="text-muted small mb-1">
                    📞 Phone: +62 21 1234 5678
                </p>
                <p class="text-muted small mb-0">
                    🕐 Business Hours: Monday - Friday, 9:00 AM - 6:00 PM (WIB)
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
