{{-- Виджет «Синхронизация Битрикс24» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\SyncWidget --}}
@php
    $last = $data['last'];
    $oldest = $data['oldest'];

    // список таблиц — от трёх ячеек высоты; таблиц в зеркале немного — отдаём все, лишние спрячет .desk-fit
    $list = $settings['show_tables'] && !in_array($dh, ['xs', 'sm'], true) ? $data['rows'] : [];
    // высокий блок: строки таблиц делят высоту (стиль группы funnel-2); очень высокий — вместо строки
    // подписей список фактов (на высоте 6–9 ячеек факты со счётчиком съедали всё место под таблицы)
    $tall = $dh === 'xl';
    $stretch = in_array($dh, ['lg', 'xl'], true);
    // колонка шириной 2: дата коротко («13.09»), записи — коротко, строки — подпись над значением
    $narrow = $dw === 'xs';
    $short = fn($date) => $narrow && $date ? mb_substr($date, 0, 5) : $date;

    // подсказка крупного числа: точное время переноса и самая давняя таблица
    $hint = ($last['date'] ? 'Последний перенос: ' . $last['date'] : 'Переносов не было')
        . ($oldest['date'] && $oldest['date'] !== $last['date'] ? "\nСамая давняя таблица: " . $oldest['date'] : '')
        . ($data['never'] ? "\nНи разу не переносилось таблиц: " . $data['never'] : '');

    // факты высокого блока: подпись => [значение, подсказка]
    $facts = array_filter([
        'Последний перенос' => $last['date'] ? [$short($last['date']), $last['date']] : null,
        'Самая давняя таблица' => $oldest['date'] && $oldest['date'] !== $last['date'] ? [$short($oldest['date']), $oldest['date']] : null,
        'Записей' => [$narrow ? $widget::compact($data['total']) : number_format($data['total'], 0, ',', ' ') . ' · табл. ' . $data['tables'],
            'Записей в зеркале Битрикс24: ' . $data['total'] . ', таблиц: ' . $data['tables']],
        'Не переносилось' => $data['never'] ? [$data['never'] . ($narrow ? '' : ' табл.'), 'Эти таблицы ни разу не переносились'] : null,
        'Правка сделки' => $settings['show_modified'] && $data['modified']
            ? [$short($data['modified']), 'Самая поздняя дата изменения сделки в выгрузке: ' . $data['modified'] . '. Это правка в Битрикс24, а не время переноса']
            : null,
    ]);
@endphp
@if(!$data['ok'])
    <div class="desk-empty">
        <i class="fa-light fa-plug-circle-xmark"></i> Зеркало Битрикс24 недоступно
    </div>
@elseif(!$data['tables'])
    <div class="desk-empty">
        <i class="fa-light fa-database"></i> В зеркале нет таблиц
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list) && !$tall])>
        <div>
            <div class="desk-label desk-nowrap">обновлено</div>
            <div class="desk-value text-{{ $last['color'] }}" title="{{ $hint }}">{{ $last['age'] }}
                @if($last['date'])
                    <span class="desk-value-sm desk-muted desk-only-w-md">назад</span>
                @endif
            </div>

            @unless($tall)
                <div class="d-flex gap-2 align-items-baseline flex-wrap desk-hide-short">
                    @if($last['date'])
                        <span class="desk-muted text-nowrap" title="{{ $last['date'] }}">{{ $short($last['date']) }}</span>
                    @endif
                    <span class="desk-muted fs-8 text-nowrap desk-only-w-md"
                          title="Записей в зеркале Битрикс24, таблиц: {{ $data['tables'] }}">
                        {{ $widget::compact($data['total']) }} записей
                    </span>
                    @if($data['never'])
                        <span class="badge badge-light-secondary fs-8 text-nowrap desk-only-w-lg"
                              title="Эти таблицы ни разу не переносились">не переносилось: {{ $data['never'] }}</span>
                    @endif
                    @if($settings['show_modified'] && $data['modified'])
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-lg"
                              title="Самая поздняя дата изменения сделки в выгрузке. Это правка в Битрикс24, а не время переноса">
                            правка сделки: {{ $data['modified'] }}
                        </span>
                    @endif
                </div>
            @endunless
        </div>

        @if($tall)
            <ul @class(['desk-list', 'sync-stacked' => $narrow])>
                @foreach($facts as $label => [$value, $title])
                    <li title="{{ $title }}">
                        <span class="desk-label {{ $narrow ? 'desk-nowrap' : 'desk-grow' }}" title="{{ $label }}">{{ $label }}</span>
                        <span class="fw-semibold text-nowrap flex-shrink-0">{{ $value }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if(!empty($list))
            {{-- data-fit-min="0": если счётчик занял всё место, строки прячутся целиком --}}
            <ul @class(['desk-list', 'desk-fit', 'desk-stack-grow', 'sync-tall' => $stretch, 'sync-stacked' => $tall && $narrow]) data-fit-min="0">
                @foreach($list as $row)
                    <li title="{{ $row['table'] }}: {{ $row['count'] }} зап. · {{ $row['date'] ? 'перенесено ' . $row['date'] : 'ни разу не переносилось' }}">
                        @if($tall && $narrow)
                            <span class="desk-nowrap desk-label" title="{{ $row['table'] }}">{{ $row['table'] }}</span>
                        @else
                            <span class="bullet bullet-dot bg-{{ $row['color'] }} w-8px h-8px flex-shrink-0"
                                  title="{{ $row['date'] ? 'Перенесено ' . $row['date'] : 'Ни разу не переносилось' }}"></span>
                            <span class="desk-grow" title="{{ $row['table'] }}">{{ $row['table'] }}</span>
                            <span class="desk-muted fs-8 text-nowrap desk-only-w-md"
                                  title="Записей в таблице">{{ $widget::compact($row['count']) }}</span>
                            @if($tall && $row['date'])
                                <span class="desk-muted fs-8 text-nowrap desk-only-w-lg" title="Перенесено">{{ $row['date'] }}</span>
                            @endif
                        @endif
                        <span class="text-nowrap fs-8 text-{{ $row['color'] }}"
                              title="{{ $row['date'] ?: 'Ни разу не переносилось' }}">{{ $row['age'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-only-h-lg desk-fit-out" data-fit-more="ещё {n}"></div>
        @endif
    </div>
@endif
