{{-- Виджет «Карточка партнёра» (patch v30): App\Modules\Pub\Desktop\Widgets\Partner\PartnerCardWidget --}}
@php
    $href = $preview || empty($data['url']) ? 'javascript:void(0)' : $data['url'];
    $morph = fn($number, ...$forms) => \App\Facades\Tools::morph($number, ...$forms);

    // узкий (ширина 2–5) или низкий (высота 1–2) блок — балл крупно; в узкой высокой колонке ниже цифры списком
    $compact = in_array($dw, ['xs', 'sm'], true) || in_array($dh, ['xs', 'sm'], true);
    $compact_list = $compact && in_array($dh, ['lg', 'xl'], true);
    // широкий блок от высоты 6: две плитки сумм, остальные цифры сеткой; от высоты 10 сетка на всю высоту
    $grid = !$compact && in_array($dh, ['lg', 'xl'], true);
    $stretch = $grid && $dh === 'xl';

    // в колонке шириной 2 сумма с символом валюты не влезает — только число, полная сумма в подсказке
    $money = fn($value) => $dw === 'xs' ? $widget::compact($value) : $widget::money($value, $data['symbol']);
    $full = fn($value) => $widget::money($value, $data['symbol'], false);

    $year_text = $data['year'] ? ' за ' . $data['year'] . ' год' : '';
    $scored = $settings['scoring'] && $data['scored'];
    $score_title = $data['scored']
        ? 'Балл ' . $data['score'] . ' · ' . $data['rank_letter'] . ' · ' . $data['rank_label']
            . ' · место ' . $data['place'] . ' из ' . $data['total'] . $year_text
        : 'В рейтинге' . $year_text . ' данных нет';
    $name_title = $data['name'] . ($data['region'] !== '' ? ' · ' . $data['region'] : '')
        . ($data['grade'] !== '' ? ' · ' . $data['grade'] : '');
    $percent = fn($value) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 1, ',', ' '), '0'), ',') . ' %';

    // все цифры карточки: ключ => [подпись, значение, подсказка, класс значения]
    $facts = [];
    if ($settings['money']) {
        $facts['specs_sum'] = ['подписано', $money($data['specs_sum']), 'Сумма подписанных спецификаций: ' . $full($data['specs_sum']) . $year_text, ''];
        $facts['paid_sum'] = ['оплачено', $money($data['paid_sum']), 'Оплаты партнёра: ' . $full($data['paid_sum']) . $year_text, 'text-success'];
        $facts['expected_sum'] = ['ожидаем', $money($data['expected_sum']), 'Плановые платежи без факта: ' . $full($data['expected_sum']), ''];
        $facts['amount_won'] = ['выиграно КП', $money($data['amount_won']), 'Сумма выигранных КП: ' . $full($data['amount_won']) . $year_text, ''];
        if ($data['overdue_sum'] > 0) {
            $facts['overdue_sum'] = ['просрочка', $money($data['overdue_sum']), 'Просрочено: ' . $full($data['overdue_sum']), 'text-danger'];
        }
    }
    if ($scored) {
        $facts['place'] = ['место', $data['place'] . ' из ' . $data['total'], $score_title, ''];
        // «из» — все КП партнёра за год (и в работе тоже); конверсия ниже — от решённых
        $facts['won'] = ['КП выиграно', $data['won'] . ' из ' . $data['proposals'], 'Выиграно из всех КП партнёра' . $year_text, ''];
        $facts['conversion'] = ['конверсия', $percent($data['conversion']), 'Конверсия решённых КП' . $year_text, ''];
    }
    if ($settings['counts']) {
        $facts['companies'] = ['компаний', $data['companies'], 'Компаний партнёра', ''];
        $facts['contracts'] = ['договоров', $data['contracts'], 'Договоров партнёра', ''];
        $facts['specs_signed'] = ['спецификаций', $data['specs_signed'], 'Подписанных спецификаций' . $year_text, ''];
        $facts['deals'] = ['сделок', $data['deals'], 'Сделки Битрикс24' . $year_text, ''];
        $facts['projects'] = ['проектов', $data['projects'], 'Проекты партнёра' . $year_text, ''];
    }

    // главное число узкого блока — балл; без балла — сумма или компании (в списке его не повторяем)
    $main_key = $scored ? null : ($settings['money'] ? 'specs_sum' : 'companies');

    // плитки широкого блока; в режиме сетки только первые две, остальное ниже
    $tile_keys = array_values(array_filter(
        $settings['money'] ? ['specs_sum', 'paid_sum', 'expected_sum', 'amount_won'] : ['companies', 'contracts', 'deals', 'projects'],
        fn($key) => isset($facts[$key])
    ));
    if ($grid) $tile_keys = array_slice($tile_keys, 0, 2);
    $tile_only = ['', '', 'desk-only-w-lg', 'desk-only-w-xl'];
    $rest = array_diff_key($facts, array_flip($tile_keys));
