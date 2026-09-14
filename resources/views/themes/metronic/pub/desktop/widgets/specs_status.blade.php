{{-- Виджет «Спецификации по статусам» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\SpecsStatusWidget --}}
@php
    $total = (int) $data['total'];
    $share = fn($count) => $total > 0 ? round($count / $total * 100, 1) : 0;
    $percent = fn($value) => rtrim(rtrim(number_format((float) $value, 1, ',', ' '), '0'), ',') . ' %';
    $hint = $data['year'] . ' · ' . $data['type'];
    $tip = fn($status) => $status['label'] . ': ' . $status['count'] . ' на '
        . $widget::money($status['amount'], $data['symbol'], false) . ' (' . $hint . ')';

    // низкий блок — только счётчики статусов в ряд
    $low = in_array($dh, ['xs', 'sm'], true);
    // блок шириной 3–5 — статусы списком, шире — плитками в три колонки
    $narrow = $dw === 'sm';
    // крупный блок — кольцо сумм по статусам на всю оставшуюся высоту
    $chart = !$low && $dw !== 'xs' && $data['amount'] > 0 && $rows >= ($narrow ? 12 : 9);
    // самый узкий высокий блок — вертикальная полоска долей на всю оставшуюся высоту
    $vsplit = $dw === 'xs' && $rows >= 6;
@endphp
@if($total === 0)
    <div class="desk-empty">
        <i class="fa-light fa-file-contract"></i> <span class="desk-hide-narrow">Спецификаций нет</span>
    </div>
@elseif($dw === 'xs')
    <div @class(['text-center', 'desk-stack' => $vsplit, 'desk-center' => !$vsplit]) title="{{ $hint }} · всего {{ $total }}">
        <div class="desk-label desk-only-h-md">всего {{ $total }}</div>
        {{-- счётчики статусов столбиком: не влезшие по высоте прячет подгон --}}
        <div class="ss-counts desk-fit">
            @foreach($data['statuses'] as $status)
                <div title="{{ $tip($status) }}">
                    <span class="bullet bullet-dot bg-{{ $status['color'] }} w-6px h-6px me-1"></span><span class="desk-value desk-value-sm">{{ $status['count'] }}</span>
                </div>
            @endforeach
        </div>
        @if($vsplit)
            <div class="desk-stack-grow fin-vsplit">
                @foreach($data['statuses'] as $status)
                    @if($status['count'] > 0)
                        <i class="bg-{{ $status['color'] }}" style="flex: {{ $status['count'] }} 1 0"
                           title="{{ $status['label'] }}: {{ $percent($share($status['count'])) }}"></i>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@elseif($low)
    <div class="desk-center">
        <div class="ss-tiles">
            @foreach($data['statuses'] as $status)
                <div class="min-w-0" title="{{ $tip($status) }}">
                    <div class="desk-label desk-nowrap desk-only-w-md">{{ $status['label'] }}</div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="bullet bullet-dot bg-{{ $status['color'] }} w-6px h-6px flex-shrink-0"></span>
                        <span class="desk-value desk-value-sm text-{{ $status['color'] }}">{{ $status['count'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="desk-stack">
        <div class="desk-label desk-nowrap" title="Всего {{ $total }} на {{ $widget::money($data['amount'], $data['symbol'], false) }} · {{ $hint }}">
            всего {{ $total }} на {{ $widget::money($data['amount'], $data['symbol']) }}<span class="desk-only-w-lg"> · {{ $hint }}</span>
        </div>

        @if($narrow)
            {{-- узкий блок: статусы списком, не влезшие по высоте прячет подгон --}}
            <ul class="desk-list ss-list desk-fit">
                @foreach($data['statuses'] as $status)
                    <li title="{{ $tip($status) }}">
                        <span class="bullet bullet-dot bg-{{ $status['color'] }} w-6px h-6px flex-shrink-0"></span>
                        <span class="desk-grow">{{ $status['label'] }}</span>
                        <span class="fw-bold flex-shrink-0">{{ $status['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="ss-tiles">
                @foreach($data['statuses'] as $status)
                    <div class="min-w-0" title="{{ $tip($status) }}">
                        <div class="desk-label desk-nowrap">
                            <i class="fa-light {{ $status['icon'] }} text-{{ $status['color'] }} me-1 desk-only-w-lg"></i>{{ $status['label'] }}
                        </div>
                        <div class="desk-value desk-value-sm text-{{ $status['color'] }}">{{ $status['count'] }}</div>
                        <div class="desk-muted fs-8 desk-nowrap desk-only-h-lg">{{ $widget::money($status['amount'], $data['symbol']) }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="desk-split ss-split">
            @foreach($data['statuses'] as $status)
                @if($status['count'] > 0)
                    <div class="bg-{{ $status['color'] }}" style="width: {{ $share($status['count']) }}%"
                         title="{{ $status['label'] }}: {{ $percent($share($status['count'])) }}"></div>
                @endif
            @endforeach
        </div>

        {{-- подписи долей и отметки о подписании: по строке, что не влезло по ширине — спрятано --}}
        <div class="fin-line desk-fit desk-only-h-lg" data-fit-axis="x" data-fit-min="0">
            @foreach($data['statuses'] as $status)
                <span class="desk-label">
                    <span class="bullet bullet-dot bg-{{ $status['color'] }} w-6px h-6px me-1"></span>{{ $status['label'] }} · {{ $percent($share($status['count'])) }}
                </span>
            @endforeach
        </div>

        <div class="fin-line desk-fit desk-hide-short" data-fit-axis="x" data-fit-min="0">
            @if($settings['signed'])
                <span class="badge badge-light-success fs-8"
                      title="Спецификации с отметкой «подписана», кроме отменённых: {{ $widget::money($data['signed_amount'], $data['symbol'], false) }}">
                    подписано: {{ $data['signed'] }}
                </span>
                @if($data['unsigned'] > 0)
                    <span class="badge badge-light-warning fs-8" title="Спецификации без отметки о подписании, кроме отменённых">
                        без подписи: {{ $data['unsigned'] }}
                    </span>
                @endif
            @endif
            @if($data['skipped'] > 0)
                <span class="desk-muted fs-8">без курса: {{ $data['skipped'] }}</span>
            @endif
        </div>

        @if($chart)
            <div class="desk-stack-grow ss-chart">
                {!! $widget::chart([
                    'type' => 'donut',
                    'money' => true, 'symbol' => $data['symbol'],
                    'legend' => in_array($dw, ['lg', 'xl'], true),
                    'labels' => array_column($data['statuses'], 'label'),
                    // серый «secondary» в графике не виден на светлом фоне — берём gray-500
                    'colors' => array_map(fn($status) => $status['color'] === 'secondary' ? 'gray-500' : $status['color'], $data['statuses']),
                    'series' => array_map(fn($status) => round($status['amount']), $data['statuses']),
                ]) !!}
            </div>
        @endif
    </div>
@endif
