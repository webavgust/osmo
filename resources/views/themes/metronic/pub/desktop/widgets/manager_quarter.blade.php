{{-- Виджет «Менеджеры × статусы поквартально» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\ManagerQuarterWidget --}}
@php
    $symbol = $data['symbol'];

    // статусы строками — только на широком блоке (от 12 колонок), уже строки сворачиваются
    // по менеджеру; кварталы колонками — от 6 колонок, уже — только менеджер и итог
    $wide = in_array($dw, ['lg', 'xl'], true);
    $by_status = $settings['rows'] === 'manager_status' && $wide;
    $quarters = !in_array($dw, ['xs', 'sm'], true);
    $narrow = $dw === 'xs';
    // высокий блок: итог крупно сверху, строки выше (стиль группы funnel-2)
    $summary = in_array($dh, ['lg', 'xl'], true) && !$narrow;

    $rows = $data['rows'];
    if (!$by_status) {
        $grouped = [];
        foreach ($rows as $row) {
            $group = $grouped[$row['manager']] ?? ['manager' => $row['manager'], 'url' => $row['url'], 'status' => '', 'first' => true, 'cells' => [], 'total' => 0.0, 'count' => 0];
            foreach ($row['cells'] as $key => $cell) {
                $group['cells'][$key]['amount'] = ($group['cells'][$key]['amount'] ?? 0.0) + $cell['amount'];
                $group['cells'][$key]['count'] = ($group['cells'][$key]['count'] ?? 0) + $cell['count'];
            }
            $group['total'] += $row['total'];
            $group['count'] += $row['count'];
            $grouped[$row['manager']] = $group;
        }
        $rows = array_values($grouped);
    }

    // заливка ячейки — от самой крупной ячейки показанных строк
    $max = 0.0;
    foreach ($rows as $row) {
        foreach ($row['cells'] as $cell) $max = max($max, abs($cell['amount']));
    }
    $shade = fn($amount) => $max > 0 ? round(0.06 + 0.5 * min(1, abs($amount) / $max), 3) : 0.06;

    // первый квартал — всегда, второй — от 340 px, третий-четвёртый — от 420 px, дальше — от 900 px
    // (mq-mid и mq-far — стиль группы funnel-2)
    $col_class = fn($i) => match (true) {
        $i === 0 => '',
        $i === 1 => 'mq-mid',
        $i < 4 => 'desk-only-w-lg',
        default => 'mq-far',
    };

    $deals = fn($count) => $count . ' ' . \App\Facades\Tools::morph($count, 'сделка', 'сделки', 'сделок');
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $managers = count(array_unique(array_column($data['rows'], 'manager')));

    $none_title = 'Сделки, у которых плановый квартал не выбран или лежит вне показанных кварталов: '
        . $deals($data['none_count']) . ' · ' . $widget::money($data['none_amount'], $symbol, false);
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-user-group"></i> Нет сделок с плановым кварталом
    </div>
@else
    <div class="desk-stack">
        @if($summary)
            <div>
                <div class="desk-value-sm" title="{{ $widget::money($data['grand_total'], $symbol, false) }}">{{ $widget::money($data['grand_total'], $symbol) }}</div>
                <div class="desk-label">
                    <span class="text-nowrap">{{ $deals($data['count_total']) }}</span>
                    <span class="text-nowrap desk-only-w-md">· {{ $managers }} {{ \App\Facades\Tools::morph($managers, 'менеджер', 'менеджера', 'менеджеров') }}</span>
                </div>
            </div>
        @endif

        <div @class(['desk-fit', 'desk-stack-grow', 'mq-tall' => $summary]) data-fit-items="tbody > tr">
            <table class="desk-table">
                {{-- в низком блоке шапке, строке и итогу вместе не хватает места — шапка прячется --}}
                <thead class="desk-hide-short">
                <tr>
                    <th class="desk-hide-narrow">Менеджер</th>
                    @if($by_status)
                        <th>Статус</th>
                    @endif
                    @if($quarters)
                        @foreach($data['columns'] as $i => $column)
                            <th class="num {{ $col_class($i) }}" title="{{ $column['title'] }}">{{ $column['label'] }}</th>
                        @endforeach
                    @endif
                    <th class="num" title="За {{ count($data['columns']) }} кв.">Итого, {{ $symbol }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    <tr title="{{ $row['manager'] }}{{ $row['status'] !== '' ? ' · ' . $row['status'] : '' }}: {{ $deals($row['count']) }} · {{ $widget::money($row['total'], $symbol, false) }}">
                        <td class="fw-semibold desk-cut desk-hide-narrow">
                            @if($row['first'])
                                <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate"
                                   title="{{ $row['manager'] }} — открыть сделки менеджера">{{ $row['manager'] }}</a>
                            @endif
                        </td>
                        @if($by_status)
                            <td class="desk-cut" title="{{ $row['status'] }}">{{ $row['status'] }}</td>
                        @endif
                        @if($quarters)
                            @foreach($data['columns'] as $i => $column)
                                @php $cell = $row['cells'][$column['key']] ?? ['amount' => 0.0, 'count' => 0]; @endphp
                                @if($cell['amount'] != 0)
                                    <td class="num {{ $col_class($i) }}" style="background: rgba(var(--bs-primary-rgb), {{ $shade($cell['amount']) }});"
                                        title="{{ $column['title'] }} · {{ $deals($cell['count']) }} · {{ $widget::money($cell['amount'], $symbol, false) }}">
                                        {{ $widget::compact($cell['amount']) }}
                                        <span class="desk-muted desk-only-w-xl">· {{ $cell['count'] }}</span>
                                    </td>
                                @else
                                    <td class="num desk-muted {{ $col_class($i) }}">·</td>
                                @endif
                            @endforeach
                        @endif
                        <td class="num fw-bold" title="{{ $deals($row['count']) }} · {{ $widget::money($row['total'], $symbol, false) }}">
                            {{ $widget::compact($row['total']) }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td class="desk-hide-narrow" @if($by_status) colspan="2" @endif>Итого</td>
                    @if($quarters)
                        @foreach($data['columns'] as $i => $column)
                            @php $cell = $data['col_totals'][$column['key']]; @endphp
                            <td class="num {{ $col_class($i) }}" title="{{ $column['title'] }} · {{ $deals($cell['count']) }} · {{ $widget::money($cell['amount'], $symbol, false) }}">
                                {{ $cell['amount'] != 0 ? $widget::compact($cell['amount']) : '·' }}
                            </td>
                        @endforeach
                    @endif
                    <td class="num" title="{{ $deals($data['count_total']) }} · {{ $widget::money($data['grand_total'], $symbol, false) }}">
                        {{ $widget::compact($data['grand_total']) }}
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>

        @if($data['none_count'] > 0)
            <div class="desk-muted fs-8 text-nowrap overflow-hidden desk-hide-short" title="{{ $none_title }}">
                <i class="fa-light fa-triangle-exclamation text-warning me-1"></i><span class="desk-hide-narrow">Не выбрано: </span>{{ $data['none_count'] }}<span class="desk-only-w-md"> {{ \App\Facades\Tools::morph($data['none_count'], 'сделка', 'сделки', 'сделок') }}</span><span class="desk-only-w-lg"> на {{ $widget::money($data['none_amount'], $symbol) }}</span>
            </div>
        @endif
    </div>
@endif
