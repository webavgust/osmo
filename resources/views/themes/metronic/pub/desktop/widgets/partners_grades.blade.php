{{-- Виджет «Партнёры по грейдам» (patch v30): App\Modules\Pub\Desktop\Widgets\Partner\PartnersGradesWidget --}}
@php
    // грейдов немного (5–7), поэтому высокий блок заполняется не строками, а плитками грейдов
    // на всю высоту; средний — итог сверху и список; широкий не низкий — таблица с долей
    $tiles = $dh === 'xl';
    $table = !$tiles && in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);
    $summary = $tiles || ($dw !== 'xs' && $dh === 'lg');
    $show_won = (string) $settings['metric'] === 'won';

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $linked = fn($row) => !empty($row['url']) && !$preview;
    $percent = fn($value) => number_format((float) $value, $value < 10 ? 1 : 0, ',', ' ') . ' %';
    $title = fn($row) => $row['label'] . ' · ' . $row['count'] . ' '
        . \App\Facades\Tools::morph($row['count'], 'партнёр', 'партнёра', 'партнёров')
        . ' из ' . $data['total'] . ($row['hint'] !== '' ? ' · ' . $row['hint'] : '');
    // цвет медали — в заливку шкалы и доли; грейд без цвета остаётся серым
    $fill = fn($row) => $row['color'] !== '' ? 'background-color: ' . $row['color'] . ';' : '';
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-medal"></i> Партнёров нет
    </div>
@else
    <div class="desk-stack">
        @if($summary)
            <div>
                <div class="d-flex flex-wrap align-items-baseline column-gap-2">
                    <span class="{{ $tiles ? 'desk-value' : 'desk-value-sm fw-bold' }} text-nowrap">{{ $data['total'] }}</span>
                    <span class="desk-label text-nowrap">{{ \App\Facades\Tools::morph($data['total'], 'партнёр', 'партнёра', 'партнёров') }}</span>
                    @if($show_won)
                        <span class="desk-label text-nowrap desk-only-w-md" title="Выигранные КП партнёров за всю историю">КП: {{ $data['won_total'] }}</span>
                    @endif
                </div>
                <div class="desk-split mt-2">
                    @foreach($data['rows'] as $row)
                        <div @class(['bg-gray-400' => $row['color'] === '']) style="width: {{ max(0, min(100, $row['share'])) }}%; {{ $fill($row) }}" title="{{ $row['label'] }} · {{ $percent($row['share']) }}"></div>
                    @endforeach
                </div>
            </div>
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
        @elseif($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Грейд</th>
                            <th class="num">Партнёров</th>
                            <th class="desk-only-w-lg" style="width: 35%;">Доля</th>
                            @if($show_won)
                                <th class="num desk-only-w-xl">Выигранных КП</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['rows'] as $row)
                            <tr>
                                <td class="desk-cut">
                                    @if($linked($row))
                                        <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $title($row) }}">
                                            @if($row['color'] !== '')
                                                <i class="fa-light fa-medal me-1" style="color: {{ $row['color'] }}"></i>
                                            @endif
                                            {{ $row['label'] }}
                                        </a>
                                    @else
                                        <span class="d-block text-truncate fw-semibold" title="{{ $title($row) }}">{{ $row['label'] }}</span>
                                    @endif
                                </td>
                                <td class="num fw-bold">{{ $row['count'] }}</td>
                                <td class="desk-only-w-lg">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1">
                                            @if($row['share'] > 0)<i style="width: {{ min(100, $row['share']) }}%; {{ $fill($row) }}"></i>@endif
                                        </div>
                                        <span class="desk-muted text-end text-nowrap" style="min-width: 3.2em;">{{ $percent($row['share']) }}</span>
                                    </div>
                                </td>
                                @if($show_won)
                                    <td class="num desk-muted desk-only-w-xl" title="Выигранные КП партнёров грейда за всю историю">{{ $row['won'] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
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
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md">{{ $percent($row['share']) }}</span>

                        @if($show_won)
                            <span class="desk-muted fs-8 text-nowrap desk-only-w-lg" title="Выигранные КП партнёров грейда за всю историю">КП {{ $row['won'] }}</span>
                        @endif

                        <span class="badge badge-light-primary flex-shrink-0" title="{{ $title($row) }}">{{ $row['count'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
        @endif
    </div>
@endif
