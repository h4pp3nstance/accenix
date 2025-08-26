<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Distributor & Sales System</title>
    <link rel="shortcut icon" href="#" type="image/x-icon">
    <link rel="shortcut icon" href="#" type="image/png">
    <link rel="icon" href="{{ asset('assets/img/icon/icon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/datatables/dataTables.dataTables.min.css') }}">
    <style>
        #loading-overlay {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: row;
            background: rgba(0, 0, 0, .5);
            padding: 20px;
            border-radius: 10px;
            color: #fff;
            z-index: 9999;
            display: none;
        }

        #loading-overlay p {
            margin: 0;
            font-size: 16px;
        }
    </style>
    @stack('styles')
</head>

<body>
    <div id="app">
        <x-sidebar></x-sidebar>
        <div id="main" class="layout-navbar navbar-fixed">
            <x-navbar></x-navbar>
            @yield('content')
            <x-footer></x-footer>
        </div>
    </div>
    @include('loading.loading')
    <script src="{{ asset('assets/js/jquery-3.7.1.js') }}"></script>
    <script src="{{ asset('assets/js/chart.js') }}"></script>
    <script src="{{ asset('assets/js/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/js/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert/sweetalert.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert/functions.js') }}"></script>
    <script src="{{ asset('assets/js/userroles-helpers.js') }}"></script>
    
    <!-- Token Validation Script - Silent Background Validation -->
    <script>
    $(document).ready(function() {
        let isValidating = false;
        let lastValidationTime = 0;
        const VALIDATION_INTERVAL = 300000; // 5 minutes instead of 15 seconds
        const MIN_VALIDATION_GAP = 30000; // Minimum 30 seconds between validations
        
        // Check token validity every 5 minutes (much less intrusive)
        setInterval(function() {
            silentTokenCheck();
        }, VALIDATION_INTERVAL);
        
        function silentTokenCheck() {
            const now = Date.now();
            
            // Prevent too frequent validations
            if (isValidating || (now - lastValidationTime) < MIN_VALIDATION_GAP) {
                return;
            }
            
            isValidating = true;
            lastValidationTime = now;
            
            $.ajax({
                url: '{{ route('validate.token') }}',
                type: 'GET',
                timeout: 5000,
                cache: false,
                silent: true, // Custom flag to indicate this is a background operation
                success: function(response) {
                    if (!response.valid) {
                        // Token invalid, show alert and redirect
                        alert('Your session has expired. You will be redirected to login.');
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = '/';
                        }
                    }
                    // If valid, do nothing (silent success)
                },
                error: function(xhr, status, error) {
                    // Only handle authentication errors
                    if (xhr.status === 401 || xhr.status === 403) {
                        alert('Your session has expired. You will be redirected to login.');
                        window.location.href = '/';
                    }
                    // Ignore network errors and other issues (silent failure)
                },
                complete: function() {
                    isValidating = false;
                }
            });
        }
        
        // Check token when page regains focus (user returns to tab)
        $(window).on('focus', function() {
            setTimeout(silentTokenCheck, 1000);
        });
        
        // Validate on form submissions (important actions)
        $(document).on('submit', 'form', function() {
            silentTokenCheck();
        });
        
        // Initial token check when page loads
        setTimeout(function() {
            silentTokenCheck();
        }, 2000); // Check 2 seconds after page load
    });
    </script>
    
    @stack('scripts')
</body>

</html>
