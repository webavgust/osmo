{{-- Виджет «Скоринг партнёров: топ» (patch v30): App\Modules\Pub\Desktop\Widgets\Partner\ScoringTopWidget --}}
@php
    // широкий и не низкий блок — таблица с грейдом и оценкой; иначе список «место · партнёр · балл»
    $table = in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);
    // строка «рейтинг за год» сверху — на высоком блоке, не в узкой колонке
    $summary = $dw !== 'xs' && in_array($dh, ['lg', 'xl'], true);

    // строк не больше 30 (предел настройки «Сколько партнёров»), лишние спрячет .desk-fit
    $list = $data['rows'];

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $year_text = $data['year'] ? 'за ' . $data['year'] . ' год' : 'за всю историю';
    $rank_title = fn($row) => 'Балл ' . $row['score'] . ' · ' . $row['rank_letter'] . ' · ' . $row['rank_label'] . ' · место ' . $row['place'] . ' из ' . $data['total'];
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-trophy"></i> Нет данных{{ $data['year'] ? ' за ' . $data['year'] . ' год' : '' }}
    </div>
@else
    <div class="desk-stack">
        @if($summary)
            <div class="d-flex flex-wrap align-items-baseline column-gap-2">
                <span class="desk-label text-nowrap">рейтинг {{ $year_text }}</span>
                <span class="desk-muted fs-8 text-nowrap" title="Партнёров в рейтинге {{ $year_text }}">в рейтинге: {{ $data['total'] }}</span>
            </div>
        @endif

        @if($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th class="num">Место</th>
                            <th>Партнёр</th>
                            <th class="desk-only-w-lg">Грейд</th>
                            <th class="desk-only-w-xl">Оценка</th>
                            <th class="desk-only-w-xl" style="width: 22%;"></th>
                            <th class="num">Балл</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="num fw-bold">{{ $row['place'] }}</td>
                                <td class="desk-cut">
                                    <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                                </td>
                                <td class="desk-muted text-nowrap desk-only-w-lg">{{ $row['grade'] ?? '—' }}</td>
                                <td class="desk-muted text-nowrap desk-only-w-xl" title="{{ $rank_title($row) }}">{{ $row['rank_letter'] }} · {{ $row['rank_label'] }}</td>
                                <td class="desk-only-w-xl">
                                    <div class="desk-bar">
                                        <i class="bg-{{ $row['rank_color'] }}" style="width: {{ max(0, min(100, $row['score'])) }}%;"></i>
                                    </div>
                                </td>
                                <td class="num">
                                    <span class="badge badge-light-{{ $row['rank_color'] }}" title="{{ $rank_title($row) }}">{{ $row['score'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    <li>
                        <span class="fw-bold w-20px text-center flex-shrink-0 desk-hide-narrow">{{ $row['place'] }}</span>
                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                        @if($row['grade'])
                            <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md">{{ $row['grade'] }}</span>
                        @endif
                        <span class="badge badge-light-{{ $row['rank_color'] }} flex-shrink-0" title="{{ $rank_title($row) }}">{{ $row['score'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}" data-fit-extra="{{ max(0, $data['total'] - count($list)) }}"></div>
    </div>
@endif
