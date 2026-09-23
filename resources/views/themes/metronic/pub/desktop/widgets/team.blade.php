{{-- Виджет «Кто в сети» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\TeamWidget --}}
@php
    // низкий блок — лента аватаров в одну строку (шире 230 px — с именами), не влезшие прячет
    // подгон по ширине; высокий — список с должностью, строк с запасом, лишние прячет .desk-fit
    $avatars = in_array($dh, ['xs', 'sm'], true);
    $list = $avatars ? $data['rows'] : array_slice($data['rows'], 0, $rows_max);
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $hint = fn($row) => trim($row['name']
        . ($settings['show_position'] && $row['position'] !== '' ? ' · ' . $row['position'] : '')
        . ' · ' . ($row['online'] ? 'в сети' : 'был ' . $row['ago'] . ' (' . $row['when'] . ')'));
    $counter = 'в сети: ' . $data['online'] . ' из ' . $data['total'];
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-users"></i>
        <span class="desk-hide-narrow">{{ $settings['only_online'] ? 'Сейчас в сети никого' : 'Никто не заходил' }}</span>
    </div>
@elseif($avatars)
    <div class="desk-center">
        <div class="d-flex align-items-center gap-2" style="min-width: 0">
            @if($data['online'] > 0)
                <span class="badge badge-light-success flex-shrink-0 desk-only-w-md" title="{{ $counter }}: визит меньше {{ $widget::ONLINE_MINUTES }} мин. назад">{{ $data['online'] }} в сети</span>
            @endif
            <div class="d-flex align-items-center gap-2 flex-grow-1 desk-fit tm-strip" data-fit-axis="x">
                @foreach($list as $row)
                    <a href="{{ $href($row) }}" class="tm-chip" title="{{ $hint($row) }}">
                        <span class="symbol symbol-30px symbol-circle flex-shrink-0">
                            <img src="{{ $row['avatar'] }}" alt="{{ $row['name'] }}"/>
                            @if($row['online'])
                                <span class="symbol-badge badge badge-circle bg-success start-100 bottom-0 w-8px h-8px border border-2 border-body"></span>
                            @endif
                        </span>
                        <span class="fw-semibold text-nowrap desk-only-w-md">{{ $row['short'] ?? $row['name'] }}</span>
                    </a>
                @endforeach
                <span class="desk-muted fs-8 text-nowrap flex-shrink-0" data-fit-more="+{n}"></span>
            </div>
        </div>
    </div>
@else
    <div class="desk-stack">
        <div class="desk-label desk-nowrap" title="{{ $counter }} последних визитов"><span class="desk-hide-narrow">в сети: </span>{{ $data['online'] }} из {{ $data['total'] }}</div>

        <ul class="desk-list desk-fit desk-stack-grow tm-list">
            @foreach($list as $row)
                <li>
                    <span class="symbol symbol-25px symbol-circle flex-shrink-0 desk-hide-narrow">
                        <img src="{{ $row['avatar'] }}" alt="{{ $row['name'] }}"/>
                    </span>
                    {{-- уже 230 px вместо значка «в сети» — зелёная точка перед именем --}}
                    @if($row['online'])
                        <span class="bullet bullet-dot bg-success h-6px w-6px flex-shrink-0 tm-dot"></span>
                    @endif
                    {{-- уже 230 px — короткое имя («Анна С.»), шире — полное и должность --}}
                    <a href="{{ $href($row) }}" class="desk-link desk-grow d-flex align-items-baseline text-hover-primary" title="{{ $hint($row) }}">
                        <span class="fw-semibold desk-nowrap min-w-0 tm-name">{{ $row['name'] }}</span>
                        <span class="fw-semibold desk-nowrap min-w-0 tm-short">{{ $row['short'] ?? $row['name'] }}</span>
                        @if($settings['show_position'] && $row['position'] !== '')
                            <span class="desk-muted fs-8 ms-2 desk-nowrap min-w-0 desk-only-w-lg tm-pos">{{ $row['position'] }}</span>
                        @endif
                    </a>
                    @if($row['phone'] !== '')
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-xl">{{ $row['phone'] }}</span>
                    @endif
                    @if($row['online'])
                        <span class="badge badge-light-success flex-shrink-0 desk-only-w-md" title="Визит меньше {{ $widget::ONLINE_MINUTES }} мин. назад">в сети</span>
                    @else
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md" title="{{ $row['when'] }}">{{ $row['ago'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8" data-fit-more="ещё {n}"></div>
    </div>
@endif
