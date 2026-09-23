{{-- Виджет «Календарь» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\CalendarWidget --}}
@php
    // сетка месяца живёт только в высоком блоке: шесть недель ниже просто не читаются;
    // в две колонки семь дней недели не встают — там список
    // (данные из кэша до листания — без шапки: пока кэш не сменится, показываем список)
    $month = $settings['view'] === 'month' && in_array($dh, ['lg', 'xl'], true) && $dw !== 'xs' && isset($data['nav'], $data['day']);

    // строки с запасом: не влезшие целиком спрячет .desk-fit
    $list = array_slice($data['rows'], 0, $rows_max);

    if ($month) {
        // шапка месяца и режим плиток: выбор месяца или года; без режима — сетка дней
        $nav = $data['nav'];
        $mode = $view['mode'] ?? null;
        $day_panel = $data['day'];
        $events = array_slice($day_panel['items'], 0, $rows_max);

        // широкий блок: панель дня всегда справа от сетки; в остальных — вместо сетки, пока день выбран
        $wide = in_array($dw, ['lg', 'xl'], true);
        $panel = $wide || !empty($view['day']);

        // стрелки шапки листают месяц, год или десятилетие — по режиму
        [$prev, $next, $prev_title, $next_title] = match ($mode) {
            'months' => [$nav['prev_year'], $nav['next_year'], 'Предыдущий год', 'Следующий год'],
            'years' => [$nav['prev_decade'], $nav['next_decade'], 'Предыдущее десятилетие', 'Следующее десятилетие'],
            default => [$nav['prev'], $nav['next'], 'Предыдущий месяц', 'Следующий месяц'],
        };
    }

    // смена состояния просмотра блока по клику (osmo-desktop.js); в превью кликов нет
    $act = fn(array $patch) => $preview ? '' : 'data-desk-view="' . e(json_encode($patch)) . '"';

    // классы ячейки дня
    $cell = fn($day) => \Illuminate\Support\Arr::toCssClasses([
        'cal-day text-center lh-1',
        'opacity-25' => !$day['in'],
        'fw-bolder' => $day['today'],
        'desk-muted' => !$day['today'] && $day['weekend'],
    ]);

    // кружок числа: сегодня — сплошной, день с событиями — светлый цвета первого события, выбранный — кольцо
    $num = fn($day) => \Illuminate\Support\Arr::toCssClasses([
        'cal-num',
        'bg-primary text-white' => $day['today'],
        $widget::tint($day['color']) => !$day['today'] && $day['color'],
        'fw-semibold' => !$day['today'] && $day['color'],
        'cal-sel' => $day['selected'],
    ]);
