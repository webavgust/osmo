{{-- Виджет «Продления» (patch v30): App\Modules\Pub\Desktop\Widgets\Keys\RenewalsWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - низкий блок — подпись и число; шире 300 px — рядом сумма и дельта в процентах;
    - узкий блок (dw xs) — сумма без «на», дельта без пояснения; уже 230 px подпись без периода — «продлено»;
    - шире 230 px — у дельты пояснение «к прошлому отрезку»; выше 96 px — новые ключи и «без курса»;
    - высота от трёх ячеек — список продлений ($rows_max в .desk-fit, «ещё N»): компания — сумма,
      шире 230 px — + дата;
    - высокий блок (dh xl) не узкий — число крупнее (desk-value-lg).
--}}
@php
    $symbol = $data['symbol'];
    $delta = $data['delta_percent'];
    $direction = match (true) {
        $delta === null, abs($delta) < 0.5 => 'flat',
        $delta > 0 => 'up',
        default => 'down',
    };
    $delta_value = $delta === null
        ? '—'
        : ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')) . number_format(abs($delta), 0, ',', ' ') . ' %';

    $narrow = $dw === 'xs';
    $big = $dh === 'xl' && !in_array($dw, ['xs', 'sm'], true);
    // список продлений — когда блок выше двух ячеек; не влезшие строки спрячет .desk-fit
    $with_list = !in_array($dh, ['xs', 'sm'], true);
    $list = $with_list ? array_slice($data['rows'], 0, $rows_max) : [];
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
@endphp
<div class="desk-stack rn">
    {{-- период («Текущий квартал») — только шире 230 px, уже он уходил в многоточие; полностью — в подсказке --}}
    <div class="desk-label desk-nowrap flex-shrink-0" title="Продлено · {{ $data['label'] }} · {{ $data['dates'] }}">
        продлено<span class="desk-only-w-md"> · {{ $data['label'] }}</span>
    </div>

    <div @class(['rn-main', 'desk-stack-grow' => !$with_list])>
        <div @class(['desk-value', 'desk-value-lg' => $big])>{{ $data['count'] }}</div>

        {{-- fs-8 только у суммы: у дельты свой кегль в em, внутри fs-8 он мельче 10 px --}}
        <div class="rn-side">
            <span @class(['desk-muted text-nowrap', 'fs-8' => $narrow]) title="{{ $widget::money($data['amount'], $symbol, false) }}">{{ $narrow ? '' : 'на ' }}{{ $widget::money($data['amount'], $symbol) }}</span>
            @if($settings['compare'])
                <span class="desk-delta {{ $direction }}"
                      @if($data['prev_count'] !== null) title="Прошлый отрезок{{ !empty($data['prev_dates']) ? ' ' . $data['prev_dates'] : '' }}: {{ $data['prev_count'] }} на {{ $widget::money($data['prev_amount'], $symbol, false) }}" @endif>{{ $delta_value }}</span>
                <span class="desk-muted fs-8 text-nowrap desk-only-w-md desk-hide-short">к прошлому отрезку</span>
            @endif
            @if($settings['show_new'])
                <span class="desk-muted fs-8 text-nowrap desk-hide-short" title="Первый ключ компании: {{ $widget::money($data['new_amount'], $symbol, false) }}">новых: {{ $data['new_count'] }}</span>
            @endif
            @if(!empty($data['skipped']))
                <span class="desk-muted fs-8 text-nowrap desk-only-w-md desk-hide-short">без курса: {{ $data['skipped'] }}</span>
            @endif
        </div>
    </div>

    @if($with_list)
        @if(empty($list))
            <div class="desk-stack-grow desk-muted fs-8 rn-list">За {{ mb_strtolower($data['label']) }} продлений не было</div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit rn-list" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $row['company'] }} · {{ $row['date'] }}">{{ $row['company'] }}</a>
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-md">{{ $row['date'] }}</span>
                        <span class="fw-semibold text-nowrap fs-8" title="{{ $widget::money($row['amount'], $symbol, false) }}">{{ $widget::money($row['amount'], $symbol) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8" data-fit-more="ещё {n}"></div>
        @endif
    @endif
</div>
