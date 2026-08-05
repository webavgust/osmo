@php use App\Support\UiTheme; @endphp
<!DOCTYPE html>
<html dir="ltr" lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="AVG" />
    <meta name="robots" content="noindex,nofollow" />
    @php $sidebar_mode = auth()->id() ? (auth()->user()?->setting?->read('sidebar_mode') ?? 'full') : 'full'; @endphp
    <meta name="sidebar_mode" content="{{ $sidebar_mode }}" />
    @if(auth()->check())<meta name="_token" content="{{ auth()->user()->ajax_token }}" />@endif
    <title>
        @section('title') {{ $title_force ?? "OSMO AVG: " . ($title ?? '') }} @show
    </title>

    <link rel="icon" type="image/svg+xml" href="/images/logo/logo_letter.svg" />

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

    {{-- Metronic 8.2.1 --}}
    <link href="{{ UiTheme::asset('plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ UiTheme::asset('css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

    {{-- Библиотеки старого фронта, которых нет в бандле Metronic --}}
    @foreach((array) UiTheme::config('legacy_css', []) as $css)
        <link href="{{ $css }}" rel="stylesheet" type="text/css" />
    @endforeach

    {{-- Font Awesome Pro и прочее из config/ui.php --}}
    @foreach((array) UiTheme::config('extra_css', []) as $css)
        <link href="{{ $css }}" rel="stylesheet" type="text/css" />
    @endforeach

    {{-- Прикладные стили проекта --}}
    <link href="/css/app.css" rel="stylesheet" />
    <link href="/css/fix.css" rel="stylesheet" />

    {{-- Левое меню (в бандле demo48 стилей app-sidebar нет — они здесь) --}}
    <link href="/metronic/css/osmo-sidebar.css" rel="stylesheet" />

    {{-- Слой совместимости: MaterialPro-классы поверх Metronic --}}
    <link href="/metronic/css/osmo-compat.css" rel="stylesheet" />
    <link href="/metronic/css/osmo-compat-pages.css" rel="stylesheet" />

    @yield('styles')
</head>

<body id="kt_app_body"
      data-kt-app-toolbar-enabled="true"
      @if($sidebar_mode === 'mini') data-kt-app-sidebar-minimize="on" @endif
      class="app-default @if(!empty($ui_theme_native)) theme-metronic @endif">

<script>
    var defaultThemeMode = "light"; var themeMode;
    if (document.documentElement) {
        if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
            themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
        } else {
            themeMode = localStorage.getItem("data-bs-theme") !== null ? localStorage.getItem("data-bs-theme") : defaultThemeMode;
        }
        if (themeMode === "system") {
            themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    }
    // состояние свёрнутого меню запоминаем на устройстве
    if (localStorage.getItem("osmo_sidebar_minimize") === "on") {
        document.body && document.body.setAttribute("data-kt-app-sidebar-minimize", "on");
    }
</script>

@yield('body')

<script>var hostUrl = "{{ UiTheme::config('assets') }}/";</script>

{{-- Metronic: jquery, bootstrap, select2, sweetalert2, dropzone и т.д. --}}
<script src="{{ UiTheme::asset('plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ UiTheme::asset('js/scripts.bundle.js') }}"></script>

{{-- Страховка: если в бандле не оказалось jquery/bootstrap/select2 — грузим старые --}}
<script>
    if (typeof jQuery === 'undefined') {
        document.write('<scr' + 'ipt src="/assets/libs/jquery/dist/jquery.min.js"></scr' + 'ipt>');
    }
</script>
<script>
    if (typeof bootstrap === 'undefined') {
        document.write('<scr' + 'ipt src="/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></scr' + 'ipt>');
    }
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 === 'undefined') {
        document.write('<scr' + 'ipt src="/assets/libs/select2/dist/js/select2.min.js"></scr' + 'ipt>');
        document.write('<link rel="stylesheet" href="/assets/libs/select2/dist/css/select2.min.css" />');
    }
    if (typeof Swal === 'undefined') {
        document.write('<scr' + 'ipt src="/assets/libs/sweetalert2/dist/sweetalert2.all.js"></scr' + 'ipt>');
    }
</script>

{{-- Библиотеки старого фронта --}}
@foreach((array) UiTheme::config('legacy_js', []) as $js)
    <script src="{{ $js }}"></script>
@endforeach
<script>
    if (typeof toastr === 'undefined') {
        document.write('<scr' + 'ipt src="/assets/extra-libs/toastr/dist/build/toastr.min.js"></scr' + 'ipt>');
    }
</script>

{{-- Мост совместимости: заглушки MaterialPro + темизация плагинов --}}
<script src="/metronic/js/osmo-metronic.js"></script>

{{-- Скрипты проекта --}}
<script src="/js/app.js"></script>
<script src="/js/pages.js"></script>

@yield('js')
</body>
</html>
