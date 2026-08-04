{{-- Левое меню: по структуре повторяет сайдбар MaterialPro (профиль → разделы → выход) --}}
<div id="kt_app_sidebar" class="app-sidebar flex-column"
     data-kt-drawer="true"
     data-kt-drawer-name="app-sidebar"
     data-kt-drawer-activate="{default: true, lg: false}"
     data-kt-drawer-overlay="true"
     data-kt-drawer-width="265px"
     data-kt-drawer-direction="start"
     data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">

    {{-- Логотип --}}
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <a href="{{ route('dashboard.index') }}" class="app-sidebar-logo-link">
            <img alt="OSMO" src="/images/logo/logo_letter_white.svg" class="h-30px app-sidebar-logo-default" />
            <img alt="OSMO" src="/images/logo/logo_letter.svg" class="h-25px app-sidebar-logo-minimize" />
        </a>

        <div id="kt_app_sidebar_toggle"
             class="app-sidebar-toggle btn btn-icon btn-sm h-30px w-30px"
             data-kt-toggle="true"
             data-kt-toggle-state="active"
             data-kt-toggle-target="body"
             data-kt-toggle-name="app-sidebar-minimize"
             title="Свернуть меню">
            <i class="fa-light fa-angles-left fs-4"></i>
        </div>
    </div>

    {{-- Карточка пользователя (аналог user-profile в MaterialPro) --}}
    <div class="app-sidebar-user px-6" id="kt_app_sidebar_user">
        <a href="{{ route('users.view', auth()->user()) }}" class="d-flex align-items-center text-decoration-none">
            <div class="symbol symbol-40px me-3 flex-shrink-0">
                <img src="{{ asset(auth()->user()->avatar()) }}" alt="{{ auth()->user()->name }}" class="rounded-circle" />
            </div>
            <div class="app-sidebar-user-info overflow-hidden">
                <div class="text-white fw-bold text-truncate">{{ auth()->user()->name }}</div>
                <div class="text-gray-500 fs-8 text-truncate">{{ auth()->user()->email }}</div>
            </div>
        </a>
    </div>

    {{-- Меню --}}
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-2"
             data-kt-scroll="true"
             data-kt-scroll-activate="true"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_user, #kt_app_sidebar_footer"
             data-kt-scroll-wrappers="#kt_app_sidebar_menu"
             data-kt-scroll-offset="5px">

            <x-sidebar.menu :tree="$menu_tree"></x-sidebar.menu>
        </div>
    </div>

    {{-- Низ: выход (как sidebar-footer в MaterialPro) --}}
    <div class="app-sidebar-footer d-flex flex-center px-6" id="kt_app_sidebar_footer">
        <a href="{{ route('calendar.index') }}" class="btn btn-icon btn-sidebar-footer"
           data-bs-toggle="tooltip" data-bs-placement="top" title="Календарь">
            <i class="fa-light fa-calendar-days fs-4"></i>
        </a>
        <a href="{{ route('users.view', auth()->user()) }}" class="btn btn-icon btn-sidebar-footer"
           data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('header.my_profile') }}">
            <i class="fa-light fa-user fs-4"></i>
        </a>
        <a href="{{ route('logout') }}" class="btn btn-icon btn-sidebar-footer"
           data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('sidebar.logout') }}">
            <i class="fa-light fa-power-off fs-4"></i>
        </a>
    </div>
</div>
