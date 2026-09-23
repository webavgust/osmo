{{-- Виджет «Крупные сделки» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\DealsTopWidget --}}
@php
    $symbol = $data['symbol'];

    // на широком блоке таблица со стадией и заказчиком, на узком — список «сделка + сумма»;
    // в низком блоке шапке, строке и итогу таблицы вместе не хватает места — тоже список
    $table = in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);
    // в колонке шириной 2 сумма с символом валюты не влезает — только число, полная сумма в title
    $amount = fn($value) => $dw === 'xs' ? $widget::compact($value) : $widget::money($value, $symbol);
    // лишние строки спрячет .desk-fit; сделок не больше 30 (предел настройки «Сколько сделок»),
    // поэтому отдаём все, а не $rows_max — подпись «ещё N» считает спрятанные честно
    $list = $data['rows'];

    // итог сверху — на высоком блоке (показывает .desk-only-h-lg); в колонке шириной 2 крупная
    // сумма не влезает. Итоговая строка таблицы на высоком блоке прячется стилем группы
    $summary = $dw !== 'xs';
    // разбивка топа по стадиям — на очень высоком блоке, чтобы он не пустовал при 10 сделках
    $colors = ['primary', 'success', 'warning', 'info', 'danger', 'gray-500'];
    $stages = $dh === 'xl' && $data['total'] > 0
        ? collect($list)->groupBy('stage')->map(fn($group, $name) => ['name' => (string) $name, 'sum' => $group->sum('amount')])
            ->sortByDesc('sum')->values()
            ->map(fn($stage, $i) => $stage + ['pct' => $stage['sum'] / $data['total'] * 100, 'color' => $colors[$i % count($colors)]])
        : collect();

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $bitrix = fn($row) => $preview || empty($row['deal_url']) ? null : $row['deal_url'];
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-arrow-up-wide-short"></i> Сделок за период нет
    </div>
@else
    <div class="desk-stack">
        @if($summary)
            <div class="desk-only-h-lg">
                <div class="d-flex flex-wrap align-items-baseline column-gap-2">
                    {{-- итог — сумма показанных сделок, а не всех сделок периода --}}
                    <span class="desk-value-sm" title="Сумма показанных сделок ({{ count($list) }} из {{ $data['found'] }}): {{ $widget::money($data['total'], $symbol, false) }}">{{ $widget::money($data['total'], $symbol) }}</span>
                    <span class="desk-label text-nowrap">{{ count($list) }} из {{ $data['found'] }}</span>
                    <span class="desk-label text-nowrap desk-only-w-md">· {{ $data['dates'] }}</span>
                </div>

                @if($stages->isNotEmpty())
                    <div class="desk-split mt-2">
                        @foreach($stages as $i => $stage)
                            <i class="bg-{{ $stage['color'] }}" style="width: {{ round($stage['pct'], 2) }}%"
                               title="{{ $stage['name'] }} · {{ $widget::money($stage['sum'], $symbol) }} · {{ round($stage['pct']) }}%"></i>
                        @endforeach
                    </div>
                    <div class="desk-only-w-md">
                        {{-- третья стадия легенды — только на широком блоке (стиль группы funnel-2) --}}
                        <div class="deals-top-legend mt-1 desk-label">
                            @foreach($stages->take(3) as $i => $stage)
                                <span title="{{ $stage['name'] }} · {{ $widget::money($stage['sum'], $symbol) }}">
                                    <span class="bullet bullet-dot bg-{{ $stage['color'] }} flex-shrink-0"></span>
                                    <span class="text-truncate">{{ $stage['name'] }}</span>
                                    <span class="text-nowrap flex-shrink-0">{{ round($stage['pct']) }}%</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if($table)
            <div class="desk-fit desk-stack-grow" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Сделка</th>
                            <th class="desk-only-w-lg">Заказчик</th>
                            <th class="desk-only-w-xl">Стадия</th>
                            <th class="num desk-only-w-xl">Закрытие</th>
                            <th class="num">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $row['title'] }}">
                                        {{ $row['title'] }}
                                    </a>
                                </td>
                                <td class="desk-cut desk-muted desk-only-w-lg" title="{{ $row['company'] ?: '—' }}">{{ $row['company'] ?: '—' }}</td>
                                <td class="desk-only-w-xl">
                                    <span class="badge badge-light" title="{{ $row['stage'] }}">{{ $row['stage'] }}</span>
                                </td>
                                <td class="num desk-muted desk-only-w-xl">{{ $row['date'] ?? '—' }}</td>
                                <td class="num fw-semibold" title="{{ $widget::money($row['amount'], $symbol, false) }}">
                                    {{ $widget::money($row['amount'], $symbol) }}
                                    @if($bitrix($row))
                                        <a href="{{ $bitrix($row) }}" target="_blank" class="text-gray-500 text-hover-primary ms-1" title="Сделка в Битрикс24">
                                            <i class="fa-light fa-arrow-up-right-from-square fs-8"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="deals-top-foot">
                        <tr>
                            <td class="desk-cut" title="{{ $data['dates'] }}">Итого <span class="desk-muted fw-normal desk-only-w-xl">· {{ $data['dates'] }}</span></td>
                            <td class="desk-only-w-lg"></td>
                            <td class="desk-only-w-xl"></td>
                            <td class="num desk-muted fw-normal desk-only-w-xl">{{ count($list) }} из {{ $data['found'] }}</td>
                            <td class="num" title="{{ $widget::money($data['total'], $symbol, false) }}">{{ $widget::money($data['total'], $symbol) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <ul class="desk-list desk-fit desk-stack-grow">
                @foreach($list as $row)
                    <li title="{{ $row['title'] }}{{ $row['company'] ? ' · ' . $row['company'] : '' }} · {{ $row['stage'] }}">
                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary desk-hide-narrow" title="{{ $row['title'] }}">
                            {{ $row['title'] }}
                        </a>

                        @if($row['project'])
                            <i class="fa-light fa-diagram-project text-gray-500 fs-8 flex-shrink-0 desk-only-w-md" title="Есть проект"></i>
                        @endif
                        @if($row['proposal'])
                            <i class="fa-light fa-file-lines text-gray-500 fs-8 flex-shrink-0 desk-only-w-md" title="Привязано КП"></i>
                        @endif

                        <span class="fw-semibold text-nowrap flex-shrink-0 ms-auto" title="{{ $widget::money($row['amount'], $symbol, false) }}">
                            {{ $amount($row['amount']) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>

        @if($data['skipped'] > 0)
            <div class="desk-muted fs-8 desk-only-h-lg">без курса: {{ $data['skipped'] }}</div>
        @endif
    </div>
@endif
