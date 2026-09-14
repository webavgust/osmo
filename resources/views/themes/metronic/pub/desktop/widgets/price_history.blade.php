{{-- Виджет «История цен» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\PriceHistoryWidget --}}
@php
    $number = fn($value) => rtrim(rtrim(number_format((float) $value, 1, ',', ' '), '0'), ',');
    $money = fn($value) => $widget::money($value, $data['symbol']);
    $money_full = fn($value) => $widget::money($value, $data['symbol'], false);
    $positions = $data['mode'] === 'positions';

    // отклонение последней редакции: сумма и процент отдельно, чтобы переносились, а не резались
    $delta = $data['diff'];
    $direction = match (true) {
        $delta === null, abs($delta) < 1 => 'flat',
        $delta > 0 => 'up',
        default => 'down',
    };
    $sign = $delta !== null && $delta > 0 ? '+' : '−';
    $delta_money = match (true) {
        $delta === null => '— первая редакция',
        $direction === 'flat' => 'цена не менялась',
        default => $sign . $money(abs($delta)),
    };
    $delta_percent = $direction === 'flat' || $data['diff_p'] === null ? '' : $sign . $number(abs($data['diff_p'])) . ' %';
    // узкий блок: только процент (или прочерк), сумма — в подсказке
    $delta_head = $dw !== 'xs' ? $delta_money : ($delta_percent !== '' ? $delta_percent : ($direction === 'flat' ? '—' : $delta_money));
    $delta_title = 'Отклонение последней редакции от предыдущей'
        . ($direction === 'flat' ? '' : ': ' . $sign . $money_full(abs((float) $delta)));

    $title = trim(($data['number'] !== '' ? '№ ' . $data['number'] . ' · ' : '') . $data['name']);
    $href = $preview || empty($data['url']) ? 'javascript:void(0)' : $data['url'];
    $value_title = $title . ' · ' . $data['block_label'] . ': ' . $money_full($data['current'])
        . ($data['date'] ? ' · редакция от ' . $data['date'] : '');

    // высота 1–2 ячейки — сумма; с 3 — отклонение и список (при ширине ≥6 сбоку от суммы);
    // высокий блок — строки в две строки, с ширины 6 — ещё и график цены по редакциям
    $has_list = !in_array($dh, ['xs', 'sm'], true);
    $list = $has_list ? array_slice($data['rows'], 0, $rows_max) : [];
    $tall = $dh === 'xl' || ($dh === 'lg' && !in_array($dw, ['xs', 'sm'], true));
    $side = !empty($list) && $dh === 'md' && !in_array($dw, ['xs', 'sm'], true);
    $chart = !$positions && count($data['rows']) >= 2 && !in_array($dw, ['xs', 'sm'], true)
        && ($dh === 'xl' || ($dh === 'lg' && in_array($dw, ['lg', 'xl'], true)));
    $value_class = match (true) {
        $side => 'desk-value-sm',
        $dh === 'xl' && in_array($dw, ['lg', 'xl'], true) => 'desk-value-lg',
        default => 'desk-value',
    };

    // узкий блок: «2,6» крупно и «млн ₽» под ним — сумма не уходит в многоточие
    $short = $data['current'] === null ? '—' : $widget::compact($data['current']);
    [$value_number, $value_unit] = preg_match('/^(.+) (млрд|млн|тыс\.)$/u', $short, $m)
        ? [$m[1], $m[2] . ' ' . $data['symbol']]
        : [$short, $data['symbol']];

    // в строке сбоку от суммы места меньше: отклонение и дата появляются на большей ширине
    $w_delta = $side ? 'desk-only-w-lg' : 'desk-only-w-md';
    $w_sub = $side ? 'desk-only-w-xl' : 'desk-only-w-md';

    // отклонение меньше рубля виджет считает нулевым (delta = flat) — знак у него не рисуем
    $row_delta = fn($row) => $row['diff'] === null || $row['delta'] === 'flat'
        ? '—'
        : ($row['diff'] > 0 ? '+' : '−') . $money(abs($row['diff']));
    $row_percent = fn($row) => $row['diff_p'] === null || $row['delta'] === 'flat'
        ? ''
        : ($row['diff'] > 0 ? '+' : '−') . $number(abs($row['diff_p'])) . ' %';
    $row_title = fn($row) => $row['label'] . ' · ' . $money_full($row['value'])
        . ($row['diff'] === null || $row['delta'] === 'flat'
            ? ''
            : ' · отклонение ' . ($row['diff'] > 0 ? '+' : '−') . $money_full(abs($row['diff']))
                . ($row_percent($row) === '' ? '' : ' (' . $row_percent($row) . ')'))
        . ($row['sub'] !== '' ? ' · ' . $row['sub'] : '')
        . ($row['note'] !== '' ? ' · ' . $row['note'] : '');
@endphp
@if(!$data['found'])
    <div class="desk-empty">
        <i class="fa-light fa-timeline-arrow"></i> Выберите КП в настройках виджета
    </div>
