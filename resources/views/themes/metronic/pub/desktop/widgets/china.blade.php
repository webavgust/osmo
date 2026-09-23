{{-- Виджет «Китай: сводка» (patch v30): App\Modules\Pub\Desktop\Widgets\Analytics\ChinaWidget --}}
@php
    // узкий блок — плитки столбиком (что не влезло, прячет подгон); шире — плитки в ряд
    // (их число растёт с шириной), под ними пометки, в высоком блоке — таблица разреза
    $narrow = $dw === 'xs';
    $button = $settings['button'] && in_array($dh, ['md', 'lg', 'xl'], true);
    $table = !$narrow && in_array($dh, ['lg', 'xl'], true);
    $list = $table ? array_slice($data['rows'], 0, $rows_max) : [];
    $total = max(1.0, (float) $data['amount']);
    $amount_full = $widget::money($data['amount'], $data['symbol'], false);
    $licenses_full = number_format($data['licenses'], 0, ',', ' ');
    $hint = 'Файл отчёта собирается на самой странице: там выбирают ключи, валюту и курсы';
@endphp
@if(empty($data['keys']))
    <div class="desk-empty">
        <i class="fa-light fa-earth-asia"></i> Действующих ключей нет
    </div>
@elseif($narrow)
    <div class="desk-stack">
        <div class="desk-stack-grow desk-fit an-column">
            @foreach([
                ['ключей', $data['keys'], null, null],
                ['сумма, ' . $data['symbol'], $widget::compact($data['amount']), $amount_full, null],
                ['лицензий', $widget::compact($data['licenses']), $licenses_full, null],
                ['компаний', $data['companies'], null, null],
                ['стран', $data['countries'], null, null],
                ['истекают', $data['expiring'], 'Ключи, до конца которых осталось меньше трёх месяцев', 'text-warning'],
            ] as [$label, $value, $full, $color])
                @continue($color && empty($value))
                <div>
                    <div class="desk-label desk-nowrap" title="{{ $label }}">{{ $label }}</div>
                    <div @class(['desk-value desk-value-sm', $color]) @if($full) title="{{ $full }}" @endif>{{ $value }}</div>
                </div>
            @endforeach
        </div>
        @if($button)
            <a class="btn btn-sm btn-light-primary w-100 px-0 china-btn" href="{{ $preview ? 'javascript:void(0)' : $data['url'] }}" title="{{ $hint }}">
                <i class="fa-light fa-file-excel p-0"></i>
            </a>
        @endif
    </div>
@else
    <div class="desk-stack">
        <div @class(['china-head', 'desk-stack-grow d-flex flex-column justify-content-center' => !$table])>
            <div class="an-tiles">
                @foreach([
                    ['ключей', $data['keys'], null, null],
                    ['лицензий', $widget::compact($data['licenses']), $licenses_full, 'desk-only-w-md'],
                    ['компаний', $data['companies'], null, 'desk-only-w-lg'],
                    ['стран', $data['countries'], null, 'desk-only-w-xl'],
                ] as [$label, $value, $full, $hide])
                    <div @class(['an-tile', $hide])>
                        <div class="desk-label desk-nowrap" title="{{ $label }}">{{ $label }}</div>
                        <div class="desk-value desk-value-sm" @if($full) title="{{ $full }}" @endif>{{ $value }}</div>
                    </div>
                @endforeach
                <div class="an-tile an-tile-wide">
                    <div class="desk-label desk-nowrap" title="Сумма КП договоров">сумма КП</div>
                    <div class="desk-value desk-value-sm" title="{{ $amount_full }}">{{ $widget::money($data['amount'], $data['symbol']) }}</div>
                </div>
            </div>

            <div class="china-meta desk-only-h-md">
                @if($data['expiring'] > 0)
                    <span class="text-warning" title="Ключи, до конца которых осталось меньше трёх месяцев">истекают: {{ $data['expiring'] }}</span>
                @endif
                <span class="desk-muted desk-only-w-md">
                    {{ $data['partners'] }} {{ \App\Facades\Tools::morph($data['partners'], 'партнёр', 'партнёра', 'партнёров') }}
                </span>
                @if($data['skipped'] > 0)
                    <span class="desk-muted desk-only-w-lg" title="Сумма КП в валюте, для которой нет курса, в итог не вошла">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>
        </div>

        @if($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>{{ $data['scope_label'] }}</th>
                            <th class="num desk-only-w-lg">Ключей</th>
                            <th class="num desk-only-w-md">Лицензий</th>
                            <th class="desk-only-w-xl china-share">Доля</th>
                            <th class="num">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            @php($share = $row['amount'] * 100 / $total)
                            <tr>
                                <td class="desk-cut" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                                <td class="num desk-muted desk-only-w-lg">{{ $row['keys'] }}</td>
                                <td class="num desk-muted desk-only-w-md">{{ $row['licenses'] }}</td>
                                <td class="desk-only-w-xl china-share">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1"><i style="width: {{ round($share, 1) }}%"></i></div>
                                        <span class="desk-muted fs-8 text-nowrap">{{ number_format($share, 1, ',', ' ') }} %</span>
                                    </div>
                                </td>
                                @if($row['amount'] > 0)
                                    <td class="num fw-semibold" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                                        {{ $widget::money($row['amount'], $data['symbol']) }}
                                    </td>
                                @else
                                    {{-- у ключей строки нет КП договора (или сумма договора ушла в строку, где его ключей больше) --}}
                                    <td class="num desk-muted" title="Без суммы: у договора нет КП или его сумма учтена в другой строке">—</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @endif

        @if($button)
            <a class="btn btn-sm btn-light-primary w-100 text-nowrap china-btn" href="{{ $preview ? 'javascript:void(0)' : $data['url'] }}" title="{{ $hint }}">
                <i class="fa-light fa-file-excel"></i>
                <span class="ms-2 desk-only-w-md">Открыть отчёт</span>
                <span class="ms-2 china-btn-short">Отчёт</span>
            </a>
        @endif
    </div>
@endif
