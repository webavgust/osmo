{{-- Виджет «Оплаты по партнёрам» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\PaymentsByPartnerWidget --}}
@php
    $title = $data['mode'] === 'fact' ? 'поступило' : 'план';
    $color = $data['mode'] === 'fact' ? 'success' : 'primary';

    // низкий блок — только сумма и итоги; выше добавляется список партнёров (шапка с итогами — три строки)
    $compact = in_array($dh, ['xs', 'sm'], true) || $rows < 5;
    // широкий блок — таблица; в остальных список (в самом узком — в две строки, см. finance.css)
    $table = in_array($dw, ['lg', 'xl'], true);
    // строк с запасом: лишние спрячет подгон .desk-fit
    $limit = max(1, min((int) $settings['limit'], $rows_max));
    $list = $compact ? [] : array_slice($data['rows'], 0, $limit);

    $share = fn($value) => number_format((float) $value, $value < 10 ? 1 : 0, ',', ' ') . ' %';
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $hint = fn($row) => $row['name'] . ' · ' . $row['count'] . ' '
        . \App\Facades\Tools::morph($row['count'], 'платёж', 'платежа', 'платежей') . ' · ' . $share($row['share']) . ' от общего';

    // в самом узком блоке сумма в две строки: число крупно, «млн ₽» подписью под ним
    preg_match('/^(.+?)(?: (млрд|млн|тыс\.))?$/u', $widget::compact($data['total']), $parts);
    $unit = trim(($parts[2] ?? '') . ' ' . $data['symbol']);
@endphp
@if($data['count'] === 0 && $data['skipped'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-hand-holding-dollar"></i> <span class="desk-hide-narrow">{{ $data['mode'] === 'fact' ? 'Оплат' : 'Плановых платежей' }} за период нет</span>
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list)])>
        <div>
            <div class="desk-label desk-nowrap desk-hide-narrow" title="{{ $data['label'] }} · {{ $data['dates'] }}">{{ $title }}<span class="desk-only-w-md">: {{ $data['label'] }}</span></div>
            <div class="desk-value text-{{ $color }}" title="{{ $data['mode'] === 'fact' ? 'Поступило' : 'План' }}: {{ $widget::money($data['total'], $data['symbol'], false) }} · {{ $data['dates'] }}">
                {{ $dw === 'xs' ? $parts[1] : $widget::money($data['total'], $data['symbol']) }}
            </div>
            @if($dw === 'xs')
                <div class="desk-label desk-nowrap">{{ $unit }}</div>
            @endif

            {{-- итоги в одну строку: что не влезло по ширине, прячет подгон --}}
            <div class="fin-line desk-fit desk-hide-short mt-1" data-fit-axis="x" data-fit-min="0">
                <span class="desk-muted">
                    {{ $data['partners'] }} {{ \App\Facades\Tools::morph($data['partners'], 'партнёр', 'партнёра', 'партнёров') }}
                </span>
                <span class="desk-muted fs-8">
                    {{ $data['count'] }} {{ \App\Facades\Tools::morph($data['count'], 'платёж', 'платежа', 'платежей') }}
                </span>
                @if($data['others_count'] > 0)
                    <span class="desk-muted fs-8" title="Партнёры за пределами списка: {{ $widget::money($data['others'], $data['symbol'], false) }}">
                        остальные: {{ $data['others_count'] }} · {{ $widget::money($data['others'], $data['symbol']) }}
                    </span>
                @endif
                @if($data['skipped'] > 0)
                    <span class="desk-muted fs-8">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>
        </div>

        @if(!empty($list) && $table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Партнёр</th>
                            <th style="width: 30%;">Доля</th>
                            <th class="num desk-only-w-xl">Платежей</th>
                            <th class="num">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    @if($row['url'] && !$preview)
                                        <a href="{{ $href($row) }}" class="desk-link d-block text-truncate fw-semibold text-hover-primary" title="{{ $hint($row) }}">{{ $row['name'] }}</a>
                                    @else
                                        <span class="d-block text-truncate fw-semibold" title="{{ $hint($row) }}">{{ $row['name'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1">
                                            <i class="bg-{{ $color }}" style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                                        </div>
                                        <span class="desk-muted text-end text-nowrap" style="min-width: 3.2em;">{{ $share($row['share']) }}</span>
                                    </div>
                                </td>
                                <td class="num desk-muted desk-only-w-xl">{{ $row['count'] }}</td>
                                <td class="num fw-bold" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                                    {{ $widget::money($row['amount'], $data['symbol']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @elseif(!empty($list))
            <ul class="desk-list fin-list desk-stack-grow desk-fit" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        @if($row['url'] && !$preview)
                            <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $hint($row) }}">{{ $row['name'] }}</a>
                        @else
                            <span class="desk-grow" title="{{ $hint($row) }}">{{ $row['name'] }}</span>
                        @endif
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md"
                              title="Доля от {{ $widget::money($data['total'], $data['symbol'], false) }}">{{ $share($row['share']) }}</span>
                        <span class="fw-bold fin-amount" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                            {{ $widget::money($row['amount'], $data['symbol']) }}
                        </span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @endif
    </div>
@endif
