{{-- Виджет «Сделки по кварталам» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\DealsQuarterWidget

    Поведение по размерам:
    - низкий блок (dh xs|sm): «план 2026, ₽» и сумма за год (подпись — выше 96 px), дельта к прошлому
      году — шире 230 px, суммы кварталов ячейками в ряд — шире 420 px;
    - ширина 2 (dw xs), высота ≥3: сумма за год, кварталы строками в .desk-fit (сумма, прошлый год);
      высота ≥6 — под ними столбики без осей на оставшееся место;
    - ширина ≥3, высота ≥3: строка «2026 · N сделок» (число сделок — шире 230 px), прошлый год
      (шире 420 px) и дельта (шире 230 px), сумма;
      график столбцами на всё место; предупреждение «без квартала» переносится по словам;
    - высота ≥10 (dh xl) и ширина ≥6: под графиком таблица кварталов — сумма, прошлый год, дельта,
      число сделок (от 420 px).
--}}
@php
    $symbol = $data['symbol'];
    $compare = $data['compare'] && $data['prev_total'] > 0;
    $low = in_array($dh, ['xs', 'sm'], true);
    $narrow = $dw === 'xs';
    $table = $dh === 'xl' && !in_array($dw, ['xs', 'sm'], true);
    $spark = !$low && $narrow && in_array($dh, ['lg', 'xl'], true);

    $morph = fn($count) => $count . ' ' . \App\Facades\Tools::morph($count, 'сделка', 'сделки', 'сделок');
    // дельта к прошлому году, %: null — сравнивать не с чем
    $delta = fn($now, $was) => $compare && $was > 0 ? (int) round(($now - $was) / $was * 100) : null;
    $delta_class = fn($value) => $value > 0 ? 'up' : ($value < 0 ? 'down' : 'flat');
    $delta_text = fn($value) => ($value > 0 ? '+' : ($value < 0 ? '−' : '')) . abs($value) . ' %';
    $total_delta = $delta($data['total'], $data['prev_total']);

    $total_title = 'План ' . $data['year'] . ': ' . $widget::money($data['total'], $symbol, false) . ', ' . $morph($data['count_total'])
        . ($compare ? '; ' . $data['prev_year'] . ': ' . $widget::money($data['prev_total'], $symbol, false) : '');
    $quarter_title = fn($q) => $q['label'] . ' ' . $data['year'] . ': ' . $widget::money($q['amount'], $symbol, false) . ' · ' . $morph($q['count'])
        . ($compare ? ' (' . $data['prev_year'] . ': ' . $widget::money($q['prev_amount'], $symbol, false) . ')' : '');

    $series = [['name' => (string) $data['year'], 'data' => array_map(fn($q) => round($q['amount'], 2), $data['quarters'])]];
    if ($compare) {
        $series[] = ['name' => (string) $data['prev_year'], 'data' => array_map(fn($q) => round($q['prev_amount'], 2), $data['quarters'])];
    }

    $config = [
        'type' => 'bar',
        'colors' => $compare ? ['primary', 'gray-400'] : ['primary'],
        'money' => true,
        'symbol' => $symbol,
        'legend' => $compare && !$spark,
        'sparkline' => $spark,
        'categories' => array_column($data['quarters'], 'label'),
        'series' => $series,
    ];

    $empty = $data['total'] == 0 && $data['prev_total'] == 0 && $data['none_count'] === 0;
