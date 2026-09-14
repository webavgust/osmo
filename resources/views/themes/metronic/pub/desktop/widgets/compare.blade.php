{{-- Виджет «Сравнение периодов» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\CompareWidget --}}
@php
    $format = function ($value) use ($widget, $data) {
        if ($value === null) return '—';
        if ($data['unit'] === 'money') return $widget::money($value, $data['symbol']);
        if ($data['unit'] === 'percent') return number_format((float) $value, 1, ',', ' ') . ' %';
        return $widget::compact($value);
    };

    $delta = $data['delta_percent'];
    $direction = match (true) {
        $delta === null, abs($delta) < 0.5 => 'flat',
        $delta > 0 => 'up',
        default => 'down',
    };
    $delta_text = $delta === null
        ? '—'
        : ($delta > 0 ? '+' : '−') . number_format(abs($delta), abs($delta) < 10 ? 1 : 0, ',', ' ') . ' %';

    // в низком или узком блоке остаётся только разница, отрезки уходят в подсказку
    $compact = in_array($dw, ['xs', 'sm'], true) || $dh === 'xs';
    $title = $data['a'] && $data['b']
        ? $data['a']['label'] . ': ' . $format($data['a']['value']) . ' · ' . $data['b']['label'] . ': ' . $format($data['b']['value'])
        : '';
@endphp
@if(empty($data['a']))
    <div class="desk-empty">
        <i class="fa-light fa-code-compare"></i> Выберите показатель в настройках
    </div>
@elseif($compact)
    <div class="desk-center" title="{{ $title }}">
        <div class="desk-label desk-nowrap">{{ $data['label'] }}</div>
        <div class="desk-value desk-delta {{ $direction }}">{{ $delta_text }}</div>
        <div class="desk-muted desk-nowrap desk-hide-short">{{ $format($data['a']['value']) }} · было {{ $format($data['b']['value']) }}</div>
    </div>
@else
    <div class="desk-stack">
        <div class="d-flex align-items-baseline gap-2 desk-hide-short">
            <span class="desk-label desk-grow" title="{{ $data['label'] }}">{{ $data['label'] }}</span>
            <span class="desk-delta {{ $direction }} text-nowrap">
                {{ $delta_text }}
                @if($data['delta_abs'] !== null)
                    <span class="desk-muted fw-normal desk-only-w-md">({{ $data['delta_abs'] > 0 ? '+' : '−' }}{{ $format(abs($data['delta_abs'])) }})</span>
                @endif
            </span>
        </div>

        <div class="desk-stack-grow">
            @foreach([$data['a'], $data['b']] as $index => $point)
                <div class="mb-2">
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="desk-label desk-grow">{{ $point['label'] }}</span>
                        <span @class(['fw-bold text-nowrap', 'desk-muted' => $index])>{{ $format($point['value']) }}</span>
                    </div>
                    <div class="desk-bar">
                        <i @class(['bg-primary' => !$index, 'bg-gray-400' => $index]) style="width: {{ $point['share'] }}%"></i>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
