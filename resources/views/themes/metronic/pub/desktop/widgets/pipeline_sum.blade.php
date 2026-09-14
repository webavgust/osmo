{{-- Виджет «Сумма КП в работе» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\PipelineSumWidget --}}
@php
    $delta = $data['delta_percent'];
    $direction = match (true) {
        $delta === null, abs($delta) < 0.5 => 'flat',
        $delta > 0 => 'up',
        default => 'down',
    };
    $delta_short = $delta === null
        ? '—'
        : ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')) . number_format(abs($delta), 0, ',', ' ') . ' %';
    $delta_title = $data['previous'] === null
        ? 'Сравнивать не с чем: отбор по периоду выключен или в прошлом отрезке КП не было'
        : 'К прошлому такому же отрезку: ' . $widget::money($data['previous'], $data['symbol'], false);
    $total_title = $widget::money($data['total'], $data['symbol'], false) . ' · ' . $data['scope_label'] . ' (' . $data['dates'] . ')';

    // высота 1–2 ячейки — сумма (в широком блоке и дельта в строку); с 3 — дельта, число КП и разбивка:
    // при ширине ≥6 сбоку от суммы, уже — под ней; с ширины 12 — таблица;
    // высокий блок — показатели плитками и строки разбивки в две строки
    $split = !empty($data['rows']) && !in_array($dh, ['xs', 'sm'], true);
    $tall = $dh === 'xl' || ($dh === 'lg' && !in_array($dw, ['xs', 'sm'], true));
    // сумма длиннее процента — сбоку от разбивки она помещается только с ширины 12
    $side = $split && $dh === 'md' && in_array($dw, ['lg', 'xl'], true);
    $table = $split && in_array($dw, ['lg', 'xl'], true);
    $list = $split ? array_slice($data['rows'], 0, $rows_max) : [];
    // сумма длинная («53,8 млн ₽»): крупнее — только с ширины 12, сбоку от разбивки — мельче
    $value_class = match (true) {
        $side => 'desk-value-sm',
        $dh === 'xl' && in_array($dw, ['lg', 'xl'], true) => 'desk-value-lg',
        default => 'desk-value',
    };

    // узкий блок: «53,8» крупно и «млн ₽» под ним — сумма не уходит в многоточие
    $short = $widget::compact($data['total']);
    [$number, $unit] = preg_match('/^(.+) (млрд|млн|тыс\.)$/u', $short, $m)
        ? [$m[1], $m[2] . ' ' . $data['symbol']]
        : [$short, $data['symbol']];

    $percent = fn($value) => number_format((float) $value, $value < 10 ? 1 : 0, ',', ' ') . ' %';
    $row_title = fn($row) => $row['label'] . ' · ' . $widget::money($row['amount'], $data['symbol'], false)
        . ' · ' . $row['count'] . ' КП · ' . $percent($row['share']) . ' от суммы';
