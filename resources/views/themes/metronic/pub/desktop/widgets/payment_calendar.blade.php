{{-- Виджет «Платёжный календарь» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\PaymentCalendarWidget --}}
@php
    $days_word = \App\Facades\Tools::morph($data['days'], 'день', 'дня', 'дней');
    // низкий блок — только сумма; выше добавляется список платежей (шапка с итогами — три строки)
    $show_list = !in_array($dh, ['xs', 'sm'], true) && $rows >= 5;
    // таблица — когда под неё есть и ширина, и высота, иначе плотный список
    $table = in_array($dw, ['md', 'lg', 'xl'], true) && $rows >= 6;
    // строк с запасом: лишние спрячет подгон .desk-fit
    $list = $show_list ? array_slice($data['rows'], 0, $rows_max) : [];
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];

    // в самом узком блоке сумма в две строки: число крупно, «млн ₽» подписью под ним
    preg_match('/^(.+?)(?: (млрд|млн|тыс\.))?$/u', $widget::compact($data['amount']), $parts);
    $unit = trim(($parts[2] ?? '') . ' ' . $data['symbol']);
@endphp
@if(empty($data['rows']) && $data['skipped'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-calendar-check"></i> Плановых платежей на ближайшие {{ $data['days'] }} {{ $days_word }} нет
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list)])>
        <div>
            <div class="desk-label desk-nowrap desk-hide-narrow">ждём за {{ $data['days'] }} {{ $days_word }}</div>
            <div class="desk-value" title="Ждём за {{ $data['days'] }} {{ $days_word }}: {{ $widget::money($data['amount'], $data['symbol'], false) }}">
                {{ $dw === 'xs' ? $parts[1] : $widget::money($data['amount'], $data['symbol']) }}
            </div>
            @if($dw === 'xs')
                <div class="desk-label desk-nowrap">{{ $unit }}</div>
            @endif

            {{-- итоги в одну строку: что не влезло по ширине, прячет подгон --}}
            <div class="fin-line desk-fit desk-hide-short mt-1" data-fit-axis="x" data-fit-min="0">
                <span class="desk-muted">
                    {{ $data['count'] }} {{ \App\Facades\Tools::morph($data['count'], 'платёж', 'платежа', 'платежей') }}
                </span>
                @if($data['overdue_count'] > 0)
                    <span class="badge badge-light-danger fs-8"
                          title="Просрочено: {{ $widget::money($data['overdue_amount'], $data['symbol'], false) }}">
                        просрочено: {{ $data['overdue_count'] }} · {{ $widget::money($data['overdue_amount'], $data['symbol']) }}
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
                            <th>Дата</th>
                            <th>Компания</th>
                            <th class="desk-only-w-lg">Спецификация</th>
                            <th class="num desk-only-w-xl">Срок</th>
                            <th class="num">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="fw-semibold" title="{{ $row['label'] }}">
                                    <span class="bullet bullet-dot bg-{{ $row['color'] }} w-6px h-6px me-2"></span>{{ $row['date'] }}
                                </td>
                                <td class="desk-cut">
                                    <a href="{{ $href($row) }}" class="desk-link d-block text-truncate fw-semibold text-hover-primary"
                                       title="{{ $row['company'] }}{{ $row['partner'] ? ' · ' . $row['partner'] : '' }}">{{ $row['company'] }}</a>
                                </td>
                                <td class="desk-cut desk-muted desk-only-w-lg" title="{{ $row['spec'] }}">{{ $row['spec'] }}</td>
                                <td class="num desk-muted desk-only-w-xl">
                                    {{ $row['days'] < 0 ? '−' . abs($row['days']) : '+' . $row['days'] }} дн.
                                </td>
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
                        <span @class(['text-nowrap flex-shrink-0', 'text-danger' => $row['state'] === 'overdue', 'desk-muted' => $row['state'] !== 'overdue'])
                              title="{{ $row['date'] }} · {{ $row['label'] }}">{{ $row['date_short'] }}</span>
                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary"
                           title="{{ $row['company'] }} · {{ $row['spec'] }}">{{ $row['company'] }}</a>
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
