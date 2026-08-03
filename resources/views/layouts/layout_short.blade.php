<!DOCTYPE html>
<html dir="ltr" lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="AVG" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="sidebar_mode" content="{{ auth()->id() ? auth()->user()?->setting?->read('sidebar_mode') ?? 'full' : 'full'}}" />
    @if(auth()->check())<meta name="_token" content="{{ auth()->user()->ajax_token }}" />@endif
    <title>
        @section('title') {{ $title_force ?? "OSMO AVG: " . ($title ?? '') }} @show
    </title>

    <!-- Favicon icon -->
    <link rel="icon" type="image/svg+xml" href="/images/logo/logo_letter.svg" />

    <!-- Custom CSS -->
    <link href="/dist/css/style.css" rel="stylesheet" />
    <link href="/css/app.css" rel="stylesheet" />
    <link href="/css/fix.css" rel="stylesheet" />

    <!-- Modules -->
    <link href="/assets/libs/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="/assets/extra-libs/toastr/dist/build/toastr.min.css" rel="stylesheet" type="text/css" />
    <link href="/assets/libs/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet" type="text/css" />

    <link href="/assets/custom/jquery.ui/jquery-ui.css" rel="stylesheet" type="text/css" />

    @yield('styles')

    <style>
        #toasts {
            z-index: 10;
            position: fixed;
            right: 7px;
            bottom: 15px;
        }
        #toasts .toast + .toast {
            margin-top: 10px;
        }
    </style>
</head>
<body data-theme="{{ config('theme.theme') }}">
@yield('body')

{{--<!-- библиотека -->--}}
<script src="/assets/libs/jquery/dist/jquery.min.js"></script>
<script src="/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

{{--<!-- настройки для отображения-->--}}
{{--<script src="/dist/js/app.min.js"></script>--}}
{{--<script src="/dist/js/app.init.js"></script>--}}

{{--<!-- настройки для отображения-->--}}
{{--<script src="/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>--}}
{{--<script src="/assets/extra-libs/sparkline/sparkline.js"></script>--}}

{{--<!--Wave Effects -->--}}
{{--<script src="/dist/js/waves.js"></script>--}}

{{--<!--Menu sidebar -->--}}
{{--<script src="/dist/js/sidebarmenu.js"></script>--}}

{{--<!--Custom JavaScript -->--}}
{{--<script src="/dist/js/feather.min.js"></script>--}}
{{--<script src="/dist/js/custom.min.js"></script>--}}


{{--<script src="/js/include.js"></script> <!-- библиотека -->--}}
<script src="/js/app.js"></script> <!-- общий файл системный -->
<script src="/js/pages.js"></script> <!-- персонально для страниц -->

<!-- Modules-->
<script src="/dist/modules/moment/min/moment.min.js"></script>
<script src="/assets/libs/select2/dist/js/select2.min.js"></script>
<script src="/assets/libs/block-ui/jquery.blockUI.js"></script>
<script src="/assets/libs/sweetalert2/dist/sweetalert2.all.js"></script>
<script src="/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="/assets/extra-libs/toastr/dist/build/toastr.min.js"></script>
<script src="/assets/custom/jquery.ui/jquery-ui.min.js"></script>



<script src="/dist/modules/visibilityjs/lib/visibility.fallback.js"></script> <!-- общий файл системный -->
<script src="/dist/modules/visibilityjs/lib/visibility.core.js"></script> <!-- общий файл системный -->
<script src="/dist/modules/visibilityjs/lib/visibility.timers.js"></script>

@yield('js')
</body>
</html>


