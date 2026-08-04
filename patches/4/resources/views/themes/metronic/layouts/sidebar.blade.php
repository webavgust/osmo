{{-- Левое меню: логотип, профиль с подменю действий, разделы --}}
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
        <a href="{{ route('dashboard.index') }}" class="app-sidebar-logo-link h-100 d-flex align-items-center">
            <img alt="OSMO" src="/images/logo/logo_letter.svg" class="h-32px app-sidebar-logo-default h-75" />
            <img alt="OSMO" src="/images/logo/logo_letter.svg" class="h-28px app-sidebar-logo-minimize h-50" />
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

    {{-- Профиль: все действия собраны в его подменю --}}
    <div class="app-sidebar-user px-5" id="kt_app_sidebar_user">
        <div class="d-flex align-items-center w-100 cursor-pointer"
             data-kt-menu-trigger="click"
             data-kt-menu-attach="parent"
             data-kt-menu-placement="bottom-start">
            <div class="symbol symbol-40px me-3 flex-shrink-0 position-relative">
                <img src="{{ asset(auth()->user()->avatar()) }}" alt="{{ auth()->user()->name }}" class="rounded-circle" />

                {{-- Индикатор новых уведомлений (id и классы нужны spider_tick) --}}
                <span id="notifies" class="@if(!$global['notifies']['count']) d-none @endif">
                    <span class="notify">
                        <span class="heartbit @if(empty($global['notifies']['new'])) d-none @endif"></span>
                        <span class="point"></span>
                    </span>
                </span>
            </div>
            <div class="app-sidebar-user-info overflow-hidden">
                <div class="fw-bold text-truncate app-sidebar-user-name">{{ auth()->user()->name }}</div>
                <div class="fs-8 text-truncate app-sidebar-user-email">{{ auth()->user()->email }}</div>
            </div>
            <i class="fa-light fa-chevron-down fs-7 ms-auto app-sidebar-user-caret"></i>
        </div>

        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-3 fs-6 w-275px" data-kt-menu="true">

            <div class="menu-item px-3">
                <a href="{{ route('users.view', auth()->user()) }}" class="menu-link px-3">
                    <span class="menu-icon"><i class="fa-light fa-user fs-5"></i></span>
                    <span class="menu-title">{{ __('header.my_profile') }}</span>
                </a>
            </div>

            <div class="separator my-2"></div>

            <div class="menu-item px-3">
                <a href="{{ route('reminder.index') }}" class="menu-link px-3">
                    <span class="menu-icon"><i class="fa-light fa-alarm-exclamation fs-5"></i></span>
                    <span class="menu-title">Напоминания</span>
                </a>
            </div>

            <div class="menu-item px-3">
                <a href="{{ route('calendar.index') }}" class="menu-link px-3">
                    <span class="menu-icon"><i class="fa-light fa-calendar-days fs-5"></i></span>
                    <span class="menu-title">Календарь</span>
                </a>
            </div>

            {{-- Уведомления: список подгружается в .notices_shell как раньше --}}
            <div class="menu-item px-3" data-kt-menu-trigger="click" data-kt-menu-placement="right-start">
                <span class="menu-link px-3">
                    <span class="menu-icon"><i class="fa-light fa-message fs-5"></i></span>
                    <span class="menu-title">Уведомления</span>
                    @if(!empty($global['notifies']['count']))
                        <span class="menu-badge">
                            <span class="badge badge-light-danger fs-8">{{ $global['notifies']['count'] }}</span>
                        </span>
                    @else
                        <span class="menu-arrow"></span>
                    @endif
                </span>
                <div class="menu-sub menu-sub-dropdown mailbox w-325px py-3">
                    <div class="loader text-center">
                        <div class="spinner-border text-primary mt-4 mb-3" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div class="notices_shell"></div>
                </div>
            </div>

            <div class="menu-item px-3">
                <a href="{{ route('notify.list') }}" class="menu-link px-3">
                    <span class="menu-icon"><i class="fa-light fa-clock-rotate-left fs-5"></i></span>
                    <span class="menu-title">История уведомлений</span>
                </a>
            </div>

            <div class="separator my-2"></div>

            {{-- Цветовая схема --}}
            <div class="menu-item px-3" data-kt-menu-trigger="click" data-kt-menu-placement="right-start">
                <span class="menu-link px-3">
                    <span class="menu-icon">
                        <i class="fa-light fa-sun-bright fs-5 theme-light-show"></i>
                        <i class="fa-light fa-moon-stars fs-5 theme-dark-show"></i>
                    </span>
                    <span class="menu-title">Цветовая схема</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-3 fs-6 w-175px"
                     data-kt-menu="true" data-kt-element="theme-mode-menu">
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                            <span class="menu-icon" data-kt-element="icon"><i class="fa-light fa-sun-bright fs-5"></i></span>
                            <span class="menu-title">Светлая</span>
                        </a>
                    </div>
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                            <span class="menu-icon" data-kt-element="icon"><i class="fa-light fa-moon-stars fs-5"></i></span>
                            <span class="menu-title">Тёмная</span>
                        </a>
                    </div>
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                            <span class="menu-icon" data-kt-element="icon"><i class="fa-light fa-desktop fs-5"></i></span>
                            <span class="menu-title">Как в системе</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Оформление (MaterialPro / Metronic) --}}
            @include('components.ui.theme_switch')

            @if(auth()->user()->isAdmin() || auth()->user()->silentAdmin())
                <div class="separator my-2"></div>

                <div class="menu-item px-3">
                    <x-ui.a.ajax class="menu-link px-3" url="{{ route('api.access.refresh') }}" method="post" :data="['a' => 1]" reload="1">
                        <span class="menu-icon"><i class="fa-light fa-arrows-rotate fs-5"></i></span>
                        <span class="menu-title">Обновить доступы</span>
                    </x-ui.a.ajax>
                </div>

                <div class="menu-item px-3">
                    <span class="menu-link px-3 text-muted fs-7">ID: {{ auth()->id() }}</span>
                </div>
            @endif

            <div class="separator my-2"></div>

            <div class="menu-item px-3">
                <a href="{{ route('logout') }}" class="menu-link px-3 text-danger">
                    <span class="menu-icon"><i class="fa-light fa-power-off fs-5 text-danger"></i></span>
                    <span class="menu-title">{{ __('header.logout') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Разделы --}}
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-2"
             data-kt-scroll="true"
             data-kt-scroll-activate="true"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_user"
             data-kt-scroll-wrappers="#kt_app_sidebar_menu"
             data-kt-scroll-offset="5px">

            <x-sidebar.menu :tree="$menu_tree"></x-sidebar.menu>
        </div>
    </div>
</div>
