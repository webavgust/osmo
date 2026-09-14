{{-- Виджет «Число» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\KpiWidget --}}
@php
    // число по единице показателя: коротко (6,8 млн ₽) или полностью (для подсказки)
    $format = function ($number, bool $short = true) use ($widget, $data) {
        if ($number === null) return '—';
        if ($data['unit'] === 'money') return $widget::money($number, $data['symbol'], $short);
        if ($data['unit'] === 'percent') {
            $text = number_format((float) $number, 1, ',', ' ');
            return (str_ends_with($text, ',0') ? substr($text, 0, -2) : $text) . ' %';
        }
        return $short ? $widget::compact($number) : number_format((float) $number, 0, ',', ' ');
    };

    $label = trim((string) $settings['caption']) !== '' ? trim((string) $settings['caption']) : $data['label'];
    if (!empty($data['period_label'])) {
        $label .= ' · ' . $data['period_label'];
    }

    $delta = $data['delta_percent'];
    $direction = match (true) {
        $delta === null, abs($delta) < 0.5 => 'flat',
        $delta > 0 => 'up',
        default => 'down',
    };
    $delta_short = $delta === null
        ? '—'
        : ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')) . number_format(abs($delta), 0, ',', ' ') . ' %';
    $delta_text = $delta_short . ' к прошлому';

    $full = $format($data['value'], false);

    // ряд за 12 месяцев: спарклайн с высоты 3 ячейки, в огромном блоке — график с подписями месяцев
    $series = $data['series'] ?? [];
    $values = array_column($series, 'value');
    $spark = count($values) > 1 && !in_array($dh, ['xs', 'sm'], true);
    $axis = $spark && $dh === 'xl' && in_array($dw, ['md', 'lg', 'xl'], true);
    $color = ['up' => 'success', 'down' => 'danger', 'flat' => 'primary'][$direction];
    // крупнее число — в высоком и широком блоке (в среднем по ширине крупный кегль не влезает)
    $big = in_array($dh, ['lg', 'xl'], true) && in_array($dw, ['lg', 'xl'], true);
    // показатель «на сейчас» (без ряда) в высоком блоке: число по центру, дата среза и ссылка внизу
    $footer = !$spark && in_array($dh, ['lg', 'xl'], true);
    $source = !$preview && !empty($data['url']) ? $data['url'] : null;
@endphp
@if($data['value'] === null)
    <div class="desk-empty">
        <i class="fa-light fa-hashtag"></i> <span class="desk-hide-narrow">Нет данных</span>
    </div>
@elseif($dw === 'xs')
    {{-- узкий блок: число (переносится по пробелу, без многоточия); выше — подпись и дельта; ещё выше — спарклайн --}}
    <div @class(['align-items-center text-center', 'desk-stack' => $spark || $footer, 'desk-center' => !$spark && !$footer]) title="{{ $label }}: {{ $full }}">
        <div @class(['mw-100', 'dk-head' => !$footer, 'desk-stack-grow d-flex flex-column justify-content-center' => $footer])>
            <div class="desk-value desk-value-sm text-wrap">
                {{ $data['unit'] === 'money' ? $widget::compact($data['value']) : $format($data['value']) }}
            </div>
            <div class="desk-only-h-md"><div class="desk-label dk-label-wrap">{{ $label }}</div></div>
            @if($data['previous'] !== null)
                <div class="desk-only-h-md"><span class="desk-delta {{ $direction }}">{{ $delta_short }}</span></div>
            @endif
        </div>
        @if($spark)
            <div class="desk-stack-grow dk-spark w-100">{!! $widget::sparkline($values, $color) !!}</div>
        @elseif($footer)
            <div class="desk-label">{{ empty($data['period_label']) ? 'на ' . now()->format('d.m') : $data['period_label'] }}</div>
        @endif
    </div>
@else
    <div @class(['desk-stack' => $spark || $footer, 'desk-center' => !$spark && !$footer])>
        <div @class(['dk-head' => !$footer, 'desk-stack-grow d-flex flex-column justify-content-center min-w-0' => $footer])>
            <div class="desk-label desk-nowrap dk-label" title="{{ $label }}">{{ $label }}</div>
            <div @class(['desk-value', 'desk-value-lg' => $big]) title="{{ $full }}">{{ $format($data['value']) }}</div>

            @if($data['previous'] !== null)
                <div class="d-flex gap-2 align-items-baseline flex-wrap desk-hide-short">
                    <span class="desk-delta {{ $direction }}">{{ $delta_text }}</span>
                    <span class="desk-muted desk-only-h-md">было: {{ $format($data['previous']) }}</span>
                </div>
            @endif
        </div>
        @if($axis)
            <div class="desk-stack-grow dk-spark">
                {!! $widget::chart([
                    'type' => 'area', 'colors' => [$color], 'legend' => false,
                    'categories' => array_column($series, 'label'),
                    'series' => [['name' => $label, 'data' => $values]],
                    'money' => $data['unit'] === 'money', 'symbol' => $data['symbol'],
                ]) !!}
            </div>
            <div class="desk-label desk-nowrap">за 12 месяцев</div>
        @elseif($spark)
            <div class="desk-stack-grow dk-spark">{!! $widget::sparkline($values, $color) !!}</div>
        @elseif($footer)
            <div class="d-flex align-items-center gap-2 desk-label">
                <span class="desk-nowrap">{{ empty($data['period_label']) ? 'на сегодня, ' . now()->format('d.m.Y') : 'за период: ' . $data['period_label'] }}</span>
                @if($source)
                    <a href="{{ $source }}" class="desk-link text-hover-primary ms-auto text-nowrap desk-only-w-md">Открыть <i class="fa-light fa-arrow-right fs-8"></i></a>
                @endif
            </div>
        @endif
    </div>
@endif
