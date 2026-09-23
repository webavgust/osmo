{{-- Виджет «Сравнение периодов» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\CompareWidget --}}
@php
    $format = function ($value) use ($widget, $data) {
        if ($value === null) return '—';
        if ($data['unit'] === 'money') return $widget::money($value, $data['symbol']);
        if ($data['unit'] === 'percent') return number_format((float) $value, 1, ',', ' ') . ' %';
        return $widget::compact($value);
    };

    $delta = $data['delta_percent'];
    $abs = $data['delta_abs'];
    // направление — по проценту, а с нулевой базы (процента нет) — по разнице в абсолюте
    $direction = match (true) {
        $delta !== null && abs($delta) < 0.5, $delta === null && !$abs => 'flat',
        ($delta ?? $abs) > 0 => 'up',
        default => 'down',
    };
    $abs_text = $abs === null ? null : ($abs > 0 ? '+' : ($abs < 0 ? '−' : '')) . $format(abs($abs));
    // главная цифра — разница в процентах; с нулевой базы — в абсолюте
    $delta_text = $delta !== null
        ? ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')) . number_format(abs($delta), abs($delta) < 10 ? 1 : 0, ',', ' ') . ' %'
        : ($abs_text ?? '—');
    // разница в абсолюте рядом с процентом (если процент есть)
    $abs_side = $delta !== null ? $abs_text : null;

    $points = $data['a'] ? [$data['a'], $data['b']] : [];
    // идущий период против прошлого сравнивается по то же число: «III квартал 2026 по 23.09»
    $caption = fn($point) => $point['label'] . (!empty($point['until']) ? ' по ' . $point['until'] : '');
    $hint = fn($point) => $caption($point) . (!empty($point['dates']) ? ' (' . $point['dates'] . ')' : '') . ': ' . $format($point['value']);
    $title = $points ? $hint($points[0]) . ' · ' . $hint($points[1]) : '';

    // раскладка: строка (высота 1–2), колонка в узком блоке, строки-полоски, столбики (высота от 6)
    $mode = match (true) {
        $dh === 'xs' => 'inline',
        $dh === 'sm' => 'line',
        in_array($dh, ['lg', 'xl'], true) => 'columns',
        in_array($dw, ['xs', 'sm'], true) => 'narrow',
        default => 'rows',
    };
@endphp
@if(empty($data['a']))
    <div class="desk-empty">
        <i class="fa-light fa-code-compare"></i>
        <span class="desk-hide-narrow">Выберите показатель в настройках</span>
    </div>
@elseif($mode === 'inline')
    {{-- одна ячейка высотой: всё в строку — подпись, разница, значения --}}
    <div class="desk-center" title="{{ $title }}">
        <div class="d-flex align-items-baseline gap-2 min-w-0">
            <span class="desk-label desk-grow desk-hide-narrow" title="{{ $data['label'] }}">{{ $data['label'] }}</span>
            <span class="desk-delta {{ $direction }} text-nowrap flex-shrink-0">{{ $delta_text }}</span>
            <span class="desk-muted text-nowrap flex-shrink-0 desk-only-w-md">{{ $format($points[0]['value']) }} · было {{ $format($points[1]['value']) }}</span>
        </div>
    </div>
