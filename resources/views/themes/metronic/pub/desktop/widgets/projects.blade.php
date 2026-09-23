{{-- Виджет «Проекты по сделкам» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\ProjectsWidget --}}
@php
    // в низком блоке — только счётчик, от трёх ячеек высоты добавляется список проектов
    // (в узкой колонке — одни названия); на широком — таблица
    $compact = in_array($dh, ['xs', 'sm'], true);
    $table = in_array($dw, ['lg', 'xl'], true);
    // строк не больше 30 (настройка «Сколько строк») — отдаём все, лишние спрячет .desk-fit
    $list = $compact ? [] : $data['rows'];
    // высокий блок: строки делят высоту (стиль группы funnel-2)
    $tall = in_array($dh, ['lg', 'xl'], true);

    $waiting = $data['mode'] === 'waiting';
    $counts = $data['counts'];

    // попап проекта (или создания проекта по сделке) — как в реестре сделок
    $box = fn($row) => $preview ? null : ($row['box_url'] ?: null);

    $hint = fn($row) => $row['title']
        . ($row['partner'] !== '' ? "\nПартнёр: " . $row['partner'] : '')
        . ($row['company'] !== '' ? "\nКомпания: " . $row['company'] : '')
        . ($row['stage'] !== '' ? "\nСтадия: " . $row['stage'] : '')
        . "\nСделок: " . $row['deals'] . ($waiting ? '' : ', спецификаций: ' . $row['specs'])
        . ($settings['show_amount'] ? "\nСумма сделок: " . $widget::money($row['amount'], $data['symbol'], false) : '');
