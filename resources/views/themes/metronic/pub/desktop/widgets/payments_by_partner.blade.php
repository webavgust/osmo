{{-- Виджет «Оплаты по партнёрам» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\PaymentsByPartnerWidget --}}
@php
    $title = $data['mode'] === 'fact' ? 'поступило' : 'план';
    $color = $data['mode'] === 'fact' ? 'success' : 'primary';

    // в узком или низком блоке — только итог, дальше добавляется список партнёров
    $compact = in_array($dw, ['xs', 'sm'], true) || in_array($dh, ['xs', 'sm'], true);
    $table = in_array($dw, ['lg', 'xl'], true);
    $limit = max(1, min((int) $settings['limit'], $rows - ($table ? 3 : 2)));
    $list = $compact ? [] : array_slice($data['rows'], 0, $limit);

    $share = fn($value) => number_format((float) $value, $value < 10 ? 1 : 0, ',', ' ') . ' %';
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
@endphp
@if($data['count'] === 0 && $data['skipped'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-hand-holding-dollar"></i> Оплат за период нет
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list)])>
        <div>
            <div class="desk-label desk-nowrap" title="{{ $data['dates'] }}">{{ $title }}: {{ $data['label'] }}</div>
            <div class="desk-value text-{{ $color }}" title="{{ $widget::money($data['total'], $data['symbol'], false) }} · {{ $data['dates'] }}">
                {{ $widget::money($data['total'], $data['symbol']) }}
            </div>

            <div class="d-flex gap-2 align-items-baseline flex-wrap desk-hide-short">
                <span class="desk-muted text-nowrap">
                    {{ $data['partners'] }} {{ \App\Facades\Tools::morph($data['partners'], 'партнёр', 'партнёра', 'партнёров') }}
                </span>
                <span class="desk-muted fs-8 text-nowrap desk-only-w-md">
                    {{ $data['count'] }} {{ \App\Facades\Tools::morph($data['count'], 'платёж', 'платежа', 'платежей') }}
                </span>
                @if($data['others_count'] > 0)
                    <span class="desk-muted fs-8 text-nowrap desk-only-w-lg"
                          title="Партнёры за пределами списка: {{ $widget::money($data['others'], $data['symbol'], false) }}">
                        остальные: {{ $data['others_count'] }} · {{ $widget::money($data['others'], $data['symbol']) }}
                    </span>
                @endif
                @if($data['skipped'] > 0)
                    <span class="desk-muted fs-8 text-nowrap desk-only-w-md">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>
        </div>

        @if(!empty($list) && $table)
            <div class="desk-stack-grow desk-scroll">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Партнёр</th>
                            <th class="desk-only-w-xl" style="width: 30%;">Доля</th>
                            <th class="num desk-only-w-xl">Платежей</th>
                            <th class="num">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    @if($row['url'] && !$preview)
                                        <a href="{{ $href($row) }}" class="desk-link d-block text-truncate fw-semibold text-hover-primary" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                                    @else
                                        <span class="d-block text-truncate fw-semibold" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                                    @endif
                                </td>
                                <td class="desk-only-w-xl">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1">
                                            <i class="bg-{{ $color }}" style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                                        </div>
                                        <span class="desk-muted text-end" style="min-width: 3.2em;">{{ $share($row['share']) }}</span>
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
        @elseif(!empty($list))
            <div class="desk-stack-grow desk-scroll">
                <ul class="desk-list">
                    @foreach($list as $row)
                        <li>
                            @if($row['url'] && !$preview)
                                <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                            @else
                                <span class="desk-grow" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                            @endif
                            <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md"
                                  title="Доля от {{ $widget::money($data['total'], $data['symbol'], false) }}">{{ $share($row['share']) }}</span>
                            <span class="fw-bold text-nowrap flex-shrink-0" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                                {{ $widget::money($row['amount'], $data['symbol']) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