@endphp
<div @class(['desk-stack', 'desk-pipe-side' => $side])>
    <div @class(['desk-center' => empty($list) || $side, 'desk-pipe-head'])>
        <div class="desk-label desk-nowrap" title="{{ $data['status_label'] }}: {{ $data['label'] }}">
            {{ $data['status_label'] }}@unless($side)<span class="desk-only-w-md">: {{ $data['label'] }}</span>@endunless
        </div>

        @if($dw === 'xs')
            <div class="{{ $value_class }}" title="{{ $total_title }}">{{ $number }}</div>
            <div class="desk-label text-nowrap">{{ $unit }}</div>
        @else
            <div class="d-flex align-items-baseline column-gap-3 min-w-0">
                <div class="{{ $value_class }} flex-shrink-0" title="{{ $total_title }}">{{ $widget::money($data['total'], $data['symbol']) }}</div>
                @if(!$split && $settings['compare'])
                    <span class="desk-delta {{ $direction }} desk-only-w-lg" title="{{ $delta_title }}">{{ $delta_short }}<span class="desk-only-w-xl"> к прошлому отрезку</span></span>
                @endif
            </div>
        @endif

        @unless($tall)
            <div class="d-flex column-gap-2 align-items-baseline flex-wrap desk-hide-short">
                @if($settings['compare'])
                    <span class="desk-delta {{ $direction }}" title="{{ $delta_title }}">{{ $delta_short }}@unless($side)<span class="desk-only-w-md"> к прошлому отрезку</span>@endunless</span>
                @endif
                <span class="desk-muted text-nowrap" title="Столько КП в сумме">{{ $data['count'] }} КП</span>
            </div>

            @if($data['skipped'] > 0)
                <div class="desk-muted fs-8 desk-only-h-md text-nowrap" title="КП в валюте, курса которой на сегодня нет: в сумму не попали">
                    без курса: {{ $data['skipped'] }}
                </div>
            @endif
        @endunless
    </div>

    @if($tall)
        <div class="desk-tiles desk-pipe-tiles">
            @if($settings['compare'])
                <div title="{{ $delta_title }}">
                    <div class="desk-label">к прошлому отрезку</div>
                    <div class="fw-bold desk-delta {{ $direction }}">{{ $delta_short }}</div>
                </div>
                <div title="{{ $delta_title }}">
                    <div class="desk-label">прошлый отрезок</div>
                    <div class="fw-bold">{{ $widget::money($data['previous'], $data['symbol']) }}</div>
                </div>
            @endif
            <div title="Столько КП в сумме">
                <div class="desk-label">КП</div>
                <div class="fw-bold">{{ $data['count'] }}</div>
            </div>
            @if($data['skipped'] > 0)
                <div title="КП в валюте, курса которой на сегодня нет: в сумму не попали">
                    <div class="desk-label">без курса</div>
                    <div class="fw-bold text-warning">{{ $data['skipped'] }}</div>
                </div>
            @endif
        </div>
    @endif

    @if(!empty($list) && $table)
        <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
            <table class="desk-table">
                <thead>
                    <tr>
                        <th>{{ $data['split'] === 'status' ? 'Статус' : 'Менеджер' }}</th>
                        <th class="num">КП</th>
                        <th @class(['desk-only-w-lg' => !$side, 'desk-only-w-xl' => $side]) style="width: 28%;">Доля</th>
                        <th class="num">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $row)
                        <tr>
                            <td class="desk-cut">
                                <span class="d-block text-truncate fw-semibold" title="{{ $row_title($row) }}">{{ $row['label'] }}</span>
                            </td>
                            <td class="num desk-muted">{{ $row['count'] }}</td>
                            <td @class(['desk-only-w-lg' => !$side, 'desk-only-w-xl' => $side])>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="desk-bar flex-grow-1">
                                        <i class="bg-{{ $row['color'] }}" style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                                    </div>
                                    <span class="desk-muted text-end text-nowrap" style="min-width: 3.2em;">{{ $percent($row['share']) }}</span>
                                </div>
                            </td>
                            <td class="num fw-bold" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                                {{ $widget::money($row['amount'], $data['symbol']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif(!empty($list))
        <ul class="desk-list desk-stack-grow desk-fit desk-pipe-list" data-fit-min="0">
            @foreach($list as $row)
                <li>
                    <div class="desk-grow">
                        <span class="d-block text-truncate" title="{{ $row_title($row) }}">{{ $row['label'] }}</span>
                        @if($tall)
                            <span class="d-flex flex-wrap column-gap-2 desk-muted fs-8">
                                <span class="text-nowrap">{{ $row['count'] }} КП</span>
                                <span class="text-nowrap">{{ $percent($row['share']) }}</span>
                            </span>
                        @endif
                    </div>

                    <div class="desk-bar flex-shrink-0 desk-only-w-md" style="width: 4rem;" title="{{ $percent($row['share']) }}">
                        <i class="bg-{{ $row['color'] }}" style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                    </div>

                    <span @class(['fw-semibold text-nowrap flex-shrink-0', 'fs-8' => $dw === 'xs']) title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                        {{ $widget::money($row['amount'], $data['symbol']) }}
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
