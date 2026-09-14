{{-- Виджет «Курсы валют» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\RatesWidget

    Поведение по размерам (ступени, стили — osmo-desktop-widgets/common-3.css):
    - ширина xs: код валюты мелко над курсом, без дельты и графика; внизу дата «на 14.09»;
    - ширина от sm: код, курс, дельта ко вчера; от 230 px — название валюты;
    - от 420 px — шапка колонок и «мин–макс за период»; от 620 px — изменение за период;
    - высота lg/xl и настройка «График» — под списком график первой валюты (не меньше 45 % высоты);
    - строк с запасом ($rows_max) в .desk-fit — лишние прячет подгон.
--}}
@php
    $narrow = $dw === 'xs';
    $list = array_slice($data['rows'], 0, $rows_max);
    $first = $data['rows'][0] ?? null;
    // график рисуем по первой валюте и только когда блок достаточно высокий и не узкий
    $chart = $settings['chart'] && !$narrow && in_array($dh, ['lg', 'xl'], true)
        && $first && count($first['history'] ?? []) > 1;
    $arrow = fn($delta) => $delta === null ? 'flat' : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'));
    // знак по округлённому значению: −0,04 % выводится «0,0 %», а не «−0,0 %»
    $pct = function ($value) {
        $value = round($value, 1);

        return ($value > 0 ? '+' : ($value < 0 ? '−' : '')) . number_format(abs($value), 1, ',', ' ') . ' %';
    };
    $rate = fn($value) => number_format($value, 2, ',', ' ') . ' ₽';
    // мин, макс и изменение за период — по истории курса
    $range = function ($row) {
        $history = $row['history'] ?? [];
        if (count($history) < 2) return null;
        $start = reset($history);

        return [
            'min' => min($history),
            'max' => max($history),
            'change' => $start ? ($row['rate'] - $start) / $start * 100 : null,
        ];
    };
    $short_date = $data['date'] ? substr($data['date'], 0, 5) : '';
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-money-bill-transfer"></i> Курсы не загружены
    </div>
@else
    <div class="desk-stack">
        @if(!$narrow)
            <div class="d-flex gap-2 desk-label dr-head desk-only-w-lg desk-hide-short">
                <span class="desk-grow">Валюта</span>
                <span class="dr-range">мин–макс за {{ $data['days'] }} дн.</span>
                <span class="dr-col desk-only-w-xl">за {{ $data['days'] }} дн.</span>
                <span class="dr-rate">курс</span>
                <span class="dr-col">ко вчера</span>
            </div>
        @endif

        <ul @class(['desk-list desk-fit', 'dr-narrow' => $narrow, 'dr-list' => $chart, 'desk-stack-grow' => !$chart])>
            @foreach($list as $row)
                @if($narrow)
                    <li title="{{ $row['name'] }}@if($row['delta'] !== null) · {{ $pct($row['delta']) }} ко вчера@endif">
                        <span class="desk-muted fs-8">{{ $row['slug'] }}</span>
                        <span class="fw-bold dr-wrap">{{ $rate($row['rate']) }}</span>
                    </li>
                @else
                    @php($period = $range($row))
                    <li title="{{ $row['name'] }}">
                        <span class="fw-bold flex-shrink-0 dr-slug">{{ $row['slug'] }}</span>
                        <span class="desk-muted desk-grow desk-only-w-md" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                        @if($period)
                            <span class="desk-muted fs-8 text-nowrap flex-shrink-0 dr-range desk-only-w-lg">
                                {{ number_format($period['min'], 2, ',', ' ') }}–{{ number_format($period['max'], 2, ',', ' ') }}
                            </span>
                            <span class="desk-delta {{ $arrow($period['change']) }} flex-shrink-0 dr-col desk-only-w-xl">
                                {{ $period['change'] === null ? '—' : $pct($period['change']) }}
                            </span>
                        @endif
                        <span class="fw-semibold text-nowrap ms-auto flex-shrink-0 dr-rate">{{ $rate($row['rate']) }}</span>
                        <span class="desk-delta {{ $arrow($row['delta']) }} flex-shrink-0 dr-col">
                            {{ $row['delta'] === null ? '' : $pct($row['delta']) }}
                        </span>
                    </li>
                @endif
            @endforeach
        </ul>

        @if($chart)
            <div class="dr-chart">
                {!! $widget::sparkline($first['history'], 'primary') !!}
            </div>
        @endif

        <div class="desk-label d-flex gap-1 desk-hide-short">
            <span @class(['text-nowrap', 'text-warning' => $data['stale']])>
                @if($data['stale'])<i class="fa-light fa-triangle-exclamation me-1"></i>@endif
                <span class="desk-hide-narrow">{{ $data['stale'] ? 'курс' : 'курс ЦБ' }} на {{ $data['date'] }}</span>
                <span class="dr-only-narrow">на {{ $short_date }}</span>
            </span>
            @if($chart)
                <span class="text-nowrap desk-only-w-lg">· график {{ $first['slug'] }} за {{ $data['days'] }} дн.</span>
            @endif
        </div>
    </div>
@endif
