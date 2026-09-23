{{-- Виджет «Страны × статусы поквартально» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\CountryQuarterWidget

    Поведение по размерам (как у country_month):
    - узкий блок (dw xs|sm): общая сумма крупно, ниже — страны и кварталы с суммами (.desk-fit);
    - средняя ширина (dw md): матрица «страна × кварталы», статусы свёрнуты в страну;
    - широкий блок (dw lg|xl): матрица «страна → статус × кварталы» (если так выбрано в «Строки»);
    - колонки кварталов появляются по ширине блока (osmo-desktop-widgets/funnel-1.css), итог — всегда;
    - строки режутся подгоном .desk-fit, итоговая строка и предупреждение «Вне таблицы» (квартал не выбран,
      прошёл или позже последнего столбца; части — в подсказке) остаются;
    - высокий блок, а строк мало — под таблицей график сумм по кварталам.
--}}
@php
    $narrow = in_array($dw, ['xs', 'sm'], true);
    $by_status = $settings['rows'] === 'country_status' && in_array($dw, ['lg', 'xl'], true);
    $symbol = $data['symbol'];
    $columns = $data['columns'];
    $period = 'за ' . count($columns) . ' кв. (' . ($columns[0]['title'] ?? '') . ' – ' . (end($columns)['title'] ?? '') . ')';

    // суммы по странам: для узкого блока и матрицы без статусов
    $countries = [];
    foreach ($data['rows'] as $row) {
        $item = $countries[$row['country']] ?? ['country' => $row['country'], 'status' => '', 'first' => true, 'cells' => [], 'total' => 0.0];
        foreach ($row['cells'] as $key => $amount) {
            $item['cells'][$key] = ($item['cells'][$key] ?? 0.0) + $amount;
        }
        $item['total'] += $row['total'];
        $countries[$row['country']] = $item;
    }
    $countries = array_values($countries);

    $lines = $by_status ? $data['rows'] : $countries;
    $max = 0.0;
    foreach ($lines as $line) {
        foreach ($line['cells'] as $amount) {
            $max = max($max, abs($amount));
        }
    }
    $shade = fn($amount) => $max > 0 ? round(0.06 + 0.5 * min(1, abs($amount) / $max), 3) : 0.06;

    // высокий блок, а строк мало — свободное место занимает график по кварталам
    $chart = !$narrow && in_array($dh, ['lg', 'xl'], true) && count($lines) * 2 <= $rows;

    // сделки вне столбцов по частям: подпись «Без квартала», если у всех квартал не выбран, иначе «Вне таблицы»
    $deals_word = fn($count) => $count . ' ' . \App\Facades\Tools::morph($count, 'сделка', 'сделки', 'сделок');
    $part_names = [
        'past' => 'квартал уже прошёл, сделка в воронке',
        'later' => 'квартал позже, чем ' . (end($columns)['title'] ?? ''),
        'empty' => 'квартал не выбран',
    ];
    $parts = array_filter($data['none_parts'] ?? [], fn($part) => $part['count'] > 0);
    $none_label = array_keys($parts) === ['empty'] ? 'Без квартала' : 'Вне таблицы';
    $none_title = 'Не попали в таблицу: ' . $widget::money($data['none_amount'], $symbol, false);
    foreach ($part_names as $key => $name) {
        if (isset($parts[$key])) {
            $none_title .= "\n" . $name . ' — ' . $deals_word($parts[$key]['count']) . ' на ' . $widget::money($parts[$key]['amount'], $symbol, false);
        }
    }
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-table-cells-large"></i> Нет сделок с плановым кварталом
    </div>
