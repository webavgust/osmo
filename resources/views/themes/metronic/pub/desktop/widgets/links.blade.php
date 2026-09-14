{{-- Виджет «Набор ссылок» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\LinksWidget --}}
@php
    // плитки — со ширины 3 колонки; в самом узком блоке и по настройке — список
    $tiles = $settings['layout'] === 'tiles' && $dw !== 'xs';
    // ссылок не больше 12: плиток отдаём все, списка — с запасом по высоте; не влезшие целиком прячет .desk-fit
    $list = $tiles ? $data['rows'] : array_slice($data['rows'], 0, $rows_max);
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $hint = fn($row) => trim($row['title'] . ($row['second'] !== '' ? ' · ' . $row['second'] : ''));
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-bookmark"></i> <span class="desk-hide-narrow">Добавьте ссылки в настройках</span>
    </div>
@elseif($tiles)
    {{-- плитки сеткой: колонок по ширине блока, ряды растягиваются на всю высоту;
         в высоком блоке плитка — карточка: значок сверху, название и вторая строка снизу --}}
    <div class="desk-stack">
        <div class="desk-stack-grow desk-fit dl-tiles">
            @foreach($list as $row)
                <a href="{{ $href($row) }}" class="desk-link text-reset dl-tile" title="{{ $hint($row) }}">
                    <span class="symbol flex-shrink-0 dl-symbol">
                        <span class="symbol-label bg-light-primary">
                            <i class="fa-light {{ $row['icon'] }} text-primary dl-icon"></i>
                        </span>
                    </span>
                    <span class="dl-text">
                        <span @class(['fw-semibold dl-title', 'text-muted' => !$row['found']])>{{ $row['title'] }}</span>
                        @if($row['second'] !== '')
                            <span class="desk-muted dl-second desk-only-h-md">{{ $row['second'] }}</span>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>
        <div class="desk-muted fs-8 desk-only-h-md" data-fit-more="ещё {n}"></div>
    </div>
@else
    {{-- список: вторая строка справа (шире 230 px), в высоком блоке — под названием --}}
    <div class="desk-stack">
        <ul class="desk-list desk-stack-grow desk-fit dl-list">
            @foreach($list as $row)
                <li>
                    <i class="fa-light {{ $row['icon'] }} text-primary flex-shrink-0 desk-hide-narrow"></i>
                    <a href="{{ $href($row) }}" @class(['desk-link desk-grow text-hover-primary', 'text-muted' => !$row['found']]) title="{{ $hint($row) }}">
                        <span class="fw-semibold dl-title">{{ $row['title'] }}</span>
                        @if($row['second'] !== '')
                            <span class="desk-muted fs-8 dl-second">{{ $row['second'] }}</span>
                        @endif
                    </a>
                    @if($row['second'] !== '')
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md dl-side">{{ $row['second'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 desk-only-h-md" data-fit-more="ещё {n}"></div>
    </div>
@endif
