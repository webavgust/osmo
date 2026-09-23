{{-- Виджет «Партнёры по грейдам» (patch v30): App\Modules\Pub\Desktop\Widgets\Partner\PartnersGradesWidget --}}
@php
    // кольцо долей цветами медалей: на широком высоком блоке (от 12×6) — слева от грейдов, итог в
    // середине кольца; на блоке шириной 3–11 — над списком, когда по высоте хватает места и кольцу,
    // и всем грейдам
    $ring_side = in_array($dw, ['lg', 'xl'], true) && in_array($dh, ['lg', 'xl'], true);
    $ring_top = !$ring_side && in_array($dw, ['sm', 'md'], true) && in_array($dh, ['lg', 'xl'], true)
        && $rows >= count($data['rows']) + 4;
    $ring = ($ring_side || $ring_top) && $data['total'] > 0;

    // грейдов немного (5–7), поэтому высокий блок без кольца заполняется не строками, а плитками
    // грейдов на всю высоту; средний — итог сверху и список
    $tiles = $dh === 'xl' && !$ring_top;
    // блок от 6 колонок без кольца (низкий) — список в две колонки: в одну 6 грейдов по высоте 3–7
    // не влезали, половина уходила в «ещё» (прежняя таблица широкого блока — так же)
    $cols = !$ring && !$tiles && in_array($dw, ['md', 'lg', 'xl'], true);
    // итог — строкой сверху; при кольце сбоку — в середине кольца, вся высота справа достаётся грейдам
    $summary = !$ring_side && ($tiles || $ring || ($dw !== 'xs' && $dh === 'lg'));
    $show_won = (string) $settings['metric'] === 'won';
    $total_word = \App\Facades\Tools::morph($data['total'], 'партнёр', 'партнёра', 'партнёров');

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $linked = fn($row) => !empty($row['url']) && !$preview;
    $percent = fn($value) => number_format((float) $value, $value < 10 ? 1 : 0, ',', ' ') . ' %';
    $title = fn($row) => $row['label'] . ' · ' . $row['count'] . ' '
        . \App\Facades\Tools::morph($row['count'], 'партнёр', 'партнёра', 'партнёров')
        . ' из ' . $data['total'] . ($row['hint'] !== '' ? ' · ' . $row['hint'] : '');
    // цвет медали — в заливку шкалы и доли; грейд без цвета остаётся серым
    $fill = fn($row) => $row['color'] !== '' ? 'background-color: ' . $row['color'] . ';' : '';

    // кольцо: цвет медали передаётся как есть (#ffb604), грейд без цвета — серым
    $ring_chart = $ring ? $widget::chart([
        'type' => 'donut',
        'labels' => array_column($data['rows'], 'label'),
        'colors' => array_map(fn($row) => $row['color'] !== '' ? $row['color'] : 'gray-400', $data['rows']),
        'series' => array_map(fn($row) => (int) $row['count'], $data['rows']),
    ]) : '';
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-medal"></i> Партнёров нет
    </div>
