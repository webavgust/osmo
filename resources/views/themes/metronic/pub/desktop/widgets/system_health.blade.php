{{-- Виджет «Состояние портала» (patch v30): App\Modules\Pub\Desktop\Widgets\Admin\SystemHealthWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - тесный блок (узкий и низкий, или в одну ячейку) — один светофор: подсистем с замечаниями;
    - обычный блок — список подсистем проблемами вверх ($rows_max в .desk-fit): значок, название, значение;
      уже 170 px — без значка и значения (оно в подсказке), шире 420 px — + пояснение;
      выше 110 px — сверху счётчик замечаний;
    - высокий блок (dh xl) или широкий и высокий (dh lg, dw lg|xl) — плитки на всю площадь:
      название, значение крупнее, пояснение и время (не в узком блоке).
--}}
@php
    $problems = (int) $data['bad'] + (int) $data['warn'];
    $total = count($data['rows']);
    $narrow = in_array($dw, ['xs', 'sm'], true);
    $compact = ($dw === 'xs' && in_array($dh, ['xs', 'sm'], true)) || $dh === 'xs';
    $tiles = $dh === 'xl' || ($dh === 'lg' && in_array($dw, ['lg', 'xl'], true));
    $list = array_slice($data['rows'], 0, max(1, $rows_max));
    $summary_color = $data['bad'] > 0 ? 'text-danger' : ($problems > 0 ? 'text-warning' : 'text-success');
    $value_color = fn($row) => match ($row['status']) {
        'bad' => 'text-danger',
        'warn' => 'text-warning',
        'none' => 'desk-muted',
        default => '',
    };
    $hint = fn($row) => trim($row['name'] . ': ' . $row['value']
        . ($row['note'] ? ' · ' . $row['note'] : '')
        . ($row['when'] ? ' · ' . $row['when'] : ''));
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-heart-pulse"></i> Подсистемы не выбраны
    </div>
@elseif($compact)
    <div class="desk-center">
        <div class="desk-label desk-nowrap" title="Подсистем с замечаниями">{{ $dw === 'xs' ? 'замечаний' : 'подсистем с замечаниями' }}</div>
        <div class="sh-main">
            <div @class(['desk-value', $summary_color])>{{ $problems }}</div>
            <span class="desk-muted fs-8 text-nowrap sh-side">из {{ $total }}</span>
        </div>
    </div>
@else
    <div class="desk-stack">
        <div class="sh-head desk-only-h-md">
            <span @class(['desk-value desk-value-sm', $summary_color])>{{ $problems }}</span>
            <span class="desk-muted text-nowrap">{{ $dw === 'xs' ? 'из ' . $total : 'замечаний из ' . $total }}</span>
            @if($data['bad'] > 0)
                <span class="badge badge-light-danger text-nowrap desk-only-w-md">ошибок: {{ $data['bad'] }}</span>
            @endif
            @if($data['warn'] > 0)
                <span class="badge badge-light-warning text-nowrap desk-only-w-md">внимание: {{ $data['warn'] }}</span>
            @endif
        </div>

        @if($tiles)
            <div class="desk-stack-grow sh-tiles">
                @foreach($data['rows'] as $row)
                    <div class="sh-tile border-{{ $row['color'] }}" title="{{ $hint($row) }}">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="fa-light {{ $row['icon'] }} text-{{ $row['color'] }} flex-shrink-0 desk-hide-narrow"></i>
                            <span class="desk-nowrap fw-semibold sh-tile-name">{{ $row['name'] }}</span>
                        </div>
                        <div @class(['sh-value', $value_color($row)])>{{ $row['value'] }}</div>
                        @unless($narrow)
                            @if($row['note'])
                                <div class="desk-muted fs-8 sh-note">{{ $row['note'] }}</div>
                            @endif
                            @if($row['when'])
                                <div class="desk-muted fs-8 text-nowrap">{{ $row['when'] }}</div>
                            @endif
                        @endunless
                    </div>
                @endforeach
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit" data-fit-min="0">
                @foreach($list as $row)
                    <li title="{{ $hint($row) }}">
                        <i class="fa-light {{ $row['icon'] }} text-{{ $row['color'] }} flex-shrink-0 desk-hide-narrow"></i>
                        <span class="bullet bullet-dot bg-{{ $row['color'] }} flex-shrink-0 sh-dot"></span>
                        <span class="desk-grow">{{ $row['name'] }}</span>
                        @if($row['note'])
                            <span class="desk-muted fs-8 desk-nowrap desk-only-w-lg sh-list-note">{{ $row['note'] }}</span>
                        @endif
                        <span @class(['fw-semibold text-nowrap flex-shrink-0 desk-hide-narrow', $value_color($row)])>{{ $row['value'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
        @endif
    </div>
@endif
