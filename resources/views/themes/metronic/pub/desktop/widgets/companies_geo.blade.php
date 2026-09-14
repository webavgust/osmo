{{-- Виджет «Компании по странам» (patch v30): App\Modules\Pub\Desktop\Widgets\Partner\CompaniesGeoWidget --}}
@php
    // широкий и не низкий блок — таблица с долей; иначе список «название + значение»
    $table = in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);

    // итог и полоска долей сверху — на высоком блоке; в узкой колонке сумма крупным кеглем не влезает
    $summary = $dw !== 'xs' && in_array($dh, ['lg', 'xl'], true);

    // строк не больше 30 (предел настройки «Сколько строк»), лишние спрячет .desk-fit
    $list = $data['rows'];

    // первые пять долей — цветом в полоске и точкой у строки, остальное серым
    $colors = ['primary', 'success', 'warning', 'info', 'danger'];
    $color = fn($i) => $colors[$i] ?? 'gray-400';
    $top_share = array_sum(array_column(array_slice($list, 0, count($colors)), 'share'));

    // в колонке шириной 2 сумма с символом валюты не влезает — только число, полная сумма в title
    $value = fn($row) => $data['money']
        ? ($dw === 'xs' ? $widget::compact($row['value']) : $widget::money($row['value'], $data['symbol']))
        : $row['count'];
    $value_full = fn($row) => $data['money']
        ? $widget::money($row['value'], $data['symbol'], false) . ' · компаний: ' . $row['count']
        : $row['count'] . ' ' . \App\Facades\Tools::morph($row['count'], 'компания', 'компании', 'компаний');
    $percent = fn($share) => number_format($share, $share < 10 ? 1 : 0, ',', ' ') . ' %';

    $groups = count($list) + $data['rest'];
    $group_word = match ($data['by']) {
        'sector' => \App\Facades\Tools::morph($groups, 'сектор', 'сектора', 'секторов'),
        'partner' => \App\Facades\Tools::morph($groups, 'партнёр', 'партнёра', 'партнёров'),
        default => \App\Facades\Tools::morph($groups, 'страна', 'страны', 'стран'),
    };
    $total_short = $data['money'] ? $widget::money($data['total'], $data['symbol']) : $data['total_count'];
    $total_full = $data['money']
        ? $widget::money($data['total'], $data['symbol'], false) . ' · компаний: ' . $data['total_count']
        : $data['total_count'] . ' ' . \App\Facades\Tools::morph($data['total_count'], 'компания', 'компании', 'компаний');
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-earth-europe"></i> Компаний нет
    </div>
@else
    <div class="desk-stack">
        @if($summary)
            <div>
                <div class="d-flex flex-wrap align-items-baseline column-gap-2">
                    <span class="desk-value-sm fw-bold text-nowrap" title="{{ $total_full }}">{{ $total_short }}</span>
                    <span class="desk-label text-nowrap">
                        {{ $data['money'] ? 'компаний ' . $data['total_count'] . ' · ' : \App\Facades\Tools::morph($data['total_count'], 'компания', 'компании', 'компаний') . ' · ' }}{{ $groups }} {{ $group_word }}
                    </span>
                </div>
                <div class="desk-split mt-2" title="Доли первых {{ min(count($list), count($colors)) }} строк разреза">
                    @foreach(array_slice($list, 0, count($colors)) as $i => $row)
                        <div class="bg-{{ $color($i) }}" style="width: {{ max(0, min(100, $row['share'])) }}%;" title="{{ $row['name'] }} · {{ $percent($row['share']) }}"></div>
                    @endforeach
                    <div class="bg-gray-400" style="width: {{ max(0, 100 - $top_share) }}%;" title="Остальные · {{ $percent(max(0, 100 - $top_share)) }}"></div>
                </div>
            </div>
        @endif

        @if($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>{{ $data['dim_label'] }}</th>
                            <th class="num">{{ $data['metric_label'] }}</th>
                            <th class="desk-only-w-lg" style="width: 34%;">Доля</th>
                            @if($data['money'])
                                <th class="num desk-only-w-xl">Компаний</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $i => $row)
                            <tr>
                                <td class="desk-cut">
                                    <span class="d-block text-truncate fw-semibold" title="{{ $row['name'] }}">@if($summary)<span class="bullet bullet-dot bg-{{ $color($i) }} me-2"></span>@endif{{ $row['name'] }}</span>
                                </td>
                                <td class="num fw-bold" title="{{ $value_full($row) }}">{{ $value($row) }}</td>
                                <td class="desk-only-w-lg">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1">
                                            @if($row['share'] > 0)<i style="width: {{ min(100, $row['share']) }}%;"></i>@endif
                                        </div>
                                        <span class="desk-muted text-end text-nowrap" style="min-width: 3.2em;">{{ $percent($row['share']) }}</span>
                                    </div>
                                </td>
                                @if($data['money'])
                                    <td class="num desk-muted desk-only-w-xl">{{ $row['count'] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $i => $row)
                    <li class="flex-wrap">
                        @if($summary)
                            <span class="bullet bullet-dot bg-{{ $color($i) }} flex-shrink-0 desk-hide-narrow"></span>
                        @endif
                        <span class="desk-grow" title="{{ $row['name'] }} · {{ $value_full($row) }}">{{ $row['name'] }}</span>
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-md">{{ $percent($row['share']) }}</span>
                        <span class="fw-bold text-nowrap flex-shrink-0" title="{{ $value_full($row) }}">{{ $value($row) }}</span>
                        {{-- полоска под строкой удваивает её высоту — только на высоком блоке --}}
                        <div class="desk-bar w-100 desk-only-h-lg">
                            @if($row['share'] > 0)<i style="width: {{ min(100, $row['share']) }}%;"></i>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>

        @if(!$summary || $data['rest'] || $data['skipped'])
            <div class="d-flex gap-2 align-items-baseline flex-wrap desk-hide-short">
                @unless($summary)
                    {{-- подпись и число — отдельными кусками: в узкой колонке число переносится целиком --}}
                    <span class="desk-muted text-nowrap" title="Итог по всем строкам разреза: {{ $total_full }}">всего:</span>
                    <span class="fw-semibold text-nowrap" title="{{ $total_full }}">{{ $data['money'] && $dw === 'xs' ? $widget::compact($data['total']) : $total_short }}</span>
                @endunless
                @if($data['rest'])
                    <span class="desk-muted fs-8 text-nowrap desk-only-w-md" title="Строки, не поместившиеся в отбор «Сколько строк»: компаний {{ $data['rest_count'] }}">
                        вне отбора: {{ $data['rest'] }}
                    </span>
                @endif
                @if($data['skipped'])
                    <span class="desk-muted fs-8 text-nowrap desk-only-w-md" title="Спецификации, для которых нет курса на дату">
                        без курса: {{ $data['skipped'] }}
                    </span>
                @endif
            </div>
        @endif
    </div>
@endif
