{{-- Вертикальное меню сайдбара --}}
<div class="menu menu-column menu-rounded menu-sub-indention menu-active-bg fw-semibold px-3"
     id="kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
    <x-sidebar.menu-children :tree="$tree" level="0"></x-sidebar.menu-children>
</div>
