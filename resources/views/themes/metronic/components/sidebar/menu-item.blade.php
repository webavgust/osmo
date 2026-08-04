@php
    $url = $item->getUrl();
    $hasChildren = $item->children->count() > 0;
    $link = ($url && !$hasChildren) ? $url : 'javascript:void(0)';
    $path = rtrim(request()->getPathInfo(), '/');
    $itemPath = $url ? rtrim(parse_url($url, PHP_URL_PATH) ?? '', '/') : null;
    $isActive = $itemPath && !$hasChildren && $path === $itemPath;
    $hasActiveChild = $hasChildren && collect($item->children)
        ->contains(fn($c) => $c->getUrl() && rtrim(parse_url($c->getUrl(), PHP_URL_PATH) ?? '', '/') === $path);
@endphp

@if(!$item->parent_id)
    {{-- Корневой раздел = подпись группы (аналог nav-small-cap в MaterialPro) --}}
    <div class="menu-item pt-4">
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase">@lang($item->name)</span>
        </div>
    </div>

    @if($hasChildren)
        <x-sidebar.menu-children :tree="$item->children" level="{{ ($level ?? 0) + 1 }}"></x-sidebar.menu-children>
    @endif
@else
    @if($hasChildren)
        <div data-kt-menu-trigger="click" class="menu-item menu-accordion @if($hasActiveChild) hover show @endif">
            <span class="menu-link">
                <span class="menu-icon">
                    @if($item->icon)
                        <i class="{{ $item->icon }}"></i>
                    @else
                        <i class="fa-light fa-circle-small"></i>
                    @endif
                </span>
                <span class="menu-title">@lang($item->name)</span>
                <span class="menu-arrow"></span>
            </span>
            <div class="menu-sub menu-sub-accordion">
                <x-sidebar.menu-children :tree="$item->children" level="{{ ($level ?? 0) + 1 }}"></x-sidebar.menu-children>
            </div>
        </div>
    @else
        <div class="menu-item">
            <a class="menu-link @if($isActive) active @endif" href="{{ $link }}">
                <span class="menu-icon">
                    @if($item->icon)
                        <i class="{{ $item->icon }}"></i>
                    @else
                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    @endif
                </span>
                <span class="menu-title">@lang($item->name)</span>
            </a>
        </div>
    @endif
@endif
