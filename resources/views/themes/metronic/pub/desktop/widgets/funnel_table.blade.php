{{-- Виджет «Таблица воронки» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\FunnelTableWidget --}}
@php
    $symbol = $data['symbol'];
    // колонки прячутся по реальной ширине блока (классы desk-only-w-*), а не по размеру

    // высокий блок: итог крупно сверху вместо итоговой строки, строки таблицы выше (стиль группы
    // funnel-2); в колонке шириной 2 крупная сумма не влезает — там остаётся итоговая строка
    $tall = in_array($dh, ['lg', 'xl'], true);
    $summary = $tall && $dw !== 'xs';
    $avg = $data['count_total'] ? $data['total'] / $data['count_total'] : null;

    // в колонке шириной 2 сумма с символом валюты не влезает — только число, полная в title;
    // колонка стадии там прячется (desk-hide-narrow), стадия — в title строки
    $amount = fn($value) => $dw === 'xs' ? $widget::compact($value) : $widget::money($value, $symbol);

    // стадий немного (константа воронки) — отдаём все, лишние спрячет .desk-fit
    $list = $data['rows'];
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-filter"></i> Сделок нет
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
                    <span class="text-nowrap desk-only-w-lg">· средняя {{ $widget::money($avg, $symbol) }}</span>
                </div>
            </div>
        @endif

        <div @class(['desk-fit', 'desk-stack-grow', 'funnel-table-tall' => $tall]) data-fit-items="tbody > tr">
            <table class="desk-table">
                {{-- в низком блоке шапке, строке и итогу вместе не хватает места — шапка прячется --}}
                <thead class="desk-hide-short">
                <tr>
                    <th class="desk-hide-narrow">Стадия</th>
                    <th class="num desk-only-w-md">Сделок</th>
                    <th class="num">Сумма</th>
                    <th class="desk-only-w-lg" style="width: 30%;">Доля</th>
                    <th class="num desk-only-w-xl">Средняя сделка</th>
                </tr>
                </thead>
                <tbody>
                @foreach($list as $row)
                    <tr title="{{ $row['stage'] }}: {{ $widget::money($row['amount'], $symbol, false) }} · {{ $row['count'] }} шт.">
                        <td class="desk-cut desk-hide-narrow" title="{{ $row['stage'] }}">{{ $row['stage'] }}</td>
                        <td class="num desk-only-w-md">{{ $row['count'] }}</td>
                        <td class="num" title="{{ $widget::money($row['amount'], $symbol, false) }}">{{ $amount($row['amount']) }}</td>
                        <td class="desk-only-w-lg">
                            <div class="d-flex align-items-center gap-2">
                                <div class="desk-bar flex-grow-1">
                                    <i @class(['f2-bar-nz' => $row['share'] > 0]) style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                                </div>
                                <span class="desk-muted text-end text-nowrap" style="min-width: 3.2em;">{{ number_format($row['share'], $row['share'] < 10 ? 1 : 0, ',', ' ') }} %</span>
                            </div>
                        </td>
                        <td class="num desk-only-w-xl">{{ $widget::money($row['avg'], $symbol) }}</td>
                    </tr>
                @endforeach
                </tbody>
                @unless($summary)
                    <tfoot>
                    <tr>
                        <td class="desk-cut desk-hide-narrow" title="{{ $data['label'] }}">Итого <span class="desk-muted fw-normal desk-only-w-md">· {{ $data['label'] }}</span></td>
                        <td class="num desk-only-w-md">{{ $data['count_total'] }}</td>
                        <td class="num" title="{{ $widget::money($data['total'], $symbol, false) }}">{{ $amount($data['total']) }}</td>
                        <td class="desk-only-w-lg"></td>
                        <td class="num desk-only-w-xl">{{ $widget::money($avg, $symbol) }}</td>
                    </tr>
                    </tfoot>
                @endunless
            </table>
        </div>
        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
    </div>
@endif
