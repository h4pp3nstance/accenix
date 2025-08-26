<!DOCTYPE html>
<html lang="en">
        <head><base href="../../">
            <title>Loccana</title>
            <meta charset="utf-8" />
            <meta name="description" content="The most advanced Bootstrap Admin Theme on Themeforest trusted by 94,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue &amp; Laravel versions. Grab your copy now and get life-time updates for free." />
            <meta name="keywords" content="Metronic, bootstrap, bootstrap 5, Angular, VueJs, React, Laravel, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <meta property="og:locale" content="en_US" />
            <meta property="og:type" content="article" />
            <meta property="og:title" content="Metronic - Bootstrap 5 HTML, VueJS, React, Angular &amp; Laravel Admin Dashboard Theme" />
            <meta property="og:url" content="https://keenthemes.com/metronic" />
            <meta property="og:site_name" content="Keenthemes | Metronic" />
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <link rel="icon" type="image/gif" href="{{ asset('metronic/assets/media/logos/loccana-logos1.png') }}"/>
            <link rel="canonical" href="https://preview.keenthemes.com/metronic8" />
            <link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
            <!--begin::Fonts-->
            <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
            <link href="/metronic/assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
            <link href="/metronic/assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
        </head>
	    <body id="kt_body" class="{{ $bgdark ?? 'bg-body' }}">
        @yield('content')

            <script>var hostUrl = "/metronic/assets/";</script>
            <script src="/metronic/assets/plugins/global/plugins.bundle.js"></script>
            <script src="/metronic/assets/js/scripts.bundle.js"></script>
            <script src="/metronic/assets/js/custom/modals/create-account.js"></script>
            @yield('js')
        </body>
    </html>
