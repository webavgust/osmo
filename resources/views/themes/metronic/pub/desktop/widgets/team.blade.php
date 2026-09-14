{{-- Виджет «Кто в сети» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\TeamWidget --}}
@php
    // низкий блок — только аватары (их в строку влезает много), высокий — список с должностью
    $avatars = in_array($dh, ['xs', 'sm'], true);
    $list = array_slice($data['rows'], 0, max(3, $avatars ? $rows * 4 : $rows));
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $hint = fn($row) => trim($row['name']
        . ($settings['show_position'] && $row['position'] !== '' ? ' · ' . $row['position'] : '')
        . ' · ' . ($row['online'] ? 'в сети' : 'был ' . $row['ago'] . ' (' . $row['when'] . ')'));
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-users"></i> Никто не заходил
    </div>
@elseif($avatars)
    <div class="desk-stack">
        <div class="desk-label desk-nowrap desk-hide-short" title="Из {{ $data['total'] }} последних визитов">
            в сети: {{ $data['online'] }} из {{ $data['total'] }}
        </div>

        <div class="desk-stack-grow desk-clip">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @foreach($list as $row)
                    <a href="{{ $href($row) }}" class="symbol symbol-30px symbol-circle" title="{{ $hint($row) }}">
                        <img src="{{ $row['avatar'] }}" alt="{{ $row['name'] }}"/>
                        @if($row['online'])
                            <span class="symbol-badge badge badge-circle bg-success start-100 bottom-0 w-8px h-8px border border-2 border-body"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="desk-scroll">
        <ul class="desk-list">
            @foreach($list as $row)
                <li>
                    <span class="symbol symbol-25px symbol-circle flex-shrink-0 desk-hide-narrow">
                        <img src="{{ $row['avatar'] }}" alt="{{ $row['name'] }}"/>
                    </span>
                    <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $hint($row) }}">
                        <span class="fw-semibold">{{ $row['name'] }}</span>
                        @if($settings['show_position'] && $row['position'] !== '')
                            <span class="desk-muted fs-8 ms-2 desk-only-w-lg">{{ $row['position'] }}</span>
                        @endif
                    </a>
                    @if($row['phone'] !== '')
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-xl">{{ $row['phone'] }}</span>
                    @endif
                    @if($row['online'])
                        <span class="badge badge-light-success flex-shrink-0" title="Визит меньше {{ $widget::ONLINE_MINUTES }} мин. назад">в сети</span>
                    @else
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md" title="{{ $row['when'] }}">{{ $row['ago'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
