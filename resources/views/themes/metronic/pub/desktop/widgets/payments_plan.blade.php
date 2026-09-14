{{-- Виджет «Оплаты: план на период» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\PaymentsPlanWidget --}}
@php
    $percent = fn($value) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 1, ',', ' '), '0'), ',') . ' %';
    $show_done = !empty($settings['done']);
    $buckets = $data['buckets'];
    $count = count($buckets);

    // низкий блок — только сумма и итоги; выше — разбивка по неделям или месяцам таблицей
    // (шапка с итогами занимает три строки)
    $compact = in_array($dh, ['xs', 'sm'], true) || $rows < 5;
    // строк с запасом: лишние спрячет подгон .desk-fit
    $list = $compact || $dw === 'xs' ? [] : array_slice($buckets, 0, $rows_max);
    // высоты хватает на все строки и ещё на график — под таблицей столбики «план и закрыто»
    $chart = !$compact && $dw !== 'xs' && $count > 1 && $rows >= $count + 9;
    // самый узкий блок: вместо таблицы — столбик «закрыто фактом» на всю высоту
    $thermo = $dw === 'xs' && $show_done && $rows >= 5;

    $max = collect($buckets)->max('amount') ?: 0;
    $share = fn($amount) => $max > 0 ? round(min(100, $amount / $max * 100), 1) : 0;
    $closed = $data['percent'] === null ? 0 : min(100, max(0, (float) $data['percent']));
    $full = $widget::money($data['amount'], $data['symbol'], false) . ' · ' . $data['dates'];
    $done_hint = 'Закрыто фактом: ' . $widget::money($data['done'], $data['symbol'], false) . ' по ' . $data['done_count'] . ' '
        . \App\Facades\Tools::morph($data['done_count'], 'платежу', 'платежам', 'платежам');

    // в самом узком блоке сумма в две строки: число крупно, «млн ₽» подписью под ним
    preg_match('/^(.+?)(?: (млрд|млн|тыс\.))?$/u', $widget::compact($data['amount']), $parts);
    $unit = trim(($parts[2] ?? '') . ' ' . $data['symbol']);
@endphp
@if($data['count'] === 0 && $data['skipped'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-calendar-days"></i> <span class="desk-hide-narrow">Плановых платежей за период нет</span>
    </div>
@elseif($dw === 'xs')
    <div @class(['text-center', 'desk-stack' => $thermo, 'desk-center' => !$thermo]) title="План: {{ $data['label'] }} · {{ $full }}">
        <div>
            <div class="desk-value desk-value-sm">{{ $parts[1] }}</div>
            <div class="desk-label text-nowrap">{{ $unit }}</div>
            @if($show_done)
                <div class="desk-only-h-md fw-semibold text-success text-nowrap" title="{{ $done_hint }}">{{ $percent($data['percent']) }}</div>
            @endif
        </div>
        @if($thermo)
            <div class="desk-stack-grow fin-thermo" title="{{ $done_hint }}"><i class="bg-success" style="height: {{ $closed }}%"></i></div>
        @endif
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list)])>
        <div>
            <div class="desk-label desk-nowrap" title="{{ $data['dates'] }}">план<span class="desk-only-w-md">: {{ $data['label'] }}</span></div>
            <div class="desk-value" title="{{ $full }}">{{ $widget::money($data['amount'], $data['symbol']) }}</div>

            {{-- итоги в одну строку: что не влезло по ширине, прячет подгон --}}
            <div class="fin-line desk-fit desk-hide-short" data-fit-axis="x" data-fit-min="0">
                <span class="desk-muted">
                    {{ $data['count'] }} {{ \App\Facades\Tools::morph($data['count'], 'платёж', 'платежа', 'платежей') }}
                </span>
                @if($show_done)
                    <span class="text-success fw-semibold fs-8" title="{{ $done_hint }}">закрыто {{ $percent($data['percent']) }}</span>
                @endif
                @if($data['skipped'] > 0)
                    <span class="desk-muted fs-8">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>
        </div>

        @if(!empty($list))
            <div @class(['desk-fit', 'desk-stack-grow' => !$chart]) data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    <tbody>
                        @foreach($list as $bucket)
                            @php $done = $show_done && $bucket['amount'] > 0 && $bucket['done'] >= $bucket['amount']; @endphp
                            <tr title="{{ $bucket['title'] }}: {{ $widget::money($bucket['amount'], $data['symbol'], false) }}@if($show_done) · закрыто {{ $widget::money($bucket['done'], $data['symbol'], false) }}@endif">
                                <td class="desk-muted">{{ $bucket['label'] }}</td>
                                <td class="desk-cut desk-only-w-md">
                                    <span class="desk-bar d-block">
                                        <i class="bg-{{ $done ? 'success' : 'primary' }}" style="width: {{ $share($bucket['amount']) }}%"></i>
                                    </span>
                                </td>
                                <td class="num fw-semibold">{{ $bucket['amount'] > 0 ? $widget::money($bucket['amount'], $data['symbol']) : '·' }}</td>
                                @if($show_done)
                                    <td class="num text-success desk-only-w-lg">{{ $bucket['done'] > 0 ? $widget::money($bucket['done'], $data['symbol']) : '·' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($chart)
            <div class="desk-stack-grow pp-chart">
                {!! $widget::chart([
                    'type' => 'bar',
                    'money' => true, 'symbol' => $data['symbol'],
                    'legend' => $show_done && in_array($dw, ['lg', 'xl'], true),
                    'colors' => $show_done ? ['primary', 'success'] : ['primary'],
                    'categories' => array_column($buckets, 'label'),
                    'series' => array_values(array_filter([
                        ['name' => 'План', 'data' => array_map(fn($bucket) => round($bucket['amount']), $buckets)],
                        $show_done ? ['name' => 'Закрыто', 'data' => array_map(fn($bucket) => round($bucket['done']), $buckets)] : null,
                    ])),
                ]) !!}
            </div>
        @endif
    </div>
@endif
