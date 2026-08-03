@if(!$item->parent_id)
    <li class="nav-small-cap">
        <i class="mdi mdi-dots-horizontal"></i>
        <span class="hide-menu">@lang($item->name)</span>
    </li>
    @if($item->children->count())
        <x-sidebar.menu-children :tree="$item->children" level="{{$level+1}}"></x-sidebar.menu-children>
    @endif
@else
    <li class="sidebar-item">
        <a class="pe-0 sidebar-link waves-effect waves-dark @if(count($item->children)) has-arrow @endif" href="@if($item->getUrl() && !$item->children->count()){{$item->getUrl()}}@else javascript:void(0) @endif" aria-expanded="false">
            @if($item->icon)<i class="{{$item->icon}} fs-4 me-1"></i>@endif
                <span class="hide-menu">@lang($item->name)</span>
        </a>

        @if(count($item->children))
            <ul aria-expanded="false" class="collapse ps-1 pe-0" level="{{$level}}">
                <x-sidebar.menu-children :tree="$item->children" level="{{$level+1}}"></x-sidebar.menu-children>
            </ul>
        @endif
    </li>
@endif