@elseif($mode === 'line')
    {{-- низкий блок: подпись и разница слева, значения отрезков справа (шире 230 px) --}}
    <div class="desk-center" title="{{ $title }}">
        <div class="d-flex align-items-center gap-3 min-w-0">
            <div class="flex-grow-1 min-w-0">
                <div class="desk-label desk-nowrap" title="{{ $data['label'] }}">{{ $data['label'] }}</div>
                <div class="desk-value desk-delta {{ $direction }}">{{ $delta_text }}</div>
            </div>
            <div class="flex-shrink-0 text-end desk-only-w-md cmp-pair">
                @foreach($points as $index => $point)
                    <div @class(['text-nowrap', 'desk-muted' => $index, 'fw-bold' => !$index])>
                        <span class="desk-muted fw-normal desk-only-w-lg">{{ $point['label'] }}</span>
                        {{ $format($point['value']) }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@elseif($mode === 'narrow')
    {{-- узкий блок средней высоты: подпись, разница крупно, значения друг под другом --}}
    <div class="desk-stack cmp-narrow" title="{{ $title }}">
        <div class="desk-label desk-nowrap cmp-caption" title="{{ $data['label'] }}">{{ $data['label'] }}</div>
        <div class="desk-value desk-delta {{ $direction }}">{{ $delta_text }}</div>
        <div class="cmp-vals">
            <div class="fw-bold text-nowrap">{{ $format($points[0]['value']) }}</div>
            <div class="desk-muted text-nowrap"><span class="desk-hide-narrow">было </span>{{ $format($points[1]['value']) }}</div>
        </div>
    </div>
@elseif($mode === 'rows')
    <div class="desk-stack">
        <div class="d-flex align-items-baseline gap-2">
            <span class="desk-label desk-grow" title="{{ $data['label'] }}">{{ $data['label'] }}</span>
            <span class="desk-delta {{ $direction }} text-nowrap">
                {{ $delta_text }}
                {{-- разница в абсолюте — только в широком блоке: значения отрезков и так видны ниже --}}
                @if($abs_side !== null)
                    <span class="desk-muted fw-normal desk-only-w-lg">({{ $abs_side }})</span>
                @endif
            </span>
        </div>

        <div class="desk-stack-grow cmp-rows">
            @foreach($points as $index => $point)
                <div title="{{ $hint($point) }}">
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="desk-label desk-grow">{{ $point['label'] }}@if(!empty($point['until']))<span class="desk-only-w-lg"> по {{ $point['until'] }}</span>@endif</span>
                        <span @class(['fw-bold text-nowrap', 'desk-muted' => $index])>{{ $format($point['value']) }}</span>
                    </div>
                    <div class="desk-bar">
                        <i @class(['bg-primary' => !$index, 'bg-gray-400' => $index]) style="width: {{ $point['share'] }}%"></i>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    {{-- высокий блок: разница крупно, под ней два столбика во всю оставшуюся высоту --}}
    <div class="desk-stack cmp-tall">
        <div class="desk-label desk-nowrap cmp-caption" title="{{ $data['label'] }}">{{ $data['label'] }}</div>
        <div class="d-flex align-items-baseline gap-2 flex-wrap">
            <span class="desk-value desk-delta {{ $direction }}">{{ $delta_text }}</span>
            @if($abs_side !== null)
                <span class="desk-muted text-nowrap desk-only-w-md">{{ $abs_side }}</span>
            @endif
        </div>
        {{-- узкий блок: значения строками — в столбик шириной 30 px сумма не встаёт --}}
        <div class="cmp-vals cmp-vals-narrow">
            <div class="fw-bold text-nowrap">{{ $format($points[0]['value']) }}</div>
            <div class="desk-muted text-nowrap">{{ $format($points[1]['value']) }}</div>
        </div>
        <div class="desk-stack-grow cmp-cols">
            @foreach($points as $index => $point)
                <div class="cmp-col" title="{{ $hint($point) }}">
                    <div @class(['cmp-col-value text-nowrap desk-hide-narrow', 'fw-bold' => !$index, 'desk-muted' => $index])>{{ $format($point['value']) }}</div>
                    <div class="cmp-col-track">
                        <i @class(['bg-primary' => !$index, 'bg-gray-400' => $index]) style="height: {{ max(1, $point['share']) }}%"></i>
                    </div>
                    <div class="desk-label cmp-col-label desk-hide-narrow">{{ $point['label'] }}@if(!empty($point['until']))<span class="desk-only-w-lg"> по {{ $point['until'] }}</span>@endif</div>
                </div>
            @endforeach
        </div>
    </div>
@endif
