@php use App\Support\UiTheme; @endphp
@if(UiTheme::switchVisible())
    <div class="menu-item px-3" data-kt-menu-trigger="click" data-kt-menu-placement="right-start">
        <span class="menu-link px-3">
            <span class="menu-icon"><i class="fa-light fa-palette fs-5"></i></span>
            <span class="menu-title">Оформление</span>
            <span class="menu-arrow"></span>
        </span>

        <div class="menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-3 fs-6 w-250px" data-kt-menu="true">
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
