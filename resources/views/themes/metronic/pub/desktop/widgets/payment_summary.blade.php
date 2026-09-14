{{-- Виджет «Оплаты: план и факт» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\PaymentSummaryWidget --}}
@php
    $percent = fn($value) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 1, ',', ' '), '0'), ',') . ' %';
    $diff = (float) $data['diff'];
    $direction = match (true) {
        abs($diff) < 1 => 'flat',
        $diff > 0 => 'up',
        default => 'down',
    };
    $diff_text = ($diff > 0 ? '+' : ($diff < 0 ? '−' : '')) . $widget::money(abs($diff), $data['symbol']);
    $bar = $data['percent'] === null ? 0 : min(100, max(0, (float) $data['percent']));
    $hint = 'Факт ' . $widget::money($data['fact'], $data['symbol'], false) . ' из плана '
        . $widget::money($data['plan'], $data['symbol'], false) . ' · ' . $data['dates'];

    // низкий блок — исполнение и факт одной строкой; выше — плитки план / факт / исполнение / отклонение
    $compact = in_array($dh, ['xs', 'sm'], true);
    // крупный блок — график «план и факт» по дням, неделям или месяцам периода
    // (в блоке шириной 3–5 плитки встают в столбик, поэтому графику нужно больше высоты)
    $series = $data['series'] ?? null;
    $chart = !$compact && $dw !== 'xs' && !empty($series) && count($series['labels']) > 1
        && $rows >= ($dw === 'sm' ? 13 : 9);
    // самый узкий блок: вместо графика — столбик исполнения на всю высоту
    $thermo = $dw === 'xs' && $rows >= 5;

    // в самом узком блоке сумма в две строки: число и «млн ₽» подписью
    preg_match('/^(.+?)(?: (млрд|млн|тыс\.))?$/u', $widget::compact($data['fact']), $parts);
    $unit = trim(($parts[2] ?? '') . ' ' . $data['symbol']);
