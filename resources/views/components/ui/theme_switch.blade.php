@php use App\Support\UiTheme; @endphp
@if(UiTheme::switchVisible())
    <li class="nav-item dropdown">
        <a class="ps-3 pe-3 nav-link dropdown-toggle waves-effect waves-dark"
           href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Оформление">
            <x-ui.icon.duotone icon="fa-palette"></x-ui.icon.duotone>
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            <h6 class="dropdown-header">Оформление</h6>
            @foreach(UiTheme::all() as $code => $theme)
                <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('ui.theme', $code) }}">
                    <span>
                        {{ $theme['title'] ?? $code }}
                        @if(!empty($theme['subtitle']))
                            <small class="d-block text-muted">{{ $theme['subtitle'] }}</small>
                        @endif
                    </span>
                    @if(UiTheme::is($code))
                        <span class="badge bg-light-success text-success ms-3">вкл</span>
                    @endif
                </a>
            @endforeach
        </div>
    </li>
@endif
