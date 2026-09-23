{{-- Виджет «Отрасли» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\IndustryWidget --}}
@php
    $symbol = $data['symbol'];

    // на широком блоке таблица с менеджером и долей, на узком — полосы
    $table = in_array($dw, ['lg', 'xl'], true);
    // высокий блок: итог крупно сверху вместо строки итога, строки выше (стиль группы funnel-2);
    // в колонке шириной 2 крупная сумма не влезает
    $tall = in_array($dh, ['lg', 'xl'], true);
    $narrow = $dw === 'xs';
    $summary = $tall && !$narrow;
    // в колонке шириной 2 сумма с символом валюты не влезает — только число, полная в title
    $amount = fn($value) => $narrow ? $widget::compact($value) : $widget::money($value, $symbol);

    // отраслей не больше 30 (настройка «Сколько отраслей», хвост свёрнут) — отдаём все,
    // лишние спрячет .desk-fit
    $list = $data['rows'];

    $hint = fn($row) => $row['industry'] . ': ' . $widget::money($row['amount'], $symbol, false) . ' · ' . $row['count'] . ' шт.';
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-industry-windows"></i> Сделок нет
    </div>
@else
    <div class="desk-stack">
        @if($summary)
            <div>
                <div class="desk-value-sm" title="{{ $widget::money($data['total'], $symbol, false) }}">{{ $widget::money($data['total'], $symbol) }}</div>
                <div class="d-flex align-items-baseline column-gap-2 desk-label">
                    <span class="desk-grow" title="{{ $data['label'] }}">{{ $data['label'] }}</span>
                    <span class="text-nowrap">
                        {{ $data['count_total'] }} {{ \App\Facades\Tools::morph($data['count_total'], 'сделка', 'сделки', 'сделок') }}
                    </span>
                </div>
            </div>
        @elseif(!$table)
            <div class="d-flex align-items-baseline gap-2 desk-hide-short">
                <span class="desk-label desk-grow desk-hide-narrow" title="{{ $data['label'] }}">{{ $data['label'] }}</span>
                <span class="fw-bold text-nowrap ms-auto" title="{{ $widget::money($data['total'], $symbol, false) }}">{{ $amount($data['total']) }}</span>
            </div>
        @endif

        @if($table)
            <div @class(['desk-fit', 'desk-stack-grow', 'industry-tall-table' => $tall]) data-fit-items="tbody > tr">
                <table class="desk-table">
                    {{-- в низком блоке шапке, строке и итогу вместе не хватает места — шапка прячется --}}
                    <thead class="desk-hide-short">
                        <tr>
                            <th>Отрасль</th>
                            {{-- колонка менеджера — по настройке «Показывать ведущего менеджера», как в списке --}}
                            @if($settings['managers'])
                                <th class="desk-only-w-xl">Ведущий менеджер</th>
                            @endif
                            <th class="num desk-only-w-lg">Сделок</th>
                            <th class="num">Сумма</th>
                            <th class="desk-only-w-lg" style="width: 22%;">Доля</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut" title="{{ $hint($row) }}">
                                    {{ $row['industry'] }}
                                    @if($row['rest'])
                                        <span class="desk-muted">({{ $row['rest'] }})</span>
                                    @endif
                                </td>
                                @if($settings['managers'])
                                    <td class="desk-cut desk-muted desk-only-w-xl" title="{{ $row['manager'] ?? '—' }}">{{ $row['manager'] ?? '—' }}</td>
                                @endif
                                <td class="num desk-only-w-lg">{{ $row['count'] }}</td>
                                <td class="num fw-semibold" title="{{ $widget::money($row['amount'], $symbol, false) }}">{{ $widget::money($row['amount'], $symbol) }}</td>
                                <td class="desk-only-w-lg">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1">
                                            <i @class(['f2-bar-nz' => $row['bar'] > 0]) style="width: {{ max(0, min(100, $row['bar'])) }}%;"></i>
                                        </div>
                                        <span class="desk-muted text-end text-nowrap" style="min-width: 3.2em;">{{ number_format($row['share'], $row['share'] < 10 ? 1 : 0, ',', ' ') }} %</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @unless($summary)
                        <tfoot>
                            <tr>
                                <td class="desk-cut" title="{{ $data['label'] }}">Итого <span class="desk-muted fw-normal desk-only-w-lg">· {{ $data['label'] }}</span></td>
                                @if($settings['managers'])
                                    <td class="desk-only-w-xl"></td>
                                @endif
                                <td class="num desk-only-w-lg">{{ $data['count_total'] }}</td>
                                <td class="num" title="{{ $widget::money($data['total'], $symbol, false) }}">{{ $widget::money($data['total'], $symbol) }}</td>
                                <td class="desk-only-w-lg"></td>
                            </tr>
                        </tfoot>
                    @endunless
                </table>
            </div>
        @else
            {{-- узкая высокая колонка: название отрасли строкой над суммой (стиль группы funnel-2) --}}
            <ul @class(['desk-list', 'desk-fit', 'desk-stack-grow', 'industry-tall-list' => $tall, 'industry-stacked' => $tall && $narrow])>
                @foreach($list as $row)
                    <li title="{{ $hint($row) }}">
                        @if($tall && $narrow)
                            <span class="desk-nowrap desk-label" title="{{ $row['industry'] }}">{{ $row['industry'] }}</span>
                        @else
                            {{-- число свёрнутых отраслей — текстом: вложенный span целиком уходил за многоточие --}}
                            <span class="desk-grow desk-hide-narrow">{{ $row['industry'] }}{{ $row['rest'] ? ' (' . $row['rest'] . ')' : '' }}</span>
                        @endif

                        <div class="desk-bar desk-only-w-md" style="flex: 0 1 32%;">
                            <i @class(['f2-bar-nz' => $row['bar'] > 0]) style="width: {{ max(0, min(100, $row['bar'])) }}%;"></i>
                        </div>

                        @if($settings['managers'] && $row['manager'])
                            <span class="desk-muted fs-8 text-nowrap desk-only-w-lg">{{ $row['manager'] }}</span>
                        @endif

                        <span class="desk-muted fs-8 text-nowrap text-end desk-hide-narrow" style="min-width: 2.4em;">{{ $row['count'] }} шт.</span>

                        <span class="fw-semibold text-nowrap text-end ms-auto" style="min-width: 4.5em;">{{ $amount($row['amount']) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
    </div>
@endif
