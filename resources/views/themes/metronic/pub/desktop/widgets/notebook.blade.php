{{-- Виджет «Блокнот» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\NotebookWidget --}}
@php
    $add = $preview ? null : ($data['add_url'] ?? null);

    // строки с запасом: не влезшие целиком спрячет .desk-fit
    $list = array_slice($data['rows'], 0, $rows_max);

    // места вдвое больше, чем заметок, — текст заметки второй строкой под названием
    $roomy = $rows >= 2 * max(1, count($list));
@endphp
<div class="desk-stack">
    @if(empty($data['rows']) && !empty($data['hidden_done']))
        {{-- заметки есть, но все выполнены и спрятаны настройкой; в узком блоке — только значок, текст в title --}}
        <div class="desk-stack-grow desk-empty" title="Все задачи выполнены">
            <i class="fa-light fa-circle-check text-success"></i><span class="desk-hide-narrow">Все задачи выполнены</span>
        </div>
    @elseif(empty($data['rows']))
        <div class="desk-stack-grow desk-empty">
            <i class="fa-light fa-note"></i> Заметок пока нет
        </div>
    @else
        <ul class="desk-list desk-stack-grow desk-fit">
            @foreach($list as $row)
                @php
                    $hint = trim($row['title'] . ($row['text'] !== '' ? ' — ' . $row['text'] : ''));
                    $done = !empty($row['done']);
                    // флажок задачи: главный элемент строки, виден во всех размерах
                    $check = $done ? 'fa-solid fa-square-check text-success' : 'fa-light fa-square';
                    $check_title = $done ? 'Вернуть в работу' : 'Отметить выполненной';
                    // выполненная — без жирного, зачёркнута и приглушена
                    $title_class = $done ? 'text-decoration-line-through desk-muted' : 'fw-semibold';
                @endphp
                <li @if($done) class="nb-done" @endif>
                    @if(!$preview && !empty($row['done_url']))
                        <a href="javascript:void(0)" class="nb-check flex-shrink-0" data-desk-post="{{ $row['done_url'] }}"
                           title="{{ $check_title }}"><i class="{{ $check }}"></i></a>
                    @else
                        <span class="nb-check flex-shrink-0"><i class="{{ $check }}"></i></span>
                    @endif
                    @if($roomy)
                        <div class="desk-grow">
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0)"
                                   @if(!$preview && !empty($row['url'])) onclick="sidebar({href: '{{ $row['url'] }}'})" @endif
                                   class="desk-link desk-grow nb-title {{ $title_class }} text-hover-primary"
                                   title="{{ $hint }}">{{ $row['title'] }}</a>
                                @if($row['favorite'])
                                    <i class="fa-solid fa-star text-warning fs-8 flex-shrink-0 desk-hide-narrow" title="Избранная заметка"></i>
                                @endif
                            </div>
                            @if($row['text'] !== '')
                                <div class="desk-muted desk-nowrap fs-7" title="{{ $hint }}">{{ $row['text'] }}</div>
                            @endif
                        </div>
                    @else
                        {{-- название по содержимому, текст сразу за ним и до даты; дата прижата вправо --}}
                        <a href="javascript:void(0)"
                           @if(!$preview && !empty($row['url'])) onclick="sidebar({href: '{{ $row['url'] }}'})" @endif
                           @class(['desk-link desk-grow nb-title text-hover-primary', $title_class, 'nb-title-cut' => $row['text'] !== ''])
                           title="{{ $hint }}">{{ $row['title'] }}</a>
                        @if($row['favorite'])
                            <i class="fa-solid fa-star text-warning fs-8 flex-shrink-0 desk-hide-narrow" title="Избранная заметка"></i>
                        @endif
                        @if($row['text'] !== '')
                            <span class="desk-muted desk-grow nb-text desk-only-w-lg" title="{{ $hint }}">{{ $row['text'] }}</span>
                        @endif
                    @endif
                    <span class="desk-muted fs-8 text-nowrap flex-shrink-0 ms-auto desk-only-w-md">{{ $row['date'] }}</span>
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 flex-shrink-0 desk-hide-short" data-fit-more="ещё {n}"></div>
    @endif

    @if($add)
        <div class="flex-shrink-0 desk-hide-short">
            <a href="javascript:void(0)" onclick="sidebar({href: '{{ $add }}'})"
               class="text-primary fw-semibold text-nowrap" title="Создать заметку">
                {{-- в узком блоке — только плюс, подпись в title --}}
                <i class="fa-light fa-plus text-primary"></i><span class="desk-hide-narrow"> Добавить заметку</span>
            </a>
        </div>
    @endif
</div>