@endphp
@if(empty($data['found']))
    <div class="desk-empty">
        <i class="fa-light fa-address-card"></i> Партнёр не выбран
    </div>
@elseif($compact)
    <div class="{{ $compact_list ? 'desk-stack' : 'desk-center' }}">
        <div>
            {{-- многоточие на самой ссылке: длинное название в узкой колонке не обрезается краем --}}
            <div class="desk-label">
                <a href="{{ $href }}" class="desk-link desk-nowrap d-block text-hover-primary" title="{{ $name_title }}">{{ $data['name'] }}</a>
            </div>
            @if($scored)
                <div class="desk-value" title="{{ $score_title }}">{{ $data['score'] }}</div>
            @elseif($main_key && isset($facts[$main_key]))
                <div class="desk-value desk-value-sm" title="{{ $facts[$main_key][2] }}">{{ $facts[$main_key][1] }}</div>
            @endif
            <div class="d-flex gap-2 align-items-baseline flex-wrap desk-hide-short">
                @if($data['grade'] !== '')
                    <span class="badge badge-light d-inline-block mw-100 text-truncate" title="{{ $data['grade'] }}{{ $data['grade_hint'] !== '' ? ' · ' . $data['grade_hint'] : '' }}">
                        @if($data['grade_color'] !== '')<i class="fa-light fa-medal me-1" style="color: {{ $data['grade_color'] }}"></i>@endif{{ $data['grade'] }}
                    </span>
                @endif
                @if($scored && !$compact_list)
                    <span class="desk-muted text-nowrap desk-only-w-md" title="{{ $score_title }}">место {{ $data['place'] }} из {{ $data['total'] }}</span>
                @endif
            </div>
        </div>
        @if($compact_list)
            {{-- в очень высокой колонке строки делят высоту поровну: при выключенных суммах или балле цифр мало --}}
            <ul @class(['desk-list', 'desk-stack-grow', 'desk-fit', 'pc-list-stretch' => $dh === 'xl']) data-fit-min="0">
                @foreach($facts as $key => [$label, $value, $hint, $class])
                    @continue($key === $main_key)
                    <li class="flex-wrap column-gap-2" title="{{ $hint }}">
                        <span class="desk-muted desk-grow">{{ $label }}</span>
                        <span class="fw-bold text-nowrap {{ $class }}">{{ $value }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
        @endif
    </div>
@else
    <div @class(['desk-stack', 'pc-stretch' => $stretch])>
        <div class="d-flex align-items-center gap-2">
            @if($data['grade_color'] !== '')
                <i class="fa-light fa-medal flex-shrink-0 desk-hide-narrow" style="color: {{ $data['grade_color'] }}" title="{{ $data['grade'] }} · {{ $data['grade_hint'] }}"></i>
            @endif
            <a href="{{ $href }}" class="desk-link desk-grow fw-semibold text-hover-primary" title="{{ $name_title }}">{{ $data['name'] }}</a>
            @if($data['grade'] !== '')
                <span class="badge badge-light flex-shrink-0 desk-only-w-md" title="{{ $data['grade_hint'] }}">{{ $data['grade'] }}</span>
            @endif
            @if($scored)
                <span class="badge badge-light-{{ $data['rank_color'] }} flex-shrink-0" title="{{ $score_title }}">{{ $data['score'] }}</span>
            @endif
        </div>

        @if($tile_keys)
            <div class="pc-tiles">
                @foreach($tile_keys as $i => $key)
                    <div class="{{ $tile_only[$i] }}">
                        <div class="desk-label desk-nowrap">{{ $facts[$key][0] }}</div>
                        <div class="pc-tile-value {{ $facts[$key][3] }}" title="{{ $facts[$key][2] }}">{{ $facts[$key][1] }}</div>
                        @if(!$grid && $key === 'specs_sum')
                            <div class="desk-muted fs-8 desk-nowrap desk-only-h-md" title="Подписанных спецификаций{{ $year_text }}">{{ $data['specs_signed'] }} {{ $morph($data['specs_signed'], 'спецификация', 'спецификации', 'спецификаций') }}</div>
                        @elseif(!$grid && $key === 'paid_sum' && $data['overdue_sum'] > 0)
                            <div class="desk-muted fs-8 desk-nowrap desk-only-h-md" title="{{ $facts['overdue_sum'][2] }}">просрочка {{ $facts['overdue_sum'][1] }}</div>
                        @elseif(!$grid && $key === 'amount_won')
                            <div class="desk-muted fs-8 desk-nowrap desk-only-h-md" title="Выиграно из всех КП партнёра{{ $year_text }}">{{ $data['won'] }} из {{ $data['proposals'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($grid)
            <div @class(['pc-facts', 'desk-stack-grow', 'desk-fit', 'pc-facts-stretch' => $stretch]) data-fit-min="0">
                @foreach($rest as [$label, $value, $hint, $class])
                    <div class="pc-fact" title="{{ $hint }}">
                        <div class="desk-label desk-nowrap">{{ $label }}</div>
                        <div class="pc-fact-value {{ $class }}">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
        @endif

        <div @class(['d-flex gap-2 align-items-baseline align-content-start flex-wrap desk-hide-short desk-fit', 'desk-stack-grow' => !$grid]) data-fit-min="0">
            @if(!$grid && $settings['counts'] && $settings['money'])
                <span class="badge badge-light-primary fs-8 text-nowrap">компаний: {{ $data['companies'] }}</span>
                <span class="badge badge-light-primary fs-8 text-nowrap desk-only-w-md">договоров: {{ $data['contracts'] }}</span>
                <span class="badge badge-light-info fs-8 text-nowrap desk-only-w-md" title="Сделки Битрикс24{{ $year_text }}">сделок: {{ $data['deals'] }}</span>
                <span class="badge badge-light-info fs-8 text-nowrap desk-only-w-lg" title="Проекты партнёра{{ $year_text }}">проектов: {{ $data['projects'] }}</span>
            @endif
            @if(!$grid && $scored)
                <span class="desk-muted fs-8 text-nowrap" title="{{ $score_title }}">место {{ $data['place'] }} из {{ $data['total'] }}</span>
                <span class="desk-muted fs-8 text-nowrap desk-only-w-lg" title="Конверсия решённых КП">конверсия {{ $percent($data['conversion']) }}</span>
            @endif
            @if(!$data['active'])
                <span class="badge badge-light-danger fs-8 text-nowrap">не активен</span>
            @endif
            @if($settings['counts'] && !$data['crm_linked'])
                <span class="badge badge-light-warning fs-8 text-nowrap" title="Партнёр не сопоставлен с компаниями Битрикс24 — его сделки не попадают в скоринг">без Битрикс24</span>
            @endif
            @if($settings['scoring'] && !$data['scored'])
                <span class="desk-muted fs-8 text-nowrap">в рейтинге{{ $year_text }} данных нет</span>
            @endif
        </div>
    </div>
@endif
