@php use App\Support\UiTheme; @endphp

<div id="kt_app_header" class="app-header mb-6 mb-lg-10">

    {{-- Верхняя полоса: логотип, действия, профиль --}}
    <div class="app-header-primary"
         data-kt-sticky="true"
         data-kt-sticky-activate="{default: false, lg: true}"
         data-kt-sticky-name="app-header-primary-sticky"
         data-kt-sticky-offset="{default: false, lg: '300px'}">

        <div class="app-container container-xxl d-flex align-items-stretch justify-content-between" id="kt_app_header_primary_container">

            {{-- Бургер для мобильных --}}
            <div class="d-flex align-items-center d-lg-none ms-n2 me-2" title="Меню">
                <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_header_menu_toggle">
                    <i class="ki-duotone ki-abstract-14 fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            {{-- Логотип --}}
            <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15">
                <a href="/" class="d-flex align-items-center">
                    <img alt="OSMO" src="/images/logo/logo_letter_white.svg" class="h-40px" />
                </a>
            </div>

            {{-- Правая часть --}}
            <div class="app-navbar flex-shrink-0 align-items-center">

                {{-- Напоминания --}}
                <div class="app-navbar-item ms-2 ms-lg-4">
                    <a href="{{ route('reminder.index') }}"
                       class="btn btn-icon btn-custom w-35px h-35px w-lg-40px h-lg-40px"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Напоминания">
                        <x-ui.icon.duotone icon="fa-alarm-exclamation" class="fs-3"></x-ui.icon.duotone>
                    </a>
                </div>

                {{-- Календарь --}}
                <div class="app-navbar-item ms-2 ms-lg-4">
                    <a href="{{ route('calendar.index') }}"
                       class="btn btn-icon btn-custom w-35px h-35px w-lg-40px h-lg-40px"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Календарь">
                        <x-ui.icon.duotone icon="fa-calendar-days" class="fs-3"></x-ui.icon.duotone>
                    </a>
                </div>

                {{-- Уведомления (id и классы нужны spider_tick) --}}
                <div class="app-navbar-item ms-2 ms-lg-4 @if(!$global['notifies']['count']) d-none @endif" id="notifies">
                    <div class="btn btn-icon btn-custom w-35px h-35px w-lg-40px h-lg-40px position-relative"
                         data-bs-toggle="dropdown" aria-expanded="false">
                        <x-ui.icon.duotone icon="fa-message" class="fs-3"></x-ui.icon.duotone>
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
                <div class="app-navbar-item ms-2 ms-lg-4">
                    <a href="#" class="btn btn-icon btn-custom w-35px h-35px w-lg-40px h-lg-40px"
                       data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                       data-kt-menu-attach="parent"
                       data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-night-day theme-light-show fs-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            <span class="path4"></span><span class="path5"></span><span class="path6"></span>
                            <span class="path7"></span><span class="path8"></span><span class="path9"></span>
                            <span class="path10"></span>
                        </i>
                        <i class="ki-duotone ki-moon theme-dark-show fs-2">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-175px"
                         data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-night-day fs-2">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        <span class="path4"></span><span class="path5"></span><span class="path6"></span>
                                        <span class="path7"></span><span class="path8"></span><span class="path9"></span>
                                        <span class="path10"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Светлая</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-moon fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                                <span class="menu-title">Тёмная</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-screen fs-2">
                                        <span class="path1"></span><span class="path2"></span>
                                        <span class="path3"></span><span class="path4"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Как в системе</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Переключатель оформления --}}
                @include('components.ui.theme_switch')

                {{-- Профиль --}}
                <div class="app-navbar-item ms-3 ms-lg-5" id="kt_header_user_menu_toggle">
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

    {{-- Нижняя полоса: главное меню --}}
    <div class="app-header-secondary">
        <div class="app-container container-xxl d-flex align-items-stretch" id="kt_app_header_secondary_container">
            <div class="d-flex flex-stack flex-row-fluid" id="kt_app_header_wrapper">
                <div class="d-flex align-items-stretch flex-row-fluid">
                    <div class="app-header-menu app-header-mobile-drawer align-items-stretch"
                         data-kt-drawer="true"
                         data-kt-drawer-name="app-header-menu"
                         data-kt-drawer-activate="{default: true, lg: false}"
                         data-kt-drawer-overlay="true"
                         data-kt-drawer-width="280px"
                         data-kt-drawer-direction="start"
                         data-kt-drawer-toggle="#kt_app_header_menu_toggle"
                         data-kt-swapper="true"
                         data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
                         data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">

                        <x-sidebar.menu :tree="$menu_tree"></x-sidebar.menu>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