@endphp
@if($data['plan_count'] === 0 && $data['fact_count'] === 0 && $data['skipped'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-scale-balanced"></i> <span class="desk-hide-narrow">Ни плана, ни оплат за период нет</span>
    </div>
@elseif($dw === 'xs')
    <div @class(['text-center', 'desk-stack' => $thermo, 'desk-center' => !$thermo]) title="{{ $hint }}">
        <div>
            <div class="desk-value desk-value-sm">{{ $percent($data['percent']) }}</div>
            <div class="desk-only-h-md">
                <div class="desk-label">факт</div>
                <div class="fw-bold text-success text-nowrap">{{ $parts[1] }}</div>
                <div class="desk-label text-nowrap">{{ $unit }}</div>
            </div>
        </div>
        @if($thermo)
            <div class="desk-stack-grow fin-thermo" title="Исполнение плана: {{ $percent($data['percent']) }}">
                <i class="bg-{{ $bar >= 100 ? 'success' : 'primary' }}" style="height: {{ $bar }}%"></i>
            </div>
        @endif
    </div>
@elseif($compact)
    <div class="desk-center">
        <div class="desk-label desk-nowrap" title="{{ $data['dates'] }}">исполнение<span class="desk-only-w-md">: {{ $data['label'] }}</span></div>
        {{-- исполнение, факт и отклонение в строку: что не влезло по ширине, прячет подгон --}}
        <div class="fin-line desk-fit" data-fit-axis="x">
            <span class="desk-value" title="{{ $hint }}">{{ $percent($data['percent']) }}</span>
            <span class="desk-muted">факт {{ $widget::money($data['fact'], $data['symbol']) }}</span>
            <span class="desk-delta {{ $direction }}" title="Отклонение от плана: {{ $widget::money($diff, $data['symbol'], false) }}">{{ $diff_text }}</span>
        </div>
    </div>
@else
    <div class="desk-stack">
        <div class="desk-label desk-nowrap desk-only-h-md" title="{{ $data['dates'] }}">план и факт<span class="desk-only-w-md">: {{ $data['label'] }}</span></div>

        {{-- плитки: колонок 1, 2 или 4 по ширине блока (finance.css); не влезшие по высоте прячет подгон --}}
        <div class="ps-tiles desk-fit">
            <div>
                <div class="desk-label desk-nowrap">план</div>
                <div class="ps-num" title="{{ $widget::money($data['plan'], $data['symbol'], false) }}">{{ $widget::money($data['plan'], $data['symbol']) }}</div>
                <div class="desk-muted fs-8 desk-nowrap desk-only-h-lg">
                    {{ $data['plan_count'] }} {{ \App\Facades\Tools::morph($data['plan_count'], 'платёж', 'платежа', 'платежей') }}
                </div>
            </div>
            <div>
                <div class="desk-label desk-nowrap">факт</div>
                <div class="ps-num text-success" title="{{ $widget::money($data['fact'], $data['symbol'], false) }}">{{ $widget::money($data['fact'], $data['symbol']) }}</div>
                <div class="desk-muted fs-8 desk-nowrap desk-only-h-lg">
                    {{ $data['fact_count'] }} {{ \App\Facades\Tools::morph($data['fact_count'], 'оплата', 'оплаты', 'оплат') }}
                </div>
            </div>
            <div>
                <div class="desk-label desk-nowrap">исполнение</div>
                <div class="ps-num" title="{{ $hint }}">{{ $percent($data['percent']) }}</div>
                <div class="desk-bar mt-1 desk-only-h-lg"><i class="bg-{{ $bar >= 100 ? 'success' : 'primary' }}" style="width: {{ $bar }}%"></i></div>
            </div>
            <div>
                <div class="desk-label desk-nowrap">отклонение</div>
                <div class="ps-num desk-delta {{ $direction }}" title="Факт минус план: {{ $widget::money($diff, $data['symbol'], false) }}">{{ $diff_text }}</div>
                @if($settings['open'])
                    <div class="desk-muted fs-8 desk-nowrap desk-only-h-lg" title="Плановые платежи периода без факта: {{ $widget::money($data['open'], $data['symbol'], false) }}">
                        остаток {{ $widget::money($data['open'], $data['symbol']) }}
                    </div>
                @endif
            </div>
        </div>

        <div class="fin-line desk-fit desk-hide-short" data-fit-axis="x" data-fit-min="0">
            @if($settings['open'] && $data['open_count'] > 0)
                <span class="badge badge-light-info fs-8"
                      title="Плановые платежи периода без факта: {{ $widget::money($data['open'], $data['symbol'], false) }}">
                    ждём ещё: {{ $data['open_count'] }} · {{ $widget::money($data['open'], $data['symbol']) }}
                </span>
            @endif
            @if($data['overdue_count'] > 0)
                <span class="badge badge-light-danger fs-8"
                      title="Просрочено внутри периода: {{ $widget::money($data['overdue'], $data['symbol'], false) }}">
                    просрочено: {{ $data['overdue_count'] }} · {{ $widget::money($data['overdue'], $data['symbol']) }}
                </span>
            @endif
            @if($data['skipped'] > 0)
                <span class="desk-muted fs-8">без курса: {{ $data['skipped'] }}</span>
            @endif
        </div>

        @if($chart)
            <div class="desk-stack-grow ps-chart">
                {!! $widget::chart([
                    'type' => 'bar',
                    'money' => true, 'symbol' => $data['symbol'],
                    'legend' => in_array($dw, ['lg', 'xl'], true),
                    'colors' => ['gray-500', 'success'],
                    'categories' => $series['labels'],
                    'series' => [['name' => 'План', 'data' => $series['plan']], ['name' => 'Факт', 'data' => $series['fact']]],
                ]) !!}
            </div>
        @endif
    </div>
@endif
