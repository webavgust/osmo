@php
    $url = $item->getUrl();
    $hasChildren = $item->children->count() > 0;
    $isActive = $url && $url !== '#' && rtrim(request()->url(), '/') === rtrim(url($url), '/');
@endphp

@if(!$item->parent_id)
    {{-- Корневой раздел: пункт верхнего уровня --}}
    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
         data-kt-menu-placement="bottom-start"
         class="menu-item menu-lg-down-accordion me-0 me-lg-2">

        @if($hasChildren)
            <span class="menu-link py-3">
                @if($item->icon)<span class="menu-icon"><i class="{{ $item->icon }} fs-4"></i></span>@endif
                <span class="menu-title">@lang($item->name)</span>
                <span class="menu-arrow d-lg-none"></span>
            </span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown py-lg-4 w-lg-275px">
                <x-sidebar.menu-children :tree="$item->children" level="1"></x-sidebar.menu-children>
            </div>
        @else
            <a class="menu-link py-3 @if($isActive) active @endif" href="{{ $url ?: 'javascript:void(0)' }}">
                @if($item->icon)<span class="menu-icon"><i class="{{ $item->icon }} fs-4"></i></span>@endif
                <span class="menu-title">@lang($item->name)</span>
            </a>
        @endif
    </div>
@else
    @if($hasChildren)
        {{-- Вложенный раздел --}}
        <div class="menu-item menu-lg-down-accordion"
             data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
             data-kt-menu-placement="right-start">
            <span class="menu-link">
                @if($item->icon)
                    <span class="menu-icon"><i class="{{ $item->icon }} fs-5"></i></span>
                @else
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                @endif
                <span class="menu-title">@lang($item->name)</span>
                <span class="menu-arrow"></span>
            </span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown py-lg-4 w-lg-275px">
                <x-sidebar.menu-children :tree="$item->children" level="{{ ($level ?? 1) + 1 }}"></x-sidebar.menu-children>
            </div>
        </div>
    @else
        {{-- Конечный пункт --}}
        <div class="menu-item">
            <a class="menu-link @if($isActive) active @endif" href="{{ $url ?: 'javascript:void(0)' }}">
                @if($item->icon)
                    <span class="menu-icon"><i class="{{ $item->icon }} fs-5"></i></span>
                @else
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                @endif
                <span class="menu-title">@lang($item->name)</span>
            </a>
        </div>
    @endif
@endif
