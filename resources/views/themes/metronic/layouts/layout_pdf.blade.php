@php use App\Support\UiTheme; @endphp
<link rel="stylesheet" href="{{ url(ltrim(UiTheme::asset('plugins/global/plugins.bundle.css'), '/')) }}">
<link rel="stylesheet" href="{{ url(ltrim(UiTheme::asset('css/style.bundle.css'), '/')) }}">
<link rel="stylesheet" href="{{ url('css/app.css') }}">
<link rel="stylesheet" href="{{ url('css/fix.css') }}">
<link rel="stylesheet" href="{{ url('metronic/css/osmo-compat.css') }}">

<style>
    body { background: white !important; }
    .btn, .d-print-none { display: none; }
    .card { box-shadow: none !important; border: 1px solid #eff2f5 !important; }
</style>

{!! $html !!}
