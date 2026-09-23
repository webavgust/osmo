{{-- Виджет «Прогресс к цели» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\ProgressWidget

    Поведение по размерам (ступени, стили — osmo-desktop-widgets/common-3.css):
    - ширина xs: процент без дробной части и шкала; выше — факт и цель столбиком (fs-8);
    - ширина от sm, высота до md: процент, «факт из цели» рядом (переносится строкой ниже), шкала,
      «осталось» от высоты 110 px;
    - высота lg/xl: подпись сверху, крупный процент и толстая шкала посередине, внизу плитки
      «Факт / Цель / Осталось» (или «Сверх плана»);
    - кольцо (настройка) — вместо процента и шкалы, когда высота от md, ширина от sm и план не перевыполнен.
--}}
@php
    $format = function ($value) use ($widget, $data) {
        if ($value === null) return '—';
        if ($data['unit'] === 'money') return $widget::money($value, $data['symbol']);
        if ($data['unit'] === 'percent') return number_format((float) $value, 1, ',', ' ') . ' %';
        return $widget::compact($value);
    };

    $percent = $data['percent'];
    $narrow = $dw === 'xs';
    $tall = in_array($dh, ['lg', 'xl'], true);
    // в узком блоке и от 100 % процент целым: «128,5 %» в две колонки не влезает;
    // от 1000 % — во сколько раз перевыполнен план («×304»), полный процент в title
    $full = $percent === null ? '' : rtrim(rtrim(number_format($percent, 1, ',', ' '), '0'), ',') . ' %';
    $text = match (true) {
        $percent === null => '—',
        $percent >= 1000 => '×' . $widget::compact(round($percent / 100)),
        $narrow || $percent >= 100 => number_format(round($percent), 0, ',', ' ') . ' %',
        default => $full,
    };
    $width = $percent === null ? 0 : min(100, max(0, $percent));
    $caption = $data['label'] . ($data['period_label'] ? ' · ' . $data['period_label'] : '');
    // перевыполнение показываем вместо нулевого остатка
    $over = $data['value'] !== null && $data['goal'] !== null && $data['value'] > $data['goal'];
    // кольцо просят настройкой и рисуем, когда блоку хватает высоты и ширины. При перевыполнении
    // кольца нет: оно полное и в центре пишет «100 %» (подпись — значение ряда, а ряд ограничен 100),
    // а процент и «сверх плана» честно показывает обычная раскладка
    $ring = $settings['ring'] && !$narrow && in_array($dh, ['md', 'lg', 'xl'], true) && $percent !== null && !$over;
@endphp
@if($data['value'] === null || $data['goal'] === null || $data['goal'] <= 0)
    <div class="desk-empty">
        <i class="fa-light fa-bullseye-arrow"></i>
        {{ $data['value'] === null ? 'Выберите показатель в настройках' : 'Задайте цель в настройках' }}
    </div>
@elseif($tall)
    <div class="desk-stack">
        <div class="desk-label desk-nowrap dp-caption" title="{{ $caption }}">{{ $caption }}</div>
        <div class="desk-stack-grow">
            @if($ring)
                {!! $widget::chart([
                    'type' => 'radialBar',
                    'colors' => [$data['color']],
                    'series' => [round($width, 1)],
                    'labels' => [$data['label']],
                    'label' => false,
                ]) !!}
            @else
                <div class="desk-center">
                    <div class="desk-value desk-value-lg text-{{ $data['color'] }}" title="{{ $full }}">{{ $text }}</div>
                    <div class="desk-bar dp-bar mt-2">
                        <i class="bg-{{ $data['color'] }}" style="width: {{ $width }}%"></i>
                    </div>
                </div>
            @endif
        </div>
        <div class="desk-tiles dp-tiles">
            <div>
                <div class="desk-label">Факт</div>
                <div class="desk-value-sm fw-bold dp-wrap" title="{{ $format($data['value']) }}">{{ $format($data['value']) }}</div>
            </div>
            <div>
                <div class="desk-label">Цель</div>
                <div class="desk-value-sm fw-bold dp-wrap">{{ $format($data['goal']) }}</div>
            </div>
            <div>
                <div class="desk-label">{{ $over ? 'Сверх плана' : 'Осталось' }}</div>
                <div @class(['desk-value-sm fw-bold dp-wrap', 'text-success' => $over])>
                    {{ $over ? '+' . $format($data['value'] - $data['goal']) : $format($data['left']) }}
                </div>
            </div>
        </div>
    </div>
@elseif($ring)
    <div class="desk-stack">
        <div class="desk-label desk-nowrap dp-caption desk-hide-short" title="{{ $caption }}">{{ $caption }}</div>
        <div class="desk-stack-grow">
            {!! $widget::chart([
                'type' => 'radialBar',
                'colors' => [$data['color']],
                'series' => [round($width, 1)],
                'labels' => [$data['label']],
                'label' => false,
            ]) !!}
        </div>
        <div class="desk-label dp-wrap text-center">
            {{ $format($data['value']) }} из {{ $format($data['goal']) }}
        </div>
    </div>
@elseif($narrow)
    <div class="desk-center">
        <div class="desk-label desk-nowrap dp-caption desk-hide-short" title="{{ $caption }}">{{ $caption }}</div>
        <div class="desk-value text-{{ $data['color'] }}" title="{{ $format($data['value']) }} из {{ $format($data['goal']) }}">{{ $text }}</div>
        <div class="desk-bar mt-1">
            <i class="bg-{{ $data['color'] }}" style="width: {{ $width }}%"></i>
        </div>
        <div class="desk-muted fs-8 dp-wrap mt-1 desk-only-h-md">{{ $format($data['value']) }}</div>
        <div class="desk-muted fs-8 dp-wrap desk-only-h-md">из {{ $format($data['goal']) }}</div>
    </div>
@else
    <div class="desk-center">
        <div class="desk-label desk-nowrap dp-caption" title="{{ $caption }}">{{ $caption }}</div>
        <div class="d-flex flex-wrap align-items-baseline column-gap-2 dp-row">
            <span class="desk-value flex-shrink-0 text-{{ $data['color'] }}" title="{{ $full }}">{{ $text }}</span>
            <span class="desk-muted dp-wrap dp-fact">{{ $format($data['value']) }} из {{ $format($data['goal']) }}</span>
        </div>
        <div class="desk-bar mt-1">
            <i class="bg-{{ $data['color'] }}" style="width: {{ $width }}%"></i>
        </div>
        <div class="desk-muted fs-8 dp-wrap mt-1 desk-only-h-md">
            @if($over)
                сверх плана +{{ $format($data['value'] - $data['goal']) }}
            @else
                осталось {{ $format($data['left']) }}
            @endif
        </div>
    </div>
@endif
