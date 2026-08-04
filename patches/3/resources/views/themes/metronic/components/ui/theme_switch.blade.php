@php use App\Support\UiTheme; @endphp
@if(UiTheme::switchVisible())
    <div class="app-navbar-item position-relative">
        <a href="#" class="btn btn-icon btn-sidebar-action"
           data-kt-menu-trigger="click"
           data-kt-menu-attach="parent"
           data-kt-menu-placement="bottom-start"
           title="Оформление">
            <i class="fa-duotone fa-palette fs-3"></i>
        </a>

        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-250px" data-kt-menu="true">
            <div class="menu-item px-3">
                <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Оформление</div>
            </div>

            @foreach(UiTheme::all() as $code => $theme)
                <div class="menu-item px-3">
                    <a href="{{ route('ui.theme', $code) }}" class="menu-link px-3 @if(UiTheme::is($code)) active @endif">
                        <span class="menu-icon">
                            <i class="fa-light {{ $theme['icon'] ?? 'fa-swatchbook' }} fs-5"></i>
                        </span>
                        <span class="menu-title d-flex flex-column">
                            <span>{{ $theme['title'] ?? $code }}</span>
                            @if(!empty($theme['subtitle']))
                                <span class="fs-8 text-muted">{{ $theme['subtitle'] }}</span>
                            @endif
                        </span>
                        @if(UiTheme::is($code))
                            <span class="menu-badge"><span class="badge badge-light-success fs-8">вкл</span></span>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
