{{-- Виджет «Просроченные оплаты» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\PaymentsOverdueWidget --}}
@php
    // низкий блок — только сумма и итоги; выше добавляется список платежей (шапка с итогами — три строки)
    $compact = in_array($dh, ['xs', 'sm'], true) || $rows < 5;
    // широкий блок — таблица; в остальных список (в самом узком — в две строки, см. finance.css)
    $table = in_array($dw, ['lg', 'xl'], true);
    // строк с запасом: лишние спрячет подгон .desk-fit
    $limit = max(1, min((int) $settings['limit'], $rows_max));
    $list = $compact ? [] : array_slice($data['rows'], 0, $limit);

    // цвет строки: чем дольше ждём, тем тревожнее
    $color = fn($days) => $days > 90 ? 'danger' : ($days > 30 ? 'warning' : 'secondary');
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];

    // корзины давности: цвет по коду корзины — от свежей просрочки к застарелой
    // (по коду, а не по порядку: без свежей корзины «31–90 дней» не должна стать серой)
    $tones = ['d30' => 'gray-400', 'd90' => 'warning', 'd365' => 'danger', 'older' => 'dark'];

    // в самом узком блоке сумма в две строки: число крупно, «млн ₽» подписью под ним
    preg_match('/^(.+?)(?: (млрд|млн|тыс\.))?$/u', $widget::compact($data['amount']), $parts);
    $unit = trim(($parts[2] ?? '') . ' ' . $data['symbol']);
@endphp
@if($data['count'] === 0 && $data['skipped'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-circle-check"></i> <span class="desk-hide-narrow">Просроченных оплат нет</span>
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list)])>
        <div>
            <div class="desk-label desk-nowrap desk-hide-narrow">просрочено</div>
            <div class="desk-value text-danger" title="Просрочено: {{ $widget::money($data['amount'], $data['symbol'], false) }}">
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
                @if($data['max_days'] > 0)
                    <span class="badge badge-light-danger fs-8" title="Самая давняя просрочка">дольше всех: {{ $data['max_days'] }} дн.</span>
                @endif
                @if($data['skipped'] > 0)
                    <span class="desk-muted fs-8">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>

            {{-- высокий блок: корзины давности полоской (доля суммы) и подписями --}}
            @if(!$compact && !empty($data['buckets']))
                <div class="desk-only-h-lg mt-3">
                    <div class="desk-split">
                        @foreach($data['buckets'] as $bucket)
                            <i class="bg-{{ $tones[$bucket['code']] ?? 'secondary' }}"
                               style="width: {{ round($bucket['amount'] / max(1, $data['amount']) * 100, 2) }}%"
                               title="{{ $bucket['label'] }}: {{ $bucket['count'] }} · {{ $widget::money($bucket['amount'], $data['symbol'], false) }}"></i>
                        @endforeach
                    </div>
                    <div class="fin-line desk-fit mt-1" data-fit-axis="x" data-fit-min="0">
                        @foreach($data['buckets'] as $bucket)
                            <span class="fs-8 desk-muted" title="{{ $widget::money($bucket['amount'], $data['symbol'], false) }}">
                                <span class="bullet bullet-dot bg-{{ $tones[$bucket['code']] ?? 'secondary' }} me-1"></span>{{ $bucket['label'] }}: {{ $bucket['count'] }} · {{ $widget::money($bucket['amount'], $data['symbol']) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if(!empty($list) && $table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Компания</th>
                            <th class="desk-only-w-xl">Спецификация</th>
                            <th class="num">План</th>
                            <th class="num">Просрочка</th>
                            <th class="num">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <a href="{{ $href($row) }}" class="desk-link d-block text-truncate fw-semibold text-hover-primary"
                                       title="{{ $row['company'] }}{{ $row['partner'] ? ' · ' . $row['partner'] : '' }}">{{ $row['company'] }}</a>
                                </td>
                                <td class="desk-cut desk-muted desk-only-w-xl" title="{{ $row['spec'] }}">{{ $row['spec'] }}</td>
                                <td class="num desk-muted">{{ $row['date'] ?? '—' }}</td>
                                <td class="num text-{{ $color($row['days']) }} fw-semibold">{{ $row['days'] }} дн.</td>
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
                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary"
                           title="{{ $row['company'] }} · {{ $row['spec'] }} · план {{ $row['date'] ?? '—' }}">{{ $row['company'] }}</a>
                        <span class="badge badge-light-{{ $color($row['days']) }} flex-shrink-0 desk-only-w-md">{{ $row['days'] }} дн.</span>
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
