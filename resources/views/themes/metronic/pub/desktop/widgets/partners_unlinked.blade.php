{{-- Виджет «Партнёры без Битрикс24» (patch v30): App\Modules\Pub\Desktop\Widgets\Partner\PartnersUnlinkedWidget --}}
@php
    // список — от высоты 3 ячеек; ниже только число. Широкий и высокий блок — таблица с регионом
    // и числом компаний: при высоте 3–5 шапке таблицы строк уже не остаётся
    $with_list = in_array($dh, ['md', 'lg', 'xl'], true);
    $table = in_array($dh, ['lg', 'xl'], true) && in_array($dw, ['lg', 'xl'], true);

    // все партнёры без сопоставления: лишние строки спрячет .desk-fit, «ещё N» считает их честно
    $list = $data['rows'];

    // число: без списка — крупно по центру; со списком — помельче, в высоком не узком блоке снова крупно
    $value_class = !$with_list || ($dh === 'xl' && $dw !== 'xs') ? 'desk-value' : 'desk-value-sm fw-bold';
    $linked_share = $data['total'] > 0 ? $data['linked'] / $data['total'] * 100 : 0;

    $href = fn($url) => $preview || empty($url) ? 'javascript:void(0)' : $url;
    $word = \App\Facades\Tools::morph($data['total'], 'партнёра', 'партнёров', 'партнёров');
@endphp
<div @class(['desk-stack', 'desk-center' => !$with_list])>
    <div>
        <div class="desk-label desk-nowrap" title="Партнёры без сопоставления с компаниями Битрикс24: их сделки не попадают в скоринг">без компании Битрикс24</div>
        <div class="d-flex flex-wrap align-items-baseline column-gap-2">
            <span @class([$value_class, 'text-nowrap', 'flex-shrink-0', 'text-warning' => $data['count'] > 0, 'text-success' => !$data['count']])>{{ $data['count'] }}</span>
            {{-- в узкой колонке слово не влезает — остаётся «из N» --}}
            <span class="desk-muted text-nowrap desk-hide-short" title="Всего партнёров в отборе: {{ $data['total'] }}">из {{ $data['total'] }}<span class="desk-hide-narrow"> {{ $word }}</span></span>
            <span class="desk-muted fs-8 text-nowrap desk-hide-short desk-only-w-md" title="Партнёры, у которых компания Битрикс24 уже выбрана">сопоставлено: {{ $data['linked'] }}</span>
        </div>
        @if($with_list && $data['total'] > 0)
            <div class="desk-bar mt-2 desk-only-h-lg" title="Сопоставлено {{ $data['linked'] }} из {{ $data['total'] }} · {{ number_format($linked_share, 0, ',', ' ') }} %">
                @if($linked_share > 0)<i class="bg-success" style="width: {{ min(100, $linked_share) }}%;"></i>@endif
            </div>
        @endif
    </div>

    @if($with_list)
        @if(empty($list))
            <div class="desk-stack-grow desk-muted fs-8">Все партнёры сопоставлены с Битрикс24</div>
        @elseif($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Партнёр</th>
                            <th class="desk-only-w-lg">Регион</th>
                            <th class="num desk-only-w-xl">Компаний</th>
                            <th class="num">Сопоставить</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <a href="{{ $href($row['url']) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                                </td>
                                <td class="desk-muted text-nowrap desk-only-w-lg">{{ $row['region'] !== '' ? $row['region'] : '—' }}</td>
                                <td class="num desk-muted desk-only-w-xl" title="Компаний портала у партнёра">{{ $row['companies'] }}</td>
                                <td class="num">
                                    <a href="{{ $href($row['edit_url']) }}" class="desk-link text-hover-primary text-nowrap" title="Открыть форму партнёра и выбрать компанию Битрикс24">
                                        <i class="fa-light fa-link"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <a href="{{ $href($row['url']) }}" class="desk-link desk-grow text-hover-primary" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                        @if($row['region'] !== '')
                            <span class="desk-muted fs-8 text-nowrap text-truncate desk-only-w-md" style="max-width: 9rem;" title="{{ $row['region'] }}">{{ $row['region'] }}</span>
                        @endif
                        <a href="{{ $href($row['edit_url']) }}" class="desk-link flex-shrink-0 text-hover-primary" title="Сопоставить: форма партнёра">
                            <i class="fa-light fa-link"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
        @endif
    @endif
</div>
