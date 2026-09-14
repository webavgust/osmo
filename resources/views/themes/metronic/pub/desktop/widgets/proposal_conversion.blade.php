{{-- Виджет «Конверсия КП» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ProposalConversionWidget --}}
@php
    $number = fn($value) => rtrim(rtrim(number_format((float) $value, 1, ',', ' '), '0'), ',');
    $percent = fn($value) => $number($value) . ' %';

    // сравнивать не с чем, если в прошлом отрезке ни одного решённого КП
    $delta = $data['delta'];
    $direction = match (true) {
        $delta === null, abs($delta) < 0.05 => 'flat',
        $delta > 0 => 'up',
        default => 'down',
    };
    $delta_text = $delta === null
        ? ($dw === 'xs' ? '—' : '— к прошлому отрезку')
        : ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')) . $number(abs($delta)) . ' п. п.';
    $delta_title = $data['previous'] === null
        ? 'В прошлом таком же отрезке решённых КП не было'
        : 'Прошлый такой же отрезок: ' . $percent($data['previous']) . ' (' . $data['previous_resolved'] . ' решённых)';
    $sum = $settings['show_sum'] && $data['amount'] !== null;
    $sum_title = $sum ? 'Сумма основных вариантов выигранных КП: ' . $widget::money($data['amount'], $data['symbol'], false) : '';

    // высота 1–2 ячейки — только процент; с 3 — отклонение, «N из M» и сумма; спарклайн при высоте 3–5
    // только сбоку от процента (ширина ≥6), при высоте ≥6 — под ним; высокий блок — плитки,
    // огромный — график с осями месяцев
    $has_spark = $settings['spark'] && !empty($data['spark']);
    $side = $has_spark && $dh === 'md' && !in_array($dw, ['xs', 'sm'], true);
    $chart = $has_spark && ($side || in_array($dh, ['lg', 'xl'], true));
    $tall = $dh === 'xl' || ($dh === 'lg' && !in_array($dw, ['xs', 'sm'], true));
    $value_class = $dh === 'xl' && $dw !== 'xs' ? 'desk-value-lg' : 'desk-value';
@endphp
<div @class(['desk-stack', 'desk-conv-side' => $side])>
    <div @class(['desk-center' => !$chart || $side, 'desk-conv-head'])>
        <div class="desk-label desk-nowrap" title="Конверсия: {{ $data['label'] }}">
            конверсия@unless($side)<span class="desk-only-w-md">: {{ $data['label'] }}</span>@endunless
        </div>
        <div class="{{ $value_class }}" title="Выиграно {{ $data['won'] }} из {{ $data['resolved'] }} решённых за {{ $data['dates'] }}">
            {{ $percent($data['conversion']) }}
        </div>

        @unless($tall)
            <div class="d-flex column-gap-2 align-items-baseline flex-wrap desk-hide-short">
                <span class="desk-delta {{ $direction }}" title="{{ $delta_title }}">{{ $delta_text }}</span>
                <span class="desk-muted text-nowrap" title="Выиграно и проиграно за период">{{ $data['won'] }} из {{ $data['resolved'] }}</span>
            </div>

            @if($sum)
                {{-- подпись и сумма — отдельные элементы: на узком блоке переносятся, а не режутся --}}
                <div class="d-flex column-gap-1 flex-wrap align-items-baseline desk-muted desk-only-h-md" title="{{ $sum_title }}">
                    <span class="desk-hide-narrow">выиграно на</span>
                    <span @class(['text-nowrap', 'fs-8' => $dw === 'xs'])>{{ $widget::money($data['amount'], $data['symbol']) }}</span>
                </div>
            @endif
        @endunless
    </div>

    @if($tall)
        <div class="desk-tiles desk-conv-tiles">
            <div title="Выиграно КП за период">
                <div class="desk-label">выиграно</div>
                <div class="fw-bold text-success">{{ $data['won'] }}</div>
            </div>
            <div title="Проиграно КП за период">
                <div class="desk-label">проиграно</div>
                <div class="fw-bold text-danger">{{ $data['lost'] }}</div>
            </div>
            <div title="{{ $delta_title }}">
                <div class="desk-label">к прошлому</div>
                <div class="fw-bold desk-delta {{ $direction }}">{{ $delta === null ? '—' : $delta_text }}</div>
            </div>
            <div title="{{ $delta_title }}">
                <div class="desk-label">прошлый отрезок</div>
                <div class="fw-bold">{{ $data['previous'] === null ? '—' : $percent($data['previous']) }}</div>
            </div>
            @if($sum)
                <div title="{{ $sum_title }}">
                    <div class="desk-label">выиграно на</div>
                    <div class="fw-bold">{{ $widget::money($data['amount'], $data['symbol']) }}</div>
                </div>
            @endif
        </div>
    @endif

    @if($chart)
        <div class="desk-stack-grow desk-conv-chart">
            {!! $widget::chart([
                'type' => 'area',
                // огромный блок — с осями и подписями месяцев, остальные — спарклайн
                'sparkline' => !($dh === 'xl' && in_array($dw, ['lg', 'xl'], true)),
                'colors' => ['success'],
                'categories' => $data['spark_labels'],
                'label' => 'Конверсия, %',
                'series' => [['name' => 'Конверсия, %', 'data' => $data['spark']]],
            ]) !!}
        </div>
    @endif
</div>
