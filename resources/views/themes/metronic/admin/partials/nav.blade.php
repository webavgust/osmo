{{--
    Навигация админ-панели (patch v28): один partial на все разделы.

    Подключается первой строкой @section('content') каждой страницы раздела:
    @include('admin.partials.nav'). Пункт показывается, только если его маршрут
    уже заведён — «Константы» появятся сами, когда этап C добавит
    admin.consts.index.
--}}
@php
    $admin_nav = [
        ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'fa-users', 'title' => 'Пользователи'],
        ['route' => 'admin.consts.index', 'match' => 'admin.consts.*', 'icon' => 'fa-sliders', 'title' => 'Константы'],
    ];
@endphp

<ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-semibold mb-6">
    @foreach($admin_nav as $item)
        @continue(!\Illuminate\Support\Facades\Route::has($item['route']))

        <li class="nav-item">
            <a href="{{ route($item['route']) }}"
               class="nav-link text-active-primary pb-3 me-6 @if(request()->routeIs($item['match'])) active @endif">
                <i class="fa-light {{ $item['icon'] }} me-2"></i>{{ $item['title'] }}
            </a>
        </li>
    @endforeach
</ul>