@endphp
@if($month)
    <div @class(['cal-wrap', 'cal-wide' => $wide])>
        @if($wide || !$panel)
            <div class="desk-stack cal-main">
                {{-- шапка как в Windows 11: заголовок открывает выбор месяца (года), стрелки листают --}}
                <div class="cal-head d-flex align-items-center gap-1 flex-shrink-0">
                    @if($mode === 'years')
                        <span class="cal-title fw-bold desk-grow">{{ $nav['decade'] }}</span>
                    @elseif($mode === 'months')
                        <a href="javascript:void(0)" class="cal-title desk-link fw-bold desk-grow" title="Выбрать год" {!! $act(['mode' => 'years']) !!}>{{ $nav['year'] }}</a>
                    @else
                        {{-- в узком блоке — короткое название месяца (переключает personal.css) --}}
                        <a href="javascript:void(0)" class="cal-title desk-link fw-bold desk-grow" title="{{ $nav['title'] }} — выбрать месяц" {!! $act(['mode' => 'months']) !!}><span class="cal-long">{{ $nav['title'] }}</span><span class="cal-short">{{ $nav['short'] }}</span></a>
                    @endif
                    @unless($nav['is_current'])
                        <a href="javascript:void(0)" class="cal-btn" title="Сегодня" {!! $act(['month' => $nav['today'], 'day' => null, 'mode' => null]) !!}><i class="fa-light fa-calendar-day"></i></a>
                    @endunless
                    <a href="javascript:void(0)" @class(['cal-btn', 'disabled' => !$prev]) title="{{ $prev_title }}" {!! $prev ? $act(['month' => $prev]) : '' !!}><i class="fa-light fa-chevron-up"></i></a>
                    <a href="javascript:void(0)" @class(['cal-btn', 'disabled' => !$next]) title="{{ $next_title }}" {!! $next ? $act(['month' => $next]) : '' !!}><i class="fa-light fa-chevron-down"></i></a>
                </div>

                @if($mode)
                    {{-- плитки 4×3: месяцы опорного года или годы десятилетия с соседями по краям --}}
                    <div class="cal-tiles desk-stack-grow">
                        @foreach($mode === 'months' ? $data['months'] : $data['years'] as $tile)
                            <a href="javascript:void(0)" @class([
                                    'cal-tile desk-link',
                                    'bg-primary text-white' => $tile['current'],
                                    'cal-sel' => $tile['selected'],
                                    'desk-muted' => !empty($tile['outside']) && !$tile['current'],
                                    'disabled' => !$tile['value'],
                               ]) title="{{ $tile['title'] ?? $tile['label'] }}"
                               {!! $tile['value'] ? $act(['month' => $tile['value'], 'mode' => $mode === 'years' ? 'months' : null]) : '' !!}>{{ $tile['label'] }}</a>
                        @endforeach
                    </div>
                @else
                    <div class="d-flex flex-shrink-0">
                        @foreach($data['weekdays'] as $index => $weekday)
                            <div @class(['flex-fill', 'text-center', 'desk-label', 'text-danger' => $index >= 5])>{{ $weekday }}</div>
                        @endforeach
                    </div>

                    <div class="desk-stack-grow d-flex flex-column">
                        @foreach($data['weeks'] as $week)
                            <div class="cal-week d-flex flex-fill align-items-center">
                                @foreach($week as $day)
                                    {{-- клик по дню — панель дня; день соседнего месяца заодно листает на него --}}
                                    <a href="javascript:void(0)" class="desk-link {{ $cell($day) }}" title="{{ $day['title'] }}"
                                       {!! $act(['day' => $day['key'], 'month' => $day['ym']]) !!}><span class="{{ $num($day) }}">{{ $day['day'] }}</span>@if($day['events'])<span class="cal-events fs-8">@foreach($day['events'] as $event)<span class="desk-nowrap"><span class="bullet bullet-dot me-1 {{ $widget::dot($event['color']) }}"></span>{{ $event['title'] }}</span>@endforeach</span>@endif</a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if($panel)
            {{-- панель дня: события выбранного дня (в широком блоке — сегодня, пока день не выбран) --}}
            <div @class(['desk-stack cal-side', 'border-start' => $wide])>
                <div class="cal-head d-flex align-items-center gap-2 flex-shrink-0">
                    @unless($wide)
                        <a href="javascript:void(0)" class="cal-btn" title="Назад к месяцу" {!! $act(['day' => null]) !!}><i class="fa-light fa-arrow-left"></i></a>
                    @endunless
                    <span @class(['fw-bold text-nowrap', 'text-primary' => $day_panel['today']]) title="{{ $day_panel['label'] }}">{{ $day_panel['date'] }}</span>
                    {{-- день недели в узком блоке не встаёт рядом со стрелкой — он в title даты --}}
                    <span class="desk-muted desk-grow desk-only-w-md" title="{{ $day_panel['label'] }}">{{ $day_panel['weekday'] }}</span>
                </div>

                @if(empty($events))
                    <div class="desk-stack-grow desk-muted cal-none">Событий нет</div>
                @else
                    <ul class="desk-list cal-agenda desk-stack-grow desk-fit">
                        @foreach($events as $item)
                            <li>
                                <span class="bullet bullet-dot flex-shrink-0 {{ $widget::dot($item['color']) }}"></span>
                                <span class="desk-muted fs-8 text-nowrap flex-shrink-0">{{ $item['time'] }}</span>
                                <a href="javascript:void(0)"
                                   @if(!$preview && $item['url']) onclick="sidebar({href: '{{ $item['url'] }}'})" @endif
                                   class="desk-link desk-grow text-hover-primary"
                                   title="{{ $item['time'] }} — {{ $item['title'] }}">{{ $item['title'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="desk-muted fs-8 flex-shrink-0" data-fit-more="ещё {n}"></div>
                @endif

                <div class="flex-shrink-0">
                    <a href="javascript:void(0)"
                       @if(!$preview) onclick="sidebar({href: '{{ $day_panel['add_url'] }}'})" @endif
                       class="text-primary fw-semibold text-nowrap" title="Новое событие на {{ $day_panel['date'] }}">
                        {{-- в узком блоке — только плюс, подпись в title --}}
                        <i class="fa-light fa-plus text-primary"></i><span class="desk-hide-narrow"> Новое событие</span>
                    </a>
                </div>
            </div>
        @endif
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
                        {{-- dot(): bg-secondary на светлом фоне не видно — как в сетке и панели дня --}}
                        <span class="bullet bullet-dot flex-shrink-0 {{ $widget::dot($row['color']) }}"></span>
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
