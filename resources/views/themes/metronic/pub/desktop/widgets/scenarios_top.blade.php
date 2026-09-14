{{-- Виджет «Популярные сценарии» (patch v30): App\Modules\Pub\Desktop\Widgets\Analytics\ScenariosTopWidget --}}
@php
    // узкий блок — итоги столбиком; шире — список (в широком — таблица), над ним строка итога
    // или плитки итогов в высоком блоке; если все сценарии влезают с запасом — строки подробные
    $narrow = $dw === 'xs';
    // в блоке высотой в две ячейки шапка таблицы съедает единственную строку — там список
    $table = in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);
    $tiles = in_array($dh, ['lg', 'xl'], true);
    $line = $dh === 'md';
    $list = array_slice($data['rows'], 0, $rows_max);
    $free = $rows - ($tiles ? 3 : ($line ? 1 : 0));
    $detail = $tiles && count($list) * 2.5 <= $free;
    $top = max(1, (int) ($data['rows'][0]['uses'] ?? 1));
    $total_title = number_format($data['total'], 0, ',', ' ') . ' '
        . \App\Facades\Tools::morph($data['total'], 'упоминание', 'упоминания', 'упоминаний') . ' '
        . $data['source_label'] . ' ' . $data['scope_label'];
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-list-ol"></i> Сценарии {{ $data['scope_label'] }} не встречаются
    </div>
@elseif($narrow)
    <div class="desk-fit an-column h-100">
        <div>
            <div class="desk-label desk-nowrap" title="{{ $total_title }}">всего</div>
            <div class="desk-value desk-value-sm" title="{{ $total_title }}">{{ $widget::compact($data['total']) }}</div>
        </div>
        <div>
            <div class="desk-label desk-nowrap" title="Разных сценариев">сценариев</div>
            <div class="desk-value desk-value-sm">{{ $data['scenarios'] }}</div>
        </div>
        @if($data['has_units'])
            <div>
                <div class="desk-label desk-nowrap" title="Лицензий в строках сценариев">лицензий</div>
                <div class="desk-value desk-value-sm" title="{{ number_format((int) $data['units'], 0, ',', ' ') }}">{{ $widget::compact($data['units']) }}</div>
            </div>
        @endif
    </div>
@else
    <div class="desk-stack">
        @if($tiles)
            <div>
                <div class="an-tiles">
                    <div class="an-tile">
                        <div class="desk-label desk-nowrap" title="{{ $total_title }}">упоминаний</div>
                        <div class="desk-value desk-value-sm" title="{{ $total_title }}">{{ $widget::compact($data['total']) }}</div>
                    </div>
                    <div class="an-tile">
                        <div class="desk-label desk-nowrap" title="Разных сценариев">сценариев</div>
                        <div class="desk-value desk-value-sm">{{ $data['scenarios'] }}</div>
                    </div>
                    @if($data['has_units'])
                        <div class="an-tile desk-only-w-md">
                            <div class="desk-label desk-nowrap" title="Лицензий в строках сценариев">лицензий</div>
                            <div class="desk-value desk-value-sm" title="{{ number_format((int) $data['units'], 0, ',', ' ') }}">{{ $widget::compact($data['units']) }}</div>
                        </div>
                    @endif
                </div>
                <div class="desk-muted fs-8 desk-nowrap" title="{{ $total_title }}">{{ $data['source_label'] }} {{ $data['scope_label'] }}</div>
            </div>
        @elseif($line)
            <div class="st-line desk-muted fs-8" title="{{ $total_title }}">
                <span>{{ number_format($data['total'], 0, ',', ' ') }} {{ \App\Facades\Tools::morph($data['total'], 'упоминание', 'упоминания', 'упоминаний') }} {{ $data['source_label'] }}</span>
                <span class="desk-only-w-md">· {{ $data['scenarios'] }} {{ \App\Facades\Tools::morph($data['scenarios'], 'сценарий', 'сценария', 'сценариев') }}</span>
                <span class="desk-only-w-lg">· {{ $data['scope_label'] }}</span>
            </div>
        @endif

        @if($detail)
            <ul class="desk-list desk-stack-grow desk-fit st-detail">
                @foreach($list as $row)
                    <li>
                        <span class="fw-bold st-place">{{ $row['place'] }}</span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2">
                                <span class="desk-grow fw-semibold" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                                <span class="badge badge-light-primary flex-shrink-0"
                                      title="{{ $row['uses'] }} {{ \App\Facades\Tools::morph($row['uses'], 'упоминание', 'упоминания', 'упоминаний') }} {{ $data['source_label'] }}">{{ $row['uses'] }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <div class="desk-bar desk-bar-fill"><i style="width: {{ round($row['uses'] * 100 / $top, 1) }}%"></i></div>
                                <span class="desk-muted fs-8 text-nowrap">{{ number_format($row['share'], 1, ',', ' ') }} %</span>
                            </div>
                            <div class="d-flex gap-3 desk-muted fs-8 mt-1">
                                <span class="desk-nowrap" title="{{ $row['group'] }}">{{ $row['group'] ?: 'без группы' }}</span>
                                @if($data['has_units'])
                                    <span class="text-nowrap ms-auto desk-only-w-md" title="{{ number_format((int) $row['units'], 0, ',', ' ') }}">
                                        {{ $widget::compact($row['units']) }} {{ \App\Facades\Tools::morph((int) $row['units'], 'лицензия', 'лицензии', 'лицензий') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @elseif($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th class="num">#</th>
                            <th>Сценарий</th>
                            <th class="desk-only-w-xl">Группа</th>
                            @if($data['has_units'])
                                <th class="num desk-only-w-xl">Лицензий</th>
                            @endif
                            <th class="num">Доля</th>
                            <th class="num">Всего</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="num fw-bold">{{ $row['place'] }}</td>
                                <td class="desk-cut" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                                <td class="desk-muted desk-only-w-xl st-group" title="{{ $row['group'] }}">{{ $row['group'] ?: '—' }}</td>
                                @if($data['has_units'])
                                    <td class="num desk-muted desk-only-w-xl" title="{{ number_format((int) $row['units'], 0, ',', ' ') }}">
                                        {{ $widget::compact($row['units']) }}
                                    </td>
                                @endif
                                <td class="num desk-muted">{{ number_format($row['share'], 1, ',', ' ') }} %</td>
                                <td class="num">
                                    <span class="badge badge-light-primary">{{ $row['uses'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    <li>
                        <span class="fw-bold w-20px text-center flex-shrink-0 desk-hide-narrow">{{ $row['place'] }}</span>
                        <span class="desk-grow" title="{{ $row['name'] }}{{ $row['group'] ? ' · ' . $row['group'] : '' }}">{{ $row['name'] }}</span>
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-md">{{ number_format($row['share'], 1, ',', ' ') }} %</span>
                        <span class="badge badge-light-primary flex-shrink-0"
                              title="{{ $row['uses'] }} {{ \App\Facades\Tools::morph($row['uses'], 'упоминание', 'упоминания', 'упоминаний') }} {{ $data['source_label'] }}">{{ $row['uses'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-hide-short" data-fit-more="и ещё {n}"></div>
        @endif
    </div>
@endif
