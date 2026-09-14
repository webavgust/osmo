{{-- Виджет «Календарь» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\CalendarWidget --}}
@php
    // сетка месяца живёт только в высоком блоке: шесть недель ниже просто не читаются;
    // в две колонки семь дней недели не встают — там список
    $month = $settings['view'] === 'month' && in_array($dh, ['lg', 'xl'], true) && $dw !== 'xs';

    // строки с запасом: не влезшие целиком спрячет .desk-fit
    $list = array_slice($data['rows'], 0, $rows_max);

    // классы ячейки дня: одни и те же у кликабельного дня и у пустого
    $cell = fn($day) => \Illuminate\Support\Arr::toCssClasses([
        'cal-day text-center lh-1 py-1',
        'opacity-25' => !$day['in'],
        'fw-bolder text-primary' => $day['today'],
        'desk-muted' => !$day['today'] && $day['weekend'],
    ]);
@endphp
@if($month)
    <div class="desk-stack">
        <div class="d-flex align-items-baseline gap-2 flex-shrink-0 desk-hide-short">
            <span class="fw-bold desk-grow">{{ $data['month'] }}</span>
            @if($data['count'] > 0)
                <span class="desk-muted fs-8 text-nowrap desk-only-w-md">
                    {{ $data['count'] }} {{ \App\Facades\Tools::morph($data['count'], 'событие', 'события', 'событий') }}
                </span>
            @endif
        </div>

        <div class="d-flex flex-shrink-0">
            @foreach($data['weekdays'] as $index => $weekday)
                <div @class(['flex-fill', 'text-center', 'desk-label', 'text-danger' => $index >= 5])>{{ $weekday }}</div>
            @endforeach
        </div>

        <div class="desk-stack-grow d-flex flex-column">
            @foreach($data['weeks'] as $week)
                <div class="cal-week d-flex flex-fill align-items-center">
                    @foreach($week as $day)
                        @php
                            // точки — в обычном блоке, названия событий — в крупном (переключает personal.css)
                            $marks = '<span class="cal-dots">' . (collect($day['dots'])->map(fn($color) => '<span class="bullet bullet-dot bg-' . e($color) . '"></span>')->implode('') ?: '<span class="bullet bullet-dot bg-transparent"></span>') . '</span>'
                                . '<span class="cal-events fs-8">' . collect($day['events'] ?? [])->map(fn($event) => '<span class="desk-nowrap"><span class="bullet bullet-dot me-1 bg-' . e($event['color']) . '"></span>' . e($event['title']) . '</span>')->implode('') . '</span>';
                        @endphp
                        @if(!$preview && $day['url'])
                            {{-- день с событиями открывает сайдбар первого из них --}}
                            <a href="javascript:void(0)" onclick="sidebar({href: '{{ $day['url'] }}'})" class="desk-link {{ $cell($day) }}" title="{{ $day['title'] }}"><span>{{ $day['day'] }}</span>{!! $marks !!}</a>
                        @else
                            <span class="{{ $cell($day) }}" title="{{ $day['title'] }}"><span>{{ $day['day'] }}</span>{!! $marks !!}</span>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@elseif(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-calendar-days"></i> Событий на ближайшие {{ $data['days'] }} {{ \App\Facades\Tools::morph($data['days'], 'день', 'дня', 'дней') }} нет
    </div>
@else
    <div class="desk-stack">
        <div class="d-flex align-items-baseline gap-2 flex-shrink-0 desk-hide-short desk-hide-narrow">
            <span class="desk-label desk-grow">ближайшие события</span>
            <span class="desk-muted fs-8 text-nowrap desk-only-w-md">
                на {{ $data['days'] }} {{ \App\Facades\Tools::morph($data['days'], 'день', 'дня', 'дней') }}
            </span>
        </div>

        <ul class="desk-list cal-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    <li>
                        <span class="bullet bullet-dot flex-shrink-0 bg-{{ $row['color'] }}"></span>
                        {{-- desk-muted и text-primary друг друга перебивают, поэтому они врозь --}}
                        <span @class([
                                'text-nowrap flex-shrink-0',
                                'fw-semibold text-primary' => $row['today'],
                                'desk-muted' => !$row['today'],
                           ])>{{ $row['when'] }}</span>
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md">{{ $row['time'] }}</span>
                        <a href="javascript:void(0)"
                           @if(!$preview && $row['url']) onclick="sidebar({href: '{{ $row['url'] }}'})" @endif
                           class="desk-link desk-grow text-hover-primary"
                           title="{{ $row['date'] }} {{ $row['time'] }} — {{ $row['title'] }}">{{ $row['title'] }}</a>
                    </li>
                @endforeach
        </ul>
        <div class="desk-muted fs-8 flex-shrink-0 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@endif
