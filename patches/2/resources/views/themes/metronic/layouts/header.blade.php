@php use App\Support\UiTheme; @endphp

<div id="kt_app_header" class="app-header">
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">

        {{-- Бургер (мобильные) --}}
        <div class="d-flex align-items-center d-lg-none ms-n3 me-2" title="Меню">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                <i class="fa-light fa-bars fs-2"></i>
            </div>
        </div>

        {{-- Логотип (мобильные — в сайдбаре он скрыт за drawer) --}}
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 d-lg-none">
            <a href="{{ route('dashboard.index') }}">
                <img alt="OSMO" src="/images/logo/logo_letter.svg" class="h-30px" />
            </a>
        </div>

        {{-- Свободное место (заголовок страницы — в toolbar ниже) --}}
        <div class="d-none d-lg-flex flex-grow-1"></div>

        {{-- Правая часть --}}
        <div class="app-navbar flex-shrink-0 align-items-center">

            {{-- Напоминания --}}
            <div class="app-navbar-item ms-1 ms-lg-3">
                <a href="{{ route('reminder.index') }}"
                   class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-lg-40px h-lg-40px"
                   data-bs-toggle="tooltip" data-bs-placement="bottom" title="Напоминания">
                    <i class="fa-duotone fa-alarm-exclamation fs-3"></i>
                </a>
            </div>

            {{-- Календарь --}}
            <div class="app-navbar-item ms-1 ms-lg-3">
                <a href="{{ route('calendar.index') }}"
                   class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-lg-40px h-lg-40px"
                   data-bs-toggle="tooltip" data-bs-placement="bottom" title="Календарь">
                    <i class="fa-duotone fa-calendar-days fs-3"></i>
                </a>
            </div>

            {{-- Уведомления (id и классы нужны spider_tick) --}}
            <div class="app-navbar-item ms-1 ms-lg-3 @if(!$global['notifies']['count']) d-none @endif" id="notifies">
                <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-lg-40px h-lg-40px position-relative"
                     data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-duotone fa-message fs-3"></i>
                    <div class="notify">
                        <span class="heartbit @if(empty($global['notifies']['new'])) d-none @endif"></span>
                        <span class="point"></span>
                    </div>
                </div>
                <div class="dropdown-menu dropdown-menu-end mailbox w-350px py-3">
                    <div class="loader text-center">
                        <div class="spinner-border text-primary mt-4 mb-3" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div class="notices_shell"></div>
                </div>
            </div>

            {{-- Светлая / тёмная тема --}}
            <div class="app-navbar-item ms-1 ms-lg-3">
                <a href="#" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-lg-40px h-lg-40px"
                   data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                   data-kt-menu-attach="parent"
                   data-kt-menu-placement="bottom-end"
                   title="Тема">
                    <i class="fa-duotone fa-sun-bright fs-3 theme-light-show"></i>
                    <i class="fa-duotone fa-moon-stars fs-3 theme-dark-show"></i>
                </a>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-175px"
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

            {{-- Профиль --}}
            <div class="app-navbar-item ms-2 ms-lg-4" id="kt_header_user_menu_toggle">
                <div class="cursor-pointer symbol symbol-35px symbol-lg-40px"
                     data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                     data-kt-menu-attach="parent"
                     data-kt-menu-placement="bottom-end">
                    <img src="{{ asset(auth()->user()->avatar()) }}" alt="user" class="rounded-circle" />
                </div>

                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <div class="menu-content d-flex align-items-center px-3">
                            <div class="symbol symbol-50px me-5">
                                <img alt="user" src="{{ asset(auth()->user()->avatar()) }}" class="rounded-circle" />
                            </div>
                            <div class="d-flex flex-column">
                                <div class="fw-bold d-flex align-items-center fs-6">{{ auth()->user()->name }}</div>
                                <span class="fw-semibold text-muted fs-7">{{ auth()->user()->email }}</span>
                                @if(auth()->user()->isAdmin() || auth()->user()->silentAdmin())
                                    <span class="fw-semibold text-muted fs-8">ID: {{ auth()->id() }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="separator my-2"></div>

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
                    @endif

                    <div class="separator my-2"></div>

                    <div class="menu-item px-5">
                        <a href="{{ route('logout') }}" class="menu-link px-5 text-danger">{{ __('header.logout') }}</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