@endphp
@if($data['total'] === 0 && empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-diagram-project"></i>
        {{ $waiting ? 'Все проектные сделки сопоставлены' : ($data['mode'] === 'archive' ? 'Архивных проектов нет' : 'Проектов нет') }}
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list)])>
        <div>
            @php $label = mb_strtolower($data['mode_label']) . ($data['partner'] ? ' · ' . $data['partner'] : ''); @endphp
            <div class="desk-label desk-nowrap" title="{{ $label }}">{{ $label }}</div>
            <div @class(['desk-value', 'text-warning' => $waiting && $data['total'] > 0])
                 title="{{ $waiting ? 'Сделки в проектных стадиях, у которых нет проекта: компания Битрикса не сопоставлена партнёру портала' : 'Проектов по сделкам' }}">
                {{ $data['total'] }}
            </div>

            <div class="d-flex gap-2 align-items-baseline flex-wrap desk-hide-short">
                @if($settings['show_amount'])
                    <span class="desk-muted text-nowrap" title="{{ $waiting ? 'Сумма всех ждущих сделок' : 'Сумма сделок всех проектов в счётчике' }}, пересчитанная по курсу на сегодня: {{ $widget::money($data['amount'], $data['symbol'], false) }}">
                        {{ $widget::money($data['amount'], $data['symbol']) }}
                    </span>
                @endif
                @if(!$waiting)
                    <span class="badge badge-light-{{ $data['mode'] === 'archive' ? 'info' : 'secondary' }} fs-8 text-nowrap desk-only-w-md"
                          title="{{ $data['mode'] === 'archive' ? 'Действующие проекты' : 'Проекты в архиве' }}">
                        {{ $data['mode'] === 'archive' ? 'действующих' : 'в архиве' }}:
                        {{ $data['mode'] === 'archive' ? $counts['active'] : $counts['archive'] }}
                    </span>
                @endif
                @if($counts['waiting'] > 0 && !$waiting)
                    <span class="badge badge-light-warning fs-8 text-nowrap desk-only-w-md"
                          title="Сделки в проектных стадиях без проекта: компания Битрикса не сопоставлена партнёру портала">
                        <i class="fa-light fa-triangle-exclamation me-1"></i>ждут сопоставления: {{ $counts['waiting'] }}
                    </span>
                @endif
                @if(!empty($data['skipped']) && $settings['show_amount'])
                    <span class="desk-muted fs-8 text-nowrap desk-only-w-lg">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>
        </div>

        @if(!empty($list) && $table)
            {{-- data-fit-min="0": если счётчик занял всё место, строки прячутся целиком --}}
            <div @class(['desk-fit', 'desk-stack-grow', 'projects-tall-table' => $tall]) data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    {{-- на высоте 3–5 ячеек счётчик занимает почти всё — шапка без строк не нужна --}}
                    <thead class="desk-only-h-lg">
                        <tr>
                            <th>{{ $waiting ? 'Сделка' : 'Проект' }}</th>
                            <th class="desk-only-w-lg">{{ $waiting ? 'Компания Битрикс24' : 'Партнёр' }}</th>
                            <th class="desk-only-w-xl">{{ $waiting ? 'Стадия' : 'Компания' }}</th>
                            <th class="num desk-only-w-xl">{{ $waiting ? 'Дата' : 'Сделок' }}</th>
                            @if(!$waiting)
                                <th class="num desk-only-w-xl" title="Спецификаций в проекте">Спец.</th>
                            @endif
                            <th class="num">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <a @if($box($row)) href="javascript:box({href: '{{ $box($row) }}'})" @else href="javascript:void(0)" @endif
                                       class="desk-link d-block text-truncate fw-semibold text-hover-primary"
                                       title="{{ $hint($row) }}">{{ $row['title'] }}</a>
                                </td>
                                <td class="desk-cut desk-muted desk-only-w-lg" title="{{ $waiting ? $row['company'] : $row['partner'] }}">
                                    {{ ($waiting ? $row['company'] : $row['partner']) ?: '—' }}
                                </td>
                                <td class="desk-cut desk-muted desk-only-w-xl" title="{{ $waiting ? $row['stage'] : $row['company'] }}">
                                    {{ ($waiting ? $row['stage'] : $row['company']) ?: '—' }}
                                </td>
                                <td class="num desk-muted desk-only-w-xl text-nowrap">{{ $waiting ? $row['date'] : $row['deals'] }}</td>
                                @if(!$waiting)
                                    <td class="num desk-muted desk-only-w-xl">{{ $row['specs'] }}</td>
                                @endif
                                <td class="num fw-semibold text-nowrap" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                                    {{ $row['amount'] > 0 ? $widget::money($row['amount'], $data['symbol']) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8 desk-only-h-lg desk-fit-out" data-fit-more="ещё {n}"></div>
        @elseif(!empty($list))
            <ul @class(['desk-list', 'desk-fit', 'desk-stack-grow', 'projects-tall-list' => $tall]) data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <span class="bullet bullet-dot bg-{{ $row['color'] }} w-8px h-8px flex-shrink-0 desk-hide-narrow"
                              title="{{ $row['pilot'] ? 'Пилотный проект' : $data['mode_label'] }}"></span>
                        <a @if($box($row)) href="javascript:box({href: '{{ $box($row) }}'})" @else href="javascript:void(0)" @endif
                           class="desk-link desk-grow text-hover-primary" title="{{ $hint($row) }}">
                            {{ $waiting ? $row['title'] : ($row['partner'] ?: $row['title']) }}
                        </a>
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md"
                              title="{{ $waiting ? 'Дата создания сделки' : 'Сделок в проекте' }}">{{ $waiting ? $row['date'] : $row['deals'] . ' сд.' }}</span>
                        @if($settings['show_amount'])
                            {{-- в колонке шириной 2 сумма не влезает рядом с названием — она в подсказке --}}
                            <span class="fw-semibold text-nowrap flex-shrink-0 desk-hide-narrow"
                                  title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                                {{ $row['amount'] > 0 ? $widget::money($row['amount'], $data['symbol']) : '—' }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-only-h-lg desk-fit-out" data-fit-more="ещё {n}"></div>
        @endif
    </div>
@endif
