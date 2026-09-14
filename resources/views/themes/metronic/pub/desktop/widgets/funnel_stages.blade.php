{{-- Виджет «Воронка по стадиям» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\FunnelStagesWidget --}}
@php
    $symbol = $data['symbol'];

    // строка итога — от трёх ячеек высоты; на высоком блоке итог крупнее и строки стадий
    // растягиваются по высоте (стиль группы funnel-2), чтобы блок не пустовал
    $head = in_array($dh, ['md', 'lg', 'xl'], true);
    $tall = in_array($dh, ['lg', 'xl'], true);
    // в колонке шириной 2 сумма с символом валюты не влезает — только число, полная в title
    $narrow = $dw === 'xs';
    $amount = fn($value) => $narrow ? $widget::compact($value) : $widget::money($value, $symbol);

    // стадий немного (константа воронки) — отдаём все, лишние спрячет .desk-fit
    $list = $data['rows'];

    $counts = (bool) $settings['counts'];
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-filter-circle-dollar"></i> Сделок нет
    </div>
@else
    <div class="desk-stack">
        @if($head && $tall && !$narrow)
            <div>
                <div class="desk-value-sm" title="{{ $widget::money($data['total'], $symbol, false) }}">{{ $widget::money($data['total'], $symbol) }}</div>
                <div class="d-flex align-items-baseline gap-2 desk-label">
                    <span class="desk-grow" title="{{ $data['label'] }}">{{ $data['label'] }}</span>
                    @if($counts)
                        <span class="text-nowrap">
                            {{ $data['count_total'] }} {{ \App\Facades\Tools::morph($data['count_total'], 'сделка', 'сделки', 'сделок') }}
                        </span>
                    @endif
                </div>
            </div>
        @elseif($head)
            <div class="d-flex align-items-baseline gap-2">
                <span class="desk-label desk-grow desk-hide-narrow" title="{{ $data['label'] }}">{{ $data['label'] }}</span>
                @if($counts)
                    <span class="desk-muted text-nowrap desk-only-w-md">
                        {{ $data['count_total'] }} {{ \App\Facades\Tools::morph($data['count_total'], 'сделка', 'сделки', 'сделок') }}
                    </span>
                @endif
                <span class="fw-bold text-nowrap ms-auto" title="{{ $widget::money($data['total'], $symbol, false) }}">{{ $amount($data['total']) }}</span>
            </div>
        @endif

        {{-- узкая высокая колонка: название стадии строкой над суммой (стиль группы funnel-2) --}}
        <ul @class(['desk-list', 'desk-fit', 'desk-stack-grow', 'funnel-stages-tall' => $tall, 'funnel-stages-stacked' => $tall && $narrow])>
            @foreach($list as $row)
                <li title="{{ $row['stage'] }}: {{ $widget::money($row['amount'], $symbol, false) }} · {{ $row['count'] }} шт. · {{ $row['share'] }}%">
                    @if($tall && $narrow)
                        <span class="desk-nowrap desk-label" title="{{ $row['stage'] }}">{{ $row['stage'] }}</span>
                    @else
                        <span class="desk-grow desk-hide-narrow">{{ $row['stage'] }}</span>
                    @endif

                    <div class="desk-bar desk-only-w-md" style="flex: 0 1 38%;">
                        <i style="width: {{ max(0, min(100, $row['bar'])) }}%;"></i>
                    </div>

                    @if($counts)
                        <span class="desk-muted fs-8 text-nowrap text-end desk-hide-narrow" style="min-width: 2.4em;">{{ $row['count'] }} шт.</span>
                    @endif

                    <span class="desk-muted fs-8 text-nowrap text-end desk-only-w-lg" style="min-width: 3em;">{{ $row['share'] }}%</span>

                    <span class="fw-semibold text-nowrap text-end ms-auto" style="min-width: 4.5em;">{{ $amount($row['amount']) }}</span>
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>

        @if($data['by_count'])
            <div class="desk-muted fs-8 desk-only-h-lg">Суммы не заполнены — полосы по числу сделок</div>
        @endif
    </div>
@endif