@endphp
@if($empty)
    <div class="desk-empty">
        <i class="fa-light fa-chart-column"></i> Сделок с плановым кварталом нет
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => $low])>
        @if($low || $narrow)
            {{-- сводка: сумма за год крупно; в низком широком блоке рядом ячейки кварталов --}}
            {{-- ячейки в ряд: сумма за год, дельта к прошлому году, кварталы; не влезшие по ширине
                 прячет подгон .desk-fit (data-fit-axis="x") с конца --}}
            <div @class(['d-flex align-items-end gap-4 min-w-0', 'desk-fit' => $low && !$narrow]) data-fit-axis="x">
                <div class="flex-shrink-0" title="{{ $total_title }}">
                    <div class="desk-label desk-nowrap">{{ $narrow ? $data['year'] : 'план ' . $data['year'] }}, {{ $symbol }}</div>
                    <div class="desk-value desk-value-sm">{{ $widget::compact($data['total']) }}</div>
                </div>
                @if($low && !$narrow)
                    @if($total_delta !== null)
                        <div class="dq-cell" title="{{ $total_title }}">
                            <div class="desk-label desk-nowrap">к {{ $data['prev_year'] }}</div>
                            <div class="desk-delta {{ $delta_class($total_delta) }}">{{ $delta_text($total_delta) }}</div>
                        </div>
                    @endif
                    @foreach($data['quarters'] as $quarter)
                        <div class="dq-cell" title="{{ $quarter_title($quarter) }}">
                            <div class="desk-label desk-nowrap">{{ $quarter['label'] }}</div>
                            <div class="fw-bold text-nowrap">{{ $widget::compact($quarter['amount']) }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        @else
            <div class="d-flex align-items-baseline gap-2 min-w-0">
                {{-- число сделок — от 230 px: в узком блоке строка уходила в многоточие вместе с числом --}}
                <span class="desk-label desk-grow" title="{{ $total_title }}">{{ $data['year'] }}<span class="desk-only-w-md"> · {{ $morph($data['count_total']) }}</span></span>
                @if($compare)
                    <span class="desk-muted text-nowrap desk-only-w-lg" title="{{ $data['prev_year'] }} год: {{ $widget::money($data['prev_total'], $symbol, false) }}">
                        {{ $data['prev_year'] }}: {{ $widget::money($data['prev_total'], $symbol) }}
                    </span>
                @endif
                @if($total_delta !== null)
                    <span class="desk-delta {{ $delta_class($total_delta) }} desk-only-w-md">{{ $delta_text($total_delta) }}</span>
                @endif
                <span class="fw-bold text-nowrap" title="{{ $widget::money($data['total'], $symbol, false) }}">{{ $widget::money($data['total'], $symbol) }}</span>
            </div>
        @endif

        @if(!$low && $narrow)
            <ul @class(['desk-list desk-fit dq-quarters', 'desk-stack-grow' => !$spark]) data-fit-min="0">
                @foreach($data['quarters'] as $quarter)
                    <li title="{{ $quarter_title($quarter) }}">
                        <span class="desk-label">{{ $quarter['label'] }}</span>
                        <span class="fw-bold text-nowrap">{{ $widget::compact($quarter['amount']) }}</span>
                        @if($compare)
                            <span class="desk-muted text-nowrap">{{ $widget::compact($quarter['prev_amount']) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if($spark)
                <div class="desk-stack-grow">{!! $widget::chart($config) !!}</div>
            @endif
        @elseif(!$low)
            <div class="desk-stack-grow">{!! $widget::chart($config) !!}</div>

            @if($table)
                <table class="desk-table">
                    <thead>
                    <tr>
                        <th>Квартал</th>
                        <th class="num">{{ $data['year'] }}, {{ $symbol }}</th>
                        @if($compare)
                            <th class="num">{{ $data['prev_year'] }}</th>
                            <th class="num">±</th>
                        @endif
                        <th class="num desk-only-w-lg">Сделок</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($data['quarters'] as $quarter)
                        @php $change = $delta($quarter['amount'], $quarter['prev_amount']); @endphp
                        <tr title="{{ $quarter_title($quarter) }}">
                            <td>{{ $quarter['label'] }}</td>
                            <td class="num fw-semibold">{{ $widget::compact($quarter['amount']) }}</td>
                            @if($compare)
                                <td class="num desk-muted">{{ $widget::compact($quarter['prev_amount']) }}</td>
                                <td class="num">
                                    @if($change !== null)
                                        <span class="desk-delta {{ $delta_class($change) }}">{{ $delta_text($change) }}</span>
                                    @endif
                                </td>
                            @endif
                            <td class="num desk-only-w-lg">{{ $quarter['count'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td>Итого</td>
                        <td class="num">{{ $widget::compact($data['total']) }}</td>
                        @if($compare)
                            <td class="num">{{ $widget::compact($data['prev_total']) }}</td>
                            <td class="num">
                                @if($total_delta !== null)
                                    <span class="desk-delta {{ $delta_class($total_delta) }}">{{ $delta_text($total_delta) }}</span>
                                @endif
                            </td>
                        @endif
                        <td class="num desk-only-w-lg">{{ $data['count_total'] }}</td>
                    </tr>
                    </tfoot>
                </table>
            @endif
        @endif

        @if(!$low && $data['none_count'] > 0)
            {{-- переносится по словам: число и сумма не уходят в многоточие; на узком блоке сумма — в title --}}
            <div class="desk-label d-flex flex-wrap align-items-center gap-1"
                 title="У этих сделок не заполнен плановый квартал, в столбцы они не попали: {{ $widget::money($data['none_amount'], $symbol, false) }}">
                <i class="fa-light fa-triangle-exclamation text-warning"></i>
                <span>без квартала: {{ $data['none_count'] }}</span>
                <span class="text-nowrap desk-hide-narrow">на {{ $widget::money($data['none_amount'], $symbol) }}</span>
            </div>
        @endif
    </div>
@endif
