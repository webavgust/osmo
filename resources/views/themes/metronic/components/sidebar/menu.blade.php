{{-- Главное меню Metronic: горизонтальное на десктопе, аккордеон в мобильном drawer --}}
<div class="menu menu-rounded menu-active-bg menu-state-primary menu-column menu-lg-row menu-title-gray-700 menu-icon-gray-500 menu-arrow-gray-500 menu-bullet-gray-500 my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0"
     id="kt_app_header_menu" data-kt-menu="true">
    <x-sidebar.menu-children :tree="$tree" level="0"></x-sidebar.menu-children>
</div>
