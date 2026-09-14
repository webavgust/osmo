{{-- Виджет «Блокнот» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\NotebookWidget --}}
@php
    $add = $preview ? null : ($data['add_url'] ?? null);

    // строки с запасом: не влезшие целиком спрячет .desk-fit
    $list = array_slice($data['rows'], 0, $rows_max);

    // места вдвое больше, чем заметок, — текст заметки второй строкой под названием
    $roomy = $rows >= 2 * max(1, count($list));
@endphp
<div class="desk-stack">
    @if(empty($data['rows']))
        <div class="desk-stack-grow desk-empty">
            <i class="fa-light fa-note"></i> Заметок пока нет
        </div>
    @else
        <ul class="desk-list desk-stack-grow desk-fit">
            @foreach($list as $row)
                @php $hint = trim($row['title'] . ($row['text'] !== '' ? ' — ' . $row['text'] : '')); @endphp
                <li>
                    <i @class([
                            'fa-star flex-shrink-0 desk-hide-narrow',
                            'fa-solid text-warning' => $row['favorite'],
                            'fa-light desk-muted' => !$row['favorite'],
                       ])
                       @if($row['favorite']) title="Избранная заметка" @endif></i>
                    @if($roomy)
                        <div class="desk-grow">
                            <a href="javascript:void(0)"
                               @if(!$preview && !empty($row['url'])) onclick="sidebar({href: '{{ $row['url'] }}'})" @endif
                               class="desk-link d-block desk-nowrap fw-semibold text-hover-primary"
                               title="{{ $hint }}">{{ $row['title'] }}</a>
                            @if($row['text'] !== '')
                                <div class="desk-muted desk-nowrap fs-7" title="{{ $hint }}">{{ $row['text'] }}</div>
                            @endif
                        </div>
                    @else
                        <a href="javascript:void(0)"
                           @if(!$preview && !empty($row['url'])) onclick="sidebar({href: '{{ $row['url'] }}'})" @endif
                           class="desk-link desk-grow fw-semibold text-hover-primary"
                           title="{{ $hint }}">{{ $row['title'] }}</a>
                        @if($row['text'] !== '')
                            <span class="desk-muted desk-grow desk-only-w-lg" title="{{ $hint }}">{{ $row['text'] }}</span>
                        @endif
                    @endif
                    <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md">{{ $row['date'] }}</span>
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
