{{-- Виджет «Активность партнёра» (patch v30): App\Modules\Pub\Desktop\Widgets\Partner\PartnerActivityWidget --}}
@php
    $series = $data['series'];
    $many = count($series) > 1;

    // график рисуем, когда блок не совсем плоский и не совсем узкий; иначе — счётчики
    $chart = !empty($series) && in_array($dh, ['md', 'lg', 'xl'], true) && $dw !== 'xs';
    // узкая высокая колонка: столбики не читаются — те же числа списком по годам, свежие сверху
    $by_year = !$chart && $dw === 'xs' && in_array($dh, ['lg', 'xl'], true);

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $period = $data['years'] ? reset($data['years']) . '–' . end($data['years']) : '';
    $first = $series[0] ?? null;

    $year_rows = [];
    foreach ($data['years'] as $i => $year) {
        $year_rows[] = [
            'year' => $year,
            'value' => array_sum(array_map(fn($row) => (int) ($row['data'][$i] ?? 0), $series)),
            'title' => $year . ': ' . implode(' · ', array_map(fn($row) => $row['name'] . ' — ' . (int) ($row['data'][$i] ?? 0), $series)),
        ];
    }
    $year_rows = array_reverse($year_rows);
    $year_max = max(1, max(array_column($year_rows, 'value') ?: [0]));
@endphp
@if(empty($series))
    {{-- рядов нет только без выбора, когда ни у кого нет событий за эти годы: сам выбор не обязателен --}}
    <div class="desk-empty" title="{{ $data['metric_label'] }}{{ $period ? ': ' . $period : '' }}">
        <i class="fa-light fa-chart-column"></i> <span class="desk-hide-narrow">{{ $data['metric_label'] }}:</span> нет данных
    </div>
@else
    <div class="desk-stack">
        <div>
            <div class="desk-label desk-nowrap" title="{{ $data['metric_label'] }} по годам{{ $period ? ', ' . $period : '' }}{{ $data['auto'] ? ' · партнёр выбран по наибольшей активности' : '' }}">
                {{-- уже 230 px — только показатель: «Спецификации по годам · сравнение» уходило в многоточие --}}
                {{ $data['metric_label'] }}<span class="desk-only-w-md"> по годам{{ $many ? ' · сравнение' : '' }}</span>
            </div>

            @unless($many)
                <div class="d-flex align-items-baseline gap-2">
                    <span class="desk-value-sm fw-bold text-nowrap flex-shrink-0" title="{{ $first['total'] }} {{ $data['unit'] }} за {{ $period }}">{{ $first['total'] }}</span>
                    <a href="{{ $href($first) }}" class="desk-link desk-grow text-hover-primary" title="{{ $first['name'] }}">{{ $first['name'] }}</a>
                </div>
                <div class="desk-muted fs-8 desk-nowrap desk-hide-short" title="{{ $data['unit'] }} за {{ $period }}">{{ $data['unit'] }} за {{ $period }}</div>
            @endunless
        </div>

        @if($chart)
            <div class="desk-stack-grow">
                {!! $widget::chart([
                    'type' => 'bar',
                    'legend' => $many,
                    'colors' => array_column($series, 'color'),
                    'categories' => array_map('strval', $data['years']),
                    'series' => array_map(fn($row) => ['name' => $row['name'], 'data' => $row['data']], $series),
                ]) !!}
            </div>
        @else
            @if($many)
                <ul @class(['desk-list', 'desk-fit', 'desk-stack-grow' => !$by_year])>
                    @foreach($series as $row)
                        <li>
                            <span class="bullet bullet-dot bg-{{ $row['color'] }} flex-shrink-0 desk-hide-narrow"></span>
                            <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                            <span class="badge badge-light-{{ $row['color'] }} flex-shrink-0" title="{{ $row['total'] }} {{ $data['unit'] }} за {{ $period }}">{{ $row['total'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($by_year)
                {{-- годы делят высоту колонки поровну, шкала под числом заменяет столбик графика --}}
                <ul class="desk-list desk-stack-grow desk-fit pa-years">
                    @foreach($year_rows as $row)
                        <li class="flex-wrap" title="{{ $row['title'] }}">
                            <span class="desk-muted desk-grow">{{ $row['year'] }}</span>
                            <span class="fw-bold text-nowrap flex-shrink-0">{{ $row['value'] }}</span>
                            <div class="desk-bar w-100">
                                @if($row['value'] > 0)<i class="bg-{{ $series[0]['color'] }}" style="width: {{ min(100, $row['value'] / $year_max * 100) }}%;"></i>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
            @endif
        @endif

        @if($many && $chart)
            <div class="d-flex gap-2 align-items-baseline flex-wrap desk-hide-short">
                @foreach($series as $row)
                    <span class="desk-muted fs-8 text-nowrap" title="{{ $row['name'] }}: {{ $row['total'] }} {{ $data['unit'] }} за {{ $period }}">
                        <span class="bullet bullet-dot bg-{{ $row['color'] }}"></span> {{ $row['total'] }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
@endif
