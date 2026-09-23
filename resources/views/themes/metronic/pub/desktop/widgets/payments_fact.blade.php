{{-- Виджет «Оплаты за период» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\PaymentsFactWidget --}}
@php
    $delta = $data['delta_percent'];
    $direction = match (true) {
        $delta === null, abs($delta) < 0.5 => 'flat',
        $delta > 0 => 'up',
        default => 'down',
    };
    $delta_short = $delta === null
        ? '—'
        : ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')) . number_format(abs($delta), 0, ',', ' ') . ' %';
    // даты прошлого отрезка: у идущего периода он обрезан по то же число
    $prev_dates = !empty($data['prev_dates']) ? ' ' . $data['prev_dates'] : '';
    $prev_caption = !empty($data['prev_until']) ? 'к прошлому по ' . $data['prev_until'] : 'к прошлому отрезку';
    $delta_hint = $data['previous'] !== null
        ? 'Прошлый отрезок' . $prev_dates . ': ' . $widget::money($data['previous'], $data['symbol'], false)
        : 'Оплат в прошлом отрезке' . $prev_dates . ' нет — сравнивать не с чем';
    $full = $data['label'] . ': ' . $widget::money($data['value'], $data['symbol'], false);

    // график оплат по дням, неделям или месяцам отрезка: спарклайн, в широком и высоком блоке — с осями
    $series = $data['series'] ?? null;
    $spark = !empty($series) && count($series['values']) > 1 && $rows >= 6;
    $axis = $spark && in_array($dw, ['md', 'lg', 'xl'], true) && $rows >= 9;
    // огромный блок — число крупнее
    $big = in_array($dw, ['lg', 'xl'], true) && in_array($dh, ['lg', 'xl'], true);

    // в самом узком блоке сумма в две строки: число и «млн ₽» подписью
    preg_match('/^(.+?)(?: (млрд|млн|тыс\.))?$/u', $widget::compact($data['value']), $parts);
    $unit = trim(($parts[2] ?? '') . ' ' . $data['symbol']);
@endphp
@if($dw === 'xs')
    <div @class(['text-center', 'desk-stack' => $spark, 'desk-center' => !$spark]) title="{{ $full }}">
        <div>
            <div class="desk-value desk-value-sm">{{ $parts[1] }}</div>
            <div class="desk-label text-nowrap">{{ $unit }}</div>
            @if($settings['compare'])
                <div class="desk-only-h-md"><span class="desk-delta {{ $direction }}" title="{{ $delta_hint }}">{{ $delta_short }}</span></div>
            @endif
        </div>
        @if($spark)
            <div class="desk-stack-grow pf-chart">{!! $widget::sparkline($series['values'], 'success') !!}</div>
        @endif
    </div>
@else
    <div @class(['desk-stack' => $spark, 'desk-center' => !$spark])>
        <div>
            <div class="desk-label desk-nowrap" title="{{ $data['label'] }}">{{ $data['label'] }}</div>
            <div @class(['desk-value', 'desk-value-lg' => $big]) title="{{ $full }}">{{ $widget::money($data['value'], $data['symbol']) }}</div>

            {{-- дельта, число оплат, прошлый отрезок в строку: что не влезло по ширине, прячет подгон --}}
            <div class="fin-line desk-fit desk-hide-short" data-fit-axis="x" data-fit-min="0">
                @if($settings['compare'])
                    <span class="desk-delta {{ $direction }}" title="{{ $delta_hint }}">
                        {{ $delta_short }}<span class="desk-only-w-md"> {{ $prev_caption }}</span>
                    </span>
                @endif
                <span class="desk-muted">
                    {{ $data['count'] }} {{ \App\Facades\Tools::morph($data['count'], 'оплата', 'оплаты', 'оплат') }}
                </span>
                @if($settings['compare'] && $data['previous'] !== null)
                    <span class="desk-muted" title="{{ $delta_hint }}">было: {{ $widget::money($data['previous'], $data['symbol']) }}</span>
                @endif
                @if($data['skipped'] > 0)
                    <span class="desk-muted fs-8">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>
        </div>

        @if($axis)
            <div class="desk-stack-grow pf-chart">
                {!! $widget::chart([
                    'type' => 'area', 'colors' => ['success'], 'legend' => false,
                    'categories' => $series['labels'],
                    'series' => [['name' => 'Оплаты', 'data' => $series['values']]],
                    'money' => true, 'symbol' => $data['symbol'],
                ]) !!}
            </div>
        @elseif($spark)
            <div class="desk-stack-grow pf-chart">{!! $widget::sparkline($series['values'], 'success') !!}</div>
        @endif
    </div>
@endif
