{{-- Виджет «Проблемы в сделках» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\DealIssuesWidget

    Поведение по размерам:
    - низкий блок (dh xs|sm): подпись (шире 170 px) и число проблемных сделок по центру;
    - высота 3–5 (dh md): число и подпись одной строкой (на ширине 2 — только число),
      ниже сделки в .desk-fit — значок (шире 170 px), название, число замечаний;
    - высота ≥6 (dh lg|xl): число крупно, в том же .desk-fit сначала типы проблем со счётчиками,
      затем сделки (подгон прячет сделки первыми);
    - ширина ≥12 (dw lg|xl): сводка и типы колонкой слева, справа таблица сделок на всю высоту —
      сделка, проблема, «+N» ещё замечаний, менеджер от 620 px (сетка — osmo-desktop-widgets/funnel-1.css).
--}}
@php
    $low = in_array($dh, ['xs', 'sm'], true);
    $types_list = in_array($dh, ['lg', 'xl'], true);
    $tight = $dh === 'md';
    $list = $low ? [] : array_slice($data['rows'], 0, $rows_max);
    $side = in_array($dw, ['lg', 'xl'], true) && !empty($list);
    $counters = array_slice($data['counters'], 0, $rows_max);

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $deals = fn($count) => $count . ' ' . \App\Facades\Tools::morph($count, 'сделка', 'сделки', 'сделок');
    $reason = fn($row) => implode(' · ', $row['issues']);
    $title = fn($row) => $row['title'] . ' · ' . $row['stage'] . ' · ' . $row['manager'] . ' — ' . $reason($row);

    $total_title = $deals($data['total']) . ' с проблемами, всего замечаний: ' . $data['issue_total'];
    $label = \App\Facades\Tools::morph($data['total'], 'сделка', 'сделки', 'сделок') . ' с проблемами';
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-circle-check"></i> Проблемных сделок нет
    </div>
@else
    <div @class(['h-100', 'cmm-side' => $side, 'desk-stack' => !$side, 'desk-center' => empty($list)])>
        <div class="cmm-summary">
            @if($tight)
                <div class="d-flex align-items-baseline flex-wrap gap-2">
                    <div class="desk-value desk-value-sm text-warning" title="{{ $total_title }}">{{ $data['total'] }}</div>
                    <span class="desk-label desk-nowrap desk-hide-narrow">{{ $label }}</span>
                </div>
            @else
                <div class="desk-label desk-nowrap desk-hide-narrow">проблемные сделки</div>
                <div class="desk-value text-warning" title="{{ $total_title }}">{{ $data['total'] }}</div>
            @endif
        </div>

        @if($side && $types_list && !empty($counters))
            <ul class="desk-list desk-fit cmm-types" data-fit-min="0">
                @foreach($counters as $counter)
                    <li title="{{ $counter['label'] }}: {{ $deals($counter['count']) }}">
                        <span class="desk-grow">{{ $counter['label'] }}</span>
                        <span class="fw-bold">{{ $counter['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if(!empty($list))
            <div class="desk-stack desk-stack-grow cmm-list">
                @if($side)
                    <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                        <table class="desk-table">
                            <thead>
                            <tr>
                                <th>Сделка</th>
                                <th class="cmm-issue">Проблема</th>
                                <th></th>
                                <th class="desk-only-w-xl">Менеджер</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($list as $row)
                                <tr>
                                    <td class="desk-cut">
                                        <a href="{{ $href($row) }}" target="_blank" class="desk-link text-hover-primary d-block text-truncate fw-semibold"
                                           title="{{ $title($row) }}">{{ $row['title'] }}</a>
                                    </td>
                                    <td class="desk-cut desk-muted cmm-issue" title="{{ $reason($row) }}">{{ $row['issues'][0] }}</td>
                                    <td class="num">
                                        @if(count($row['issues']) > 1)
                                            <span class="badge badge-light-warning" title="{{ $reason($row) }}">+{{ count($row['issues']) - 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="desk-muted desk-only-w-xl">{{ $row['manager'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <ul class="desk-list desk-stack-grow desk-fit" data-fit-min="0">
                        {{-- высокий блок: сначала типы проблем со счётчиками, затем сделки (подгон прячет с конца) --}}
                        @if($types_list)
                            @foreach($counters as $counter)
                                <li title="{{ $counter['label'] }}: {{ $deals($counter['count']) }}">
                                    <span class="desk-grow">{{ $counter['label'] }}</span>
                                    <span class="fw-bold">{{ $counter['count'] }}</span>
                                </li>
                            @endforeach
                        @endif
                        @foreach($list as $i => $row)
                            <li @class(['pt-3' => $types_list && $i === 0 && !empty($counters)])>
                                <i class="fa-light fa-triangle-exclamation text-warning flex-shrink-0 desk-hide-narrow"></i>
                                <a href="{{ $href($row) }}" target="_blank" class="desk-link desk-grow text-hover-primary" title="{{ $title($row) }}">{{ $row['title'] }}</a>
                                <span class="badge badge-light-warning flex-shrink-0" title="{{ $reason($row) }}">{{ count($row['issues']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="desk-label desk-nowrap desk-hide-short" data-fit-more="ещё {n}"></div>
            </div>
        @endif
    </div>
@endif
