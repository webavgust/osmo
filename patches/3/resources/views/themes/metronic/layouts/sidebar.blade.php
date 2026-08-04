{{-- Левое меню: логотип, панель действий (бывший верхний бар), профиль, разделы --}}
<div id="kt_app_sidebar" class="app-sidebar flex-column"
     data-kt-drawer="true"
     data-kt-drawer-name="app-sidebar"
     data-kt-drawer-activate="{default: true, lg: false}"
     data-kt-drawer-overlay="true"
     data-kt-drawer-width="330px"
     data-kt-drawer-direction="start"
     data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">

    {{-- Логотип --}}
    <div class="app-sidebar-logo px-5" id="kt_app_sidebar_logo">
        <a href="{{ route('dashboard.index') }}" class="app-sidebar-logo-link">
            <img alt="OSMO" src="/images/logo/logo_letter.svg" class="h-32px app-sidebar-logo-default" />
            <img alt="OSMO" src="/images/logo/logo_letter.svg" class="h-28px app-sidebar-logo-minimize" />
        </a>

        <div id="kt_app_sidebar_toggle"
             class="app-sidebar-toggle btn btn-icon btn-sm h-32px w-32px"
             data-kt-toggle="true"
             data-kt-toggle-state="active"
             data-kt-toggle-target="body"
             data-kt-toggle-name="app-sidebar-minimize"
             title="Свернуть меню">
            <i class="fa-light fa-angles-left fs-4"></i>
        </div>
    </div>

    {{-- Панель действий (перенесена из верхнего бара) --}}
    <div class="app-sidebar-navbar px-5" id="kt_app_sidebar_navbar">

        <a href="{{ route('reminder.index') }}" class="btn btn-icon btn-sidebar-action"
           data-bs-toggle="tooltip" data-bs-placement="bottom" title="Напоминания">
            <i class="fa-duotone fa-alarm-exclamation fs-3"></i>
        </a>

        <a href="{{ route('calendar.index') }}" class="btn btn-icon btn-sidebar-action"
           data-bs-toggle="tooltip" data-bs-placement="bottom" title="Календарь">
            <i class="fa-duotone fa-calendar-days fs-3"></i>
        </a>

        {{-- Уведомления (id и классы нужны spider_tick) --}}
        <div class="position-relative @if(!$global['notifies']['count']) d-none @endif" id="notifies">
            <div class="btn btn-icon btn-sidebar-action" data-bs-toggle="dropdown" aria-expanded="false" title="Уведомления">
                <i class="fa-duotone fa-message fs-3"></i>
                <div class="notify">
                    <span class="heartbit @if(empty($global['notifies']['new'])) d-none @endif"></span>
                    <span class="point"></span>
                </div>
            </div>
            <div class="dropdown-menu dropdown-menu-start mailbox w-325px py-3">
                <div class="loader text-center">
                    <div class="spinner-border text-primary mt-4 mb-3" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div class="notices_shell"></div>
            </div>
        </div>

        {{-- Светлая / тёмная тема --}}
        <div class="position-relative">
            <a href="#" class="btn btn-icon btn-sidebar-action"
               data-kt-menu-trigger="click"
               data-kt-menu-attach="parent"
               data-kt-menu-placement="bottom-start"
               title="Тема">
                <i class="fa-duotone fa-sun-bright fs-3 theme-light-show"></i>
                <i class="fa-duotone fa-moon-stars fs-3 theme-dark-show"></i>
            </a>
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-3 fs-6 w-175px"
                 data-kt-menu="true" data-kt-element="theme-mode-menu">
                <div class="menu-item px-3 my-0">
                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                        <span class="menu-icon" data-kt-element="icon"><i class="fa-light fa-sun-bright fs-4"></i></span>
                        <span class="menu-title">Светлая</span>
                    </a>
                </div>
                <div class="menu-item px-3 my-0">
                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                        <span class="menu-icon" data-kt-element="icon"><i class="fa-light fa-moon-stars fs-4"></i></span>
                        <span class="menu-title">Тёмная</span>
                    </a>
                </div>
                <div class="menu-item px-3 my-0">
                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                        <span class="menu-icon" data-kt-element="icon"><i class="fa-light fa-desktop fs-4"></i></span>
                        <span class="menu-title">Как в системе</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Переключатель оформления --}}
        @include('components.ui.theme_switch')

        {{-- Выход --}}
        <a href="{{ route('logout') }}" class="btn btn-icon btn-sidebar-action ms-auto"
           data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ __('header.logout') }}">
            <i class="fa-light fa-power-off fs-3"></i>
        </a>
    </div>

    {{-- Профиль --}}
    <div class="app-sidebar-user px-5" id="kt_app_sidebar_user">
        <div class="d-flex align-items-center w-100 cursor-pointer"
             data-kt-menu-trigger="click"
             data-kt-menu-attach="parent"
             data-kt-menu-placement="bottom-start">
            <div class="symbol symbol-40px me-3 flex-shrink-0">
                <img src="{{ asset(auth()->user()->avatar()) }}" alt="{{ auth()->user()->name }}" class="rounded-circle" />
            </div>
            <div class="app-sidebar-user-info overflow-hidden">
                <div class="fw-bold text-truncate app-sidebar-user-name">{{ auth()->user()->name }}</div>
                <div class="fs-8 text-truncate app-sidebar-user-email">{{ auth()->user()->email }}</div>
            </div>
            <i class="fa-light fa-chevron-down fs-7 ms-auto app-sidebar-user-caret"></i>
        </div>

        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
            <div class="menu-item px-5">
                <a href="{{ route('users.view', auth()->user()) }}" class="menu-link px-5">{{ __('header.my_profile') }}</a>
            </div>

            <div class="menu-item px-5">
                <a href="{{ route('notify.list') }}" class="menu-link px-5">История уведомлений</a>
            </div>

            @if(auth()->user()->isAdmin() || auth()->user()->silentAdmin())
                <div class="menu-item px-5">
                    <x-ui.a.ajax class="menu-link px-5" url="{{ route('api.access.refresh') }}" method="post" :data="['a' => 1]" reload="1">
                        Обновить доступы
                    </x-ui.a.ajax>
                </div>
                <div class="menu-item px-5">
                    <span class="menu-link px-5 text-muted fs-7">ID: {{ auth()->id() }}</span>
                </div>
            @endif

            <div class="separator my-2"></div>

            <div class="menu-item px-5">
                <a href="{{ route('logout') }}" class="menu-link px-5 text-danger">{{ __('header.logout') }}</a>
            </div>
        </div>
    </div>

    {{-- Разделы --}}
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-2"
             data-kt-scroll="true"
             data-kt-scroll-activate="true"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_navbar, #kt_app_sidebar_user"
             data-kt-scroll-wrappers="#kt_app_sidebar_menu"
             data-kt-scroll-offset="5px">

            <x-sidebar.menu :tree="$menu_tree"></x-sidebar.menu>
        </div>
    </div>
</div>