@else
    {{-- широкий высокий блок: кольцо слева, итог и грейды справа --}}
    <div @class(['pg-side' => $ring_side, 'desk-stack' => !$ring_side])>
    @if($ring_side)
        <div class="pg-ring">
            {!! $ring_chart !!}
            <div class="pg-ring-total">
                <span class="{{ $tiles ? 'desk-value' : 'desk-value-sm fw-bold' }} text-nowrap">{{ $data['total'] }}</span>
                <span class="desk-label text-nowrap">{{ $total_word }}</span>
                @if($show_won)
                    <span class="desk-label text-nowrap">КП: {{ $data['won_total'] }}</span>
                @endif
            </div>
        </div>
        <div class="desk-stack">
    @endif
        @if($summary)
            <div>
                <div class="d-flex flex-wrap align-items-baseline column-gap-2">
                    <span class="{{ $tiles ? 'desk-value' : 'desk-value-sm fw-bold' }} text-nowrap">{{ $data['total'] }}</span>
                    <span class="desk-label text-nowrap">{{ $total_word }}</span>
                    @if($show_won)
                        <span class="desk-label text-nowrap desk-only-w-md" title="Выигранные КП партнёров за всю историю">КП: {{ $data['won_total'] }}</span>
                    @endif
                </div>
                {{-- с кольцом полоска долей не нужна: доли видны на кольце --}}
                @unless($ring)
                    <div class="desk-split mt-2">
                        @foreach($data['rows'] as $row)
                            <div @class(['bg-gray-400' => $row['color'] === '']) style="width: {{ max(0, min(100, $row['share'])) }}%; {{ $fill($row) }}" title="{{ $row['label'] }} · {{ $percent($row['share']) }}"></div>
                        @endforeach
                    </div>
                @endunless
            </div>
        @endif

        @if($ring_top)
            <div class="pg-ring-top">{!! $ring_chart !!}</div>
        @endif

        @if($tiles)
            <div class="desk-stack-grow pg-tiles">
                @foreach($data['rows'] as $row)
                    <div class="pg-tile" title="{{ $title($row) }}">
                        @if($row['color'] !== '')
                            <i class="fa-light fa-medal fs-2 flex-shrink-0 desk-hide-narrow" style="color: {{ $row['color'] }}"></i>
                        @endif
                        <div class="pg-main">
                            <div class="d-flex align-items-baseline gap-2">
                                @if($linked($row))
                                    <a href="{{ $href($row) }}" class="desk-link desk-grow fw-bold text-hover-primary" title="{{ $title($row) }}">{{ $row['label'] }}</a>
                                @else
                                    <span class="desk-grow fw-bold" title="{{ $title($row) }}">{{ $row['label'] }}</span>
                                @endif
                                <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md">{{ $percent($row['share']) }}</span>
                            </div>
                            @if($row['hint'] !== '')
                                <div class="desk-muted fs-8 desk-nowrap desk-only-w-md" title="{{ $row['hint'] }}">{{ $row['hint'] }}</div>
                            @endif
                            <div class="desk-bar mt-1">
                                @if($row['share'] > 0)<i style="width: {{ min(100, $row['share']) }}%; {{ $fill($row) }}"></i>@endif
                            </div>
                        </div>
                        @if($show_won)
                            <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-lg" title="Выигранные КП партнёров грейда за всю историю">КП {{ $row['won'] }}</span>
                        @endif
                        <span class="desk-value-sm fw-bold text-nowrap flex-shrink-0">{{ $row['count'] }}</span>
                    </div>
                @endforeach
            </div>
        @else
            {{-- рядом с кольцом лишнюю высоту делят строки; под кольцом список не сжимается — сжимается кольцо;
                 в низком широком блоке — две колонки --}}
            <ul @class(['desk-list', 'desk-fit', 'desk-stack-grow' => !$ring_top, 'pg-list-keep' => $ring_top, 'pg-list-fill' => $ring, 'pg-cols' => $cols])>
                @foreach($data['rows'] as $row)
                    <li>
                        @if($row['color'] !== '')
                            <i class="fa-light fa-medal flex-shrink-0 desk-hide-narrow" style="color: {{ $row['color'] }}"></i>
                        @endif
                        @if($linked($row))
                            <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $title($row) }}">{{ $row['label'] }}</a>
                        @else
                            <span class="desk-grow" title="{{ $title($row) }}">{{ $row['label'] }}</span>
                        @endif

                        <div class="desk-bar flex-shrink-0 desk-only-w-md" style="width: 4rem;" title="{{ $percent($row['share']) }}">
                            @if($row['share'] > 0)<i style="width: {{ min(100, $row['share']) }}%; {{ $fill($row) }}"></i>@endif
                        </div>
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md pg-pct">{{ $percent($row['share']) }}</span>

                        @if($show_won)
                            <span class="desk-muted fs-8 text-nowrap desk-only-w-lg" title="Выигранные КП партнёров грейда за всю историю">КП {{ $row['won'] }}</span>
                        @endif

                        <span class="badge badge-light-primary flex-shrink-0" title="{{ $title($row) }}">{{ $row['count'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
        @endif
    @if($ring_side)
        </div>
    @endif
    </div>
@endif
