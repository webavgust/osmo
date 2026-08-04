@php
    $url = $item->getUrl();
    $hasChildren = $item->children->count() > 0;
    $link = ($url && !$hasChildren) ? $url : 'javascript:void(0)';
    $isActive = $url && $url !== '#' && !$hasChildren
        && rtrim(request()->getPathInfo(), '/') === rtrim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    // раскрыть ветку, если активная страница внутри неё
    $hasActiveChild = $hasChildren && str_contains(
        collect($item->children)->map(fn($c) => (string) $c->getUrl())->implode('|'),
        rtrim(request()->getPathInfo(), '/')
    ) && rtrim(request()->getPathInfo(), '/') !== '';
@endphp

@if(!$item->parent_id)
    {{-- Корневой раздел = подпись группы (аналог nav-small-cap в MaterialPro) --}}
    <div class="menu-item pt-4">
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase fs-8">
                <i class="fa-light fa-ellipsis me-2 menu-heading-icon"></i>
                @lang($item->name)
            </span>
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
                        <i class="{{ $item->icon }} fs-5"></i>
                    @else
                        <i class="fa-light fa-circle-small fs-5"></i>
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
                        <i class="{{ $item->icon }} fs-5"></i>
                    @else
                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    @endif
                </span>
                <span class="menu-title">@lang($item->name)</span>
            </a>
        </div>
    @endif
@endif
