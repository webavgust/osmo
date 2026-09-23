{{-- Виджет «Журнал изменений» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\ChangesWidget --}}
@php
    // в широком блоке — таблица, в узком — список. Строк с запасом ($rows_max), но не больше
    // настройки «Сколько событий»; не влезшие по высоте спрячет .desk-fit и подпишет «ещё N»
    $table = in_array($dw, ['lg', 'xl'], true);
    $list = array_slice($data['rows'], 0, max(1, min((int) $settings['limit'], $rows_max)));
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $hint = fn($row) => trim($row['type_label'] . ' · ' . $row['title'] . ' · ' . $row['event_label']
        . ' · ' . $row['user'] . ' · ' . $row['when']
        . ($row['changes'] > 0 ? ' · правок: ' . $row['changes'] : '')
        // объект удалён — ленты нет, строка не ссылка
        . (!$preview && empty($row['url']) ? ' · объект удалён' : ''));
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-timeline"></i> Изменений пока нет
    </div>
@elseif($table)
    <div class="desk-stack">
        <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
            <table class="desk-table">
                <thead>
                    <tr>
                        <th>Событие</th>
                        <th>Объект</th>
                        <th class="desk-only-w-xl">Кто</th>
                        <th class="num desk-only-w-lg">Правок</th>
                        <th class="num">Когда</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $row)
                        <tr>
                            <td>
                                <span class="badge badge-light-{{ $row['color'] }} text-nowrap" title="{{ $row['event_label'] }}">
                                    <i class="fa-light {{ $row['icon'] }} fs-8 me-1"></i>{{ $row['event_label'] }}
                                </span>
                            </td>
                            <td class="desk-cut">
                                <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $hint($row) }}">
                                    {{ $row['title'] }}
                                </a>
                            </td>
                            <td class="desk-muted desk-only-w-xl">{{ $row['user'] }}</td>
                            <td class="num desk-muted desk-only-w-lg">{{ $row['changes'] > 0 ? $row['changes'] : '—' }}</td>
                            <td class="num desk-muted text-nowrap" title="{{ $row['ago'] }}">{{ $row['when'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
    </div>
@else
    <div class="desk-stack">
        <ul class="desk-list desk-stack-grow desk-fit">
            @foreach($list as $row)
                <li>
                    <i class="fa-light {{ $row['icon'] }} text-{{ $row['color'] }} flex-shrink-0 desk-hide-narrow" title="{{ $row['event_label'] }}"></i>
                    {{-- название и автор режутся каждый сам по себе, автор сжимается первым --}}
                    <a href="{{ $href($row) }}" class="desk-link desk-grow d-flex align-items-baseline gap-2 text-hover-primary" title="{{ $hint($row) }}">
                        <span class="changes-title fw-semibold text-truncate">{{ $row['title'] }}</span>
                        <span class="changes-user desk-muted fs-8 text-truncate">{{ $row['user'] }}</span>
                    </a>
                    {{-- время коротко («12 мин.»): полное «12 минут назад» съедало название; оно в подсказке.
                         В узком блоке времени нет места — оно в подсказке названия --}}
                    <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-hide-narrow" title="{{ $row['when'] }} · {{ $row['ago'] }}">{{ $row['ago_short'] ?? $row['ago'] }}</span>
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
    </div>
@endif