@else
    <div @class(['desk-stack', 'desk-ph-side' => $side])>
        <div @class(['desk-center' => empty($list) || $side, 'desk-ph-head'])>
            <div class="desk-label desk-nowrap" title="{{ $title }} · {{ $data['block_label'] }}">
                @if($preview || empty($data['url']))
                    {{ $data['number'] !== '' ? '№ ' . $data['number'] : $data['name'] }}
                @else
                    <a href="{{ $href }}" class="desk-link text-hover-primary">{{ $data['number'] !== '' ? '№ ' . $data['number'] : $data['name'] }}</a>
                @endif
                @unless($side)
                    <span class="desk-muted desk-only-w-md">· {{ $data['block_label'] }}</span>
                @endunless
            </div>

            @if($dw === 'xs')
                <div class="{{ $value_class }}" title="{{ $value_title }}">{{ $value_number }}</div>
                <div class="desk-label text-nowrap">{{ $value_unit }}</div>
            @else
                <div class="{{ $value_class }}" title="{{ $value_title }}">{{ $money($data['current']) }}</div>
            @endif

            <div class="d-flex column-gap-2 align-items-baseline flex-wrap desk-hide-short">
                <span class="desk-delta {{ $direction }}" title="{{ $delta_title }}">{{ $delta_head }}</span>
                @if($delta_percent !== '' && $dw !== 'xs')
                    <span class="desk-delta {{ $direction }}" title="{{ $delta_title }}">({{ $delta_percent }})</span>
                @endif
                <span class="desk-muted text-nowrap" title="Редакций у КП: {{ $data['iterations'] }}">{{ $data['iterations'] }} ред.</span>
                @if($data['date'])
                    <span class="desk-muted text-nowrap desk-hide-narrow" title="Дата последней редакции">{{ $data['date'] }}</span>
                @endif
            </div>

            @if($data['converted'])
                <div class="desk-muted fs-8 desk-only-h-md desk-nowrap" title="Редакции в разных валютах — суммы приведены к рублям по курсу на сегодня{{ $data['rate_unknown'] ? '; курс части валют неизвестен' : '' }}">
                    приведено к рублям{{ $data['rate_unknown'] ? ', курс не по всем валютам' : '' }}
                </div>
            @endif
            @if($positions && $data['note'] !== '' && !empty($list))
                <div class="desk-muted fs-8 desk-only-h-lg desk-nowrap" title="{{ $data['note'] }}">{{ $data['note'] }}</div>
            @endif
        </div>

        @if($chart)
            @php($chrono = array_reverse($data['rows']))
            <div class="desk-ph-chart">
                {!! $widget::chart([
                    'type' => 'area',
                    'series' => [['name' => $data['block_label'], 'data' => array_map('floatval', array_column($chrono, 'value'))]],
                    'categories' => array_column($chrono, 'label'),
                    'colors' => ['primary'],
                    'money' => true,
                    'symbol' => $data['symbol'],
                ]) !!}
            </div>
        @endif

        @if(!empty($list))
            <ul class="desk-list desk-stack-grow desk-fit desk-ph-list" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        @if($tall)
                            <div class="desk-grow">
                                <span class="d-block text-truncate fw-semibold" title="{{ $row_title($row) }}">{{ $row['label'] }}</span>
                                <span class="d-flex flex-wrap column-gap-2 desk-muted fs-8">
                                    <span @class(['text-nowrap' => !$positions, 'desk-nowrap' => $positions]) title="{{ $row['sub'] }}">{{ $row['sub'] }}</span>
                                    @if($row['note'] !== '')
                                        <span class="desk-nowrap" title="{{ $row['note'] }}">{{ $row['note'] }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="text-end flex-shrink-0 ms-auto">
                                <div @class(['fw-semibold text-nowrap', 'fs-8' => $dw === 'xs']) title="{{ $money_full($row['value']) }}">{{ $money($row['value']) }}</div>
                                <div class="d-flex justify-content-end flex-wrap column-gap-2">
                                    {{-- узкий блок: вместо суммы отклонения только процент, сумма в подсказке --}}
                                    <span class="desk-delta {{ $row['delta'] }}" title="{{ $row_title($row) }}">{{ $dw === 'xs' && $row_percent($row) !== '' ? $row_percent($row) : $row_delta($row) }}</span>
                                    @if($row_percent($row) !== '' && $dw !== 'xs')
                                        <span class="desk-delta {{ $row['delta'] }} desk-only-w-md">{{ $row_percent($row) }}</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <span class="desk-grow fw-semibold" title="{{ $row_title($row) }}">{{ $row['label'] }}</span>
                            <span @class(['desk-muted desk-ph-sub', $w_sub, 'flex-shrink-0 text-nowrap' => !$positions, 'desk-nowrap' => $positions]) title="{{ $row['sub'] }}">{{ $row['sub'] }}</span>
                            @if($row['note'] !== '')
                                <span class="desk-muted desk-nowrap desk-only-w-xl desk-ph-note" title="{{ $row['note'] }}">{{ $row['note'] }}</span>
                            @endif
                            <span @class(['fw-semibold text-nowrap flex-shrink-0', 'fs-8' => $dw === 'xs']) title="{{ $money_full($row['value']) }}">{{ $money($row['value']) }}</span>
                            <span class="desk-delta {{ $row['delta'] }} text-nowrap flex-shrink-0 {{ $w_delta }}" title="{{ $row_title($row) }}">{{ $row_delta($row) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @elseif($data['note'] !== '' && $has_list)
            <div class="desk-muted fs-8 desk-hide-short">{{ $data['note'] }}</div>
        @endif
    </div>
@endif
