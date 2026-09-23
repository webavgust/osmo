{{-- Виджет «Сценарии по спецификациям» (patch v30): App\Modules\Pub\Desktop\Widgets\Analytics\ScenariosSpecsWidget --}}
@php
    // узкий блок — итоги столбиком; шире — список (в широком и не низком блоке — таблица отчёта),
    // над ним строка итога или, в высоком блоке, плитки итогов; строки — до $rows_max в .desk-fit
    $by_scenario = $data['view'] === 'scenarios';
    $narrow = $dw === 'xs';
    $table = in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);
    $tiles = in_array($dh, ['lg', 'xl'], true);
    $line = $dh === 'md';
    $list = array_slice($data['rows'], 0, $rows_max);
    $top = max(1, (int) $data['companies']);
    $counters = [
        ['клиентов', $data['companies'], null],
        ['сценариев', $data['scenarios'], null],
        ['спецификаций', $data['specs'], 'desk-only-w-md'],
        ['партнёров', $data['partners'], 'desk-only-w-lg'],
        ['строк отчёта', $data['total'], 'desk-only-w-xl'],
    ];
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-grid-2-plus"></i> Сценариев по спецификациям нет
    </div>
@elseif($narrow)
    <div class="desk-fit an-column h-100">
        @foreach($counters as [$label, $value])
            <div>
                <div class="desk-label desk-nowrap" title="{{ $label }}">{{ $label }}</div>
                <div class="desk-value desk-value-sm">{{ $value }}</div>
            </div>
        @endforeach
    </div>
@else
    <div class="desk-stack">
        @if($tiles)
            <div class="an-tiles">
                @foreach($counters as [$label, $value, $hide])
                    <div @class(['an-tile', $hide])>
                        <div class="desk-label desk-nowrap" title="{{ $label }}">{{ $label }}</div>
                        <div class="desk-value desk-value-sm">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        @elseif($line)
            <div class="sp-line desk-muted fs-8">
                <span>{{ $data['companies'] }} {{ \App\Facades\Tools::morph($data['companies'], 'клиент', 'клиента', 'клиентов') }}</span>
                <span class="desk-only-w-md">· {{ $data['specs'] }} {{ \App\Facades\Tools::morph($data['specs'], 'спецификация', 'спецификации', 'спецификаций') }}</span>
                <span class="desk-only-w-lg">· {{ $data['scenarios'] }} {{ \App\Facades\Tools::morph($data['scenarios'], 'сценарий', 'сценария', 'сценариев') }}</span>
                <span class="desk-only-w-xl">· всего {{ $data['total'] }} {{ \App\Facades\Tools::morph($data['total'], 'строка', 'строки', 'строк') }}</span>
            </div>
        @endif

        @if($table && $by_scenario)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th class="num">#</th>
                            <th>Сценарий</th>
                            <th class="desk-only-w-xl sp-share">Доля клиентов</th>
                            <th class="num">Спецификаций</th>
                            <th class="num">Клиентов</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $i => $row)
                            <tr>
                                <td class="num fw-bold">{{ $i + 1 }}</td>
                                <td class="desk-cut" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                                <td class="desk-only-w-xl sp-share">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1"><i style="width: {{ round($row['companies'] * 100 / $top, 1) }}%"></i></div>
                                        <span class="desk-muted fs-8 text-nowrap">{{ round($row['companies'] * 100 / $top) }} %</span>
                                    </div>
                                </td>
                                <td class="num desk-muted">{{ $row['specs'] }}</td>
                                <td class="num"><span class="badge badge-light-primary">{{ $row['companies'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @elseif($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th class="desk-only-w-xl sp-c-partner">Партнёр</th>
                            <th class="sp-c-company">Клиент</th>
                            <th class="sp-c-spec">Спецификация</th>
                            <th>Сценарий</th>
                            @if($settings['neuro'])
                                <th class="desk-only-w-xl sp-c-neuro">Нейросервис</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut desk-muted desk-only-w-xl sp-c-partner" title="{{ $row['partner'] }}">{{ $row['partner'] ?: '—' }}</td>
                                <td class="desk-cut sp-c-company" title="{{ $row['company'] }}">{{ $row['company'] ?: '—' }}</td>
                                <td class="desk-cut desk-muted sp-c-spec" title="{{ $row['spec'] }}">{{ $row['spec'] ?: '—' }}</td>
                                <td class="desk-cut" title="{{ $row['scenario'] }}">{{ $row['scenario'] ?: '—' }}</td>
                                @if($settings['neuro'])
                                    <td class="desk-cut desk-muted desk-only-w-xl sp-c-neuro" title="{{ $row['neuro'] }}">{{ $row['neuro'] ?: '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    @if($by_scenario)
                        <li>
                            <span class="desk-grow" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                            <span class="desk-muted fs-8 text-nowrap desk-only-w-md">
                                {{ $row['specs'] }} {{ \App\Facades\Tools::morph($row['specs'], 'спецификация', 'спецификации', 'спецификаций') }}
                            </span>
                            <span class="badge badge-light-primary flex-shrink-0"
                                  title="{{ $row['companies'] }} {{ \App\Facades\Tools::morph($row['companies'], 'клиент', 'клиента', 'клиентов') }}">{{ $row['companies'] }}</span>
                        </li>
                    @else
                        <li>
                            <span class="desk-grow" title="{{ $row['company'] }} · {{ $row['spec'] }} · {{ $row['scenario'] }}">{{ $row['scenario'] ?: '—' }}</span>
                            <span class="desk-muted fs-8 desk-nowrap desk-only-w-md sp-company" title="{{ $row['company'] }} · {{ $row['spec'] }}">{{ $row['company'] }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-hide-short" data-fit-more="и ещё {n}"></div>
        @endif
    </div>
@endif
