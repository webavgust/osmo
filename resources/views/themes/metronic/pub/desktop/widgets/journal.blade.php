{{-- Виджет «Заметки по компаниям» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\JournalWidget --}}
@php
    // широкий блок — таблица с КП и текстом заметки; средний и узкий — список «компания, текст, дата»;
    // в самом узком (xs) дата встаёт над компанией. Строки — с запасом, лишние прячет .desk-fit,
    // а в высоком блоке текст заметки переносится на 2–3 строки (контейнерный запрос в common-2.css)
    $table = in_array($dw, ['lg', 'xl'], true);
    $narrow = $dw === 'xs';
    $list = array_slice($data['rows'], 0, $rows_max);
    $box = fn($row) => $preview ? null : ($row['url'] ?: null);
    $hint = fn($row) => trim($row['date'] . ' · ' . $row['company']
        . ($row['proposal'] !== '' ? ' · КП ' . $row['proposal'] : '') . ' · ' . $row['text']);
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-note-sticky"></i> <span class="desk-hide-narrow">Записей журнала нет</span>
    </div>
@elseif($table)
    <div class="desk-stack">
        <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
            <table class="desk-table dj-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Компания</th>
                        <th class="desk-only-w-xl">КП</th>
                        <th>Заметка</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $row)
                        <tr>
                            <td class="desk-muted">{{ $row['date'] }}</td>
                            <td class="dj-company-cell">
                                <a @if($box($row)) href="javascript:box({href: '{{ $box($row) }}'})" @else href="javascript:void(0)" @endif
                                   class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $hint($row) }}">
                                    {{ $row['company'] }}
                                </a>
                            </td>
                            <td class="desk-only-w-xl">
                                @if($row['proposal'] !== '')
                                    <a href="{{ $preview || empty($row['proposal_url']) ? 'javascript:void(0)' : $row['proposal_url'] }}"
                                       class="desk-link text-hover-primary" title="КП {{ $row['proposal'] }}">{{ $row['proposal'] }}</a>
                                @else
                                    <span class="desk-muted">—</span>
                                @endif
                            </td>
                            <td class="desk-cut desk-muted"><div class="dj-note" title="{{ $row['text'] }}">{{ $row['text'] }}</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@else
    <div class="desk-stack">
        <ul class="desk-list desk-stack-grow desk-fit dj-list {{ $narrow ? 'dj-narrow' : '' }}">
            @foreach($list as $row)
                <li>
                    <a @if($box($row)) href="javascript:box({href: '{{ $box($row) }}'})" @else href="javascript:void(0)" @endif
                       class="desk-link text-hover-primary fw-semibold dj-company" title="{{ $hint($row) }}">{{ $row['company'] }}</a>
                    <span class="desk-muted fs-8 dj-note" title="{{ $row['text'] }}">{{ $row['text'] }}</span>
                    @if($row['proposal'] !== '')
                        <span class="badge badge-light-primary flex-shrink-0 desk-only-w-lg dj-kp" title="КП {{ $row['proposal'] }}">{{ $row['proposal'] }}</span>
                    @endif
                    <span class="desk-muted fs-8 text-nowrap flex-shrink-0 dj-date">{{ $row['date'] }}</span>
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@endif