@else
    <div class="desk-stack">
        @if($narrow)
            @php usort($countries, fn($a, $b) => $b['total'] <=> $a['total']); @endphp
            <div title="{{ $widget::money($data['grand_total'], $symbol, false) }} {{ $period }}">
                <div @class(['desk-value', 'desk-value-sm' => $dw === 'xs'])>{{ $widget::compact($data['grand_total']) }}</div>
                <div class="desk-label desk-nowrap desk-hide-short">{{ $symbol }} за {{ count($columns) }} кв.</div>
            </div>
            @if(!in_array($dh, ['xs', 'sm'], true))
                <ul class="desk-list desk-stack-grow desk-fit" data-fit-min="0">
                    @foreach(array_slice($countries, 0, $rows_max) as $item)
                        <li title="{{ $item['country'] }}: {{ $widget::money($item['total'], $symbol, false) }}">
                            <span class="desk-grow">{{ $item['country'] }}</span>
                            <span class="fw-bold text-nowrap">{{ $widget::compact($item['total']) }}</span>
                        </li>
                    @endforeach
                    {{-- ниже стран — суммы по кварталам: подгон прячет строки с конца, поэтому они уходят первыми --}}
                    <li class="desk-label pt-3">По кварталам</li>
                    @foreach($columns as $column)
                        @php $amount = $data['col_totals'][$column['key']] ?? 0; @endphp
                        <li title="{{ $column['title'] }}: {{ $widget::money($amount, $symbol, false) }}">
                            <span class="desk-grow">{{ $column['label'] }}</span>
                            <span class="fw-bold text-nowrap">{{ $amount != 0 ? $widget::compact($amount) : '·' }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="desk-label desk-nowrap" data-fit-more="ещё {n}"></div>
            @endif
        @else
            <div @class(['desk-fit', 'desk-stack-grow' => !$chart]) data-fit-items="tbody > tr" data-fit-min="0">
                <table @class(['desk-table', 'cm-status' => $by_status])>
                    <thead>
                    <tr>
                        <th>Страна</th>
                        @if($by_status)
                            <th>Статус</th>
                        @endif
                        @foreach($columns as $i => $column)
                            <th class="num" data-m="{{ $i }}" title="{{ $column['title'] }}">{{ $column['label'] }}</th>
                        @endforeach
                        <th class="num" title="{{ $period }}">Итого, {{ $symbol }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach(array_slice($lines, 0, $rows_max) as $row)
                        <tr>
                            <td class="desk-cut fw-semibold" title="{{ $row['country'] }}">{{ $row['first'] ? $row['country'] : '' }}</td>
                            @if($by_status)
                                <td class="desk-cut" title="{{ $row['status'] }}">{{ $row['status'] }}</td>
                            @endif
                            @foreach($columns as $i => $column)
                                @php $amount = $row['cells'][$column['key']] ?? 0; @endphp
                                @if($amount != 0)
                                    <td class="num" data-m="{{ $i }}" style="background: rgba(var(--bs-primary-rgb), {{ $shade($amount) }});"
                                        title="{{ $column['title'] }} · {{ $widget::money($amount, $symbol, false) }}">{{ $widget::compact($amount) }}</td>
                                @else
                                    <td class="num desk-muted" data-m="{{ $i }}">·</td>
                                @endif
                            @endforeach
                            <td class="num fw-bold" title="{{ $widget::money($row['total'], $symbol, false) }}">{{ $widget::compact($row['total']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td @if($by_status) colspan="2" @endif>Итого</td>
                        @foreach($columns as $i => $column)
                            @php $amount = $data['col_totals'][$column['key']] ?? 0; @endphp
                            <td class="num" data-m="{{ $i }}" title="{{ $column['title'] }} · {{ $widget::money($amount, $symbol, false) }}">{{ $amount != 0 ? $widget::compact($amount) : '·' }}</td>
                        @endforeach
                        <td class="num" title="{{ $widget::money($data['grand_total'], $symbol, false) }}">{{ $widget::compact($data['grand_total']) }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="desk-label desk-nowrap desk-hide-short" data-fit-more="ещё строк: {n}"></div>
            @if($chart)
                <div class="desk-stack-grow">
                    {!! $widget::chart([
                        'type' => 'bar',
                        'money' => true,
                        'symbol' => $symbol,
                        'categories' => array_column($columns, 'label'),
                        'series' => [['name' => 'Сумма', 'data' => array_map(fn($c) => round((float) ($data['col_totals'][$c['key']] ?? 0)), $columns)]],
                    ]) !!}
                </div>
            @endif
        @endif

        @if($data['none_count'] > 0)
            {{-- предупреждение переносится по словам: число сделок и сумма не уходят в многоточие --}}
            <div class="desk-label d-flex flex-wrap align-items-center gap-1 desk-hide-short" title="{{ $none_title }}">
                <i class="fa-light fa-triangle-exclamation text-warning"></i>
                <span>{{ $none_label }}: {{ $deals_word($data['none_count']) }}</span>
                <span class="text-nowrap desk-hide-narrow">на {{ $widget::money($data['none_amount'], $symbol) }}</span>
            </div>
        @endif
    </div>
@endif
