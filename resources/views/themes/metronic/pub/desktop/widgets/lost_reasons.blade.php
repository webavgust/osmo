{{-- Виджет «Причины проигрыша» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\LostReasonsWidget --}}
@php
    $percent = fn($value) => number_format((float) $value, $value < 10 ? 1 : 0, ',', ' ') . ' %';
    $sums = (bool) $settings['show_sum'];

    // высота 1–2 ячейки — только итог; с 3 — разбивка: при ширине ≥6 сбоку от итога, уже — под ним;
    // широкий блок — таблица; высокий — кольцо (если включено) или строки с пояснением причины
    $split = !in_array($dh, ['xs', 'sm'], true);
    $tall = $dh === 'xl' || ($dh === 'lg' && !in_array($dw, ['xs', 'sm'], true));
    $chart = $settings['chart'] && in_array($dh, ['lg', 'xl'], true) && $dw !== 'xs';
    $side = $split && $dh === 'md' && !in_array($dw, ['xs', 'sm'], true);
    $table = $split && !$chart && in_array($dw, ['lg', 'xl'], true);
    $list = $split && !$chart ? array_slice($data['rows'], 0, $rows_max) : [];
    $value_class = $dh === 'xl' && $dw !== 'xs' ? 'desk-value-lg' : 'desk-value';

    $money_full = fn($amount) => $widget::money($amount, $data['symbol'], false);
    $title = fn($row) => $row['label'] . ' · ' . $row['count'] . ' из ' . $data['total']
        . ' (' . $percent($row['share']) . ')'
        . ($sums ? ' · ' . $money_full($row['amount']) : '')
        . ($row['hint'] !== '' ? ' · ' . $row['hint'] : '');
    $total_title = 'Проиграно КП: ' . $data['total']
        . ($data['top'] !== '' ? ' · чаще всего: ' . $data['top'] : '')
        . ($sums ? ' · всего ' . $money_full($data['amount']) : '');
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-thumbs-down"></i> Проигранных КП нет{{ $data['scope_label'] === 'за всё время' ? '' : ': ' . $data['label'] }}
    </div>
@else
    <div @class(['desk-stack', 'desk-lost-side' => $side])>
        <div @class(['desk-center' => !$split || $side, 'desk-lost-head'])>
            <div class="desk-label desk-nowrap" title="Проиграно: {{ $data['label'] }}">
                проиграно@unless($side)<span class="desk-only-w-md">: {{ $data['label'] }}</span>@endunless
            </div>
            <div class="d-flex align-items-baseline column-gap-3 min-w-0">
                <div class="{{ $value_class }} flex-shrink-0" title="{{ $total_title }}">{{ $data['total'] }} КП</div>
                @unless($split)
                    {{-- низкий блок: сумма и главная причина в строку с числом, если хватает ширины --}}
                    @if($sums)
                        <span class="desk-muted text-nowrap flex-shrink-0 desk-only-w-lg" title="{{ $money_full($data['amount']) }}">{{ $widget::money($data['amount'], $data['symbol']) }}</span>
                    @endif
                    @if($data['top'] !== '')
                        <span class="desk-muted desk-nowrap desk-only-w-xl" title="Чаще всего: {{ $data['top'] }}">чаще всего: {{ $data['top'] }}</span>
                    @endif
                @endunless
            </div>
            @if($split && $sums)
                <div class="desk-muted text-nowrap desk-hide-short" title="Сумма проигранных КП: {{ $money_full($data['amount']) }}">
                    {{ $widget::money($data['amount'], $data['symbol']) }}
                </div>
            @endif
        </div>

        @if($chart)
            <div class="desk-stack-grow">
                {!! $widget::chart([
                    'type' => 'donut',
                    'legend' => true,
                    'labels' => array_column($data['rows'], 'label'),
                    'colors' => array_column($data['rows'], 'chart_color'),
                    'series' => array_map('intval', array_column($data['rows'], 'count')),
                ]) !!}
            </div>
        @elseif($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Причина</th>
                            @if($tall)
                                <th class="desk-only-w-xl">Пояснение</th>
                            @endif
                            <th class="num">КП</th>
                            <th @class(['desk-only-w-lg' => !$side, 'desk-only-w-xl' => $side]) style="width: 28%;">Доля</th>
                            @if($sums)
                                <th class="num">Сумма</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <span class="d-flex align-items-center gap-1 fw-semibold" title="{{ $title($row) }}">
                                        <i class="fa-light fa-circle-small text-{{ $row['color'] }} flex-shrink-0"></i><span class="desk-nowrap">{{ $row['label'] }}</span>
                                    </span>
                                </td>
                                @if($tall)
                                    <td class="desk-cut desk-muted desk-only-w-xl" title="{{ $row['hint'] }}">
                                        <span class="d-block text-truncate">{{ $row['hint'] }}</span>
                                    </td>
                                @endif
                                <td class="num fw-bold">{{ $row['count'] }}</td>
                                <td @class(['desk-only-w-lg' => !$side, 'desk-only-w-xl' => $side])>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1">
                                            <i class="bg-{{ $row['color'] }}" style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                                        </div>
                                        <span class="desk-muted text-end text-nowrap" style="min-width: 3.2em;">{{ $percent($row['share']) }}</span>
                                    </div>
                                </td>
                                @if($sums)
                                    <td class="num desk-muted" title="{{ $money_full($row['amount']) }}">
                                        {{ $widget::money($row['amount'], $data['symbol']) }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif(!empty($list))
            <ul class="desk-list desk-stack-grow desk-fit desk-lost-list" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <div class="desk-grow">
                            <span class="d-block text-truncate" title="{{ $title($row) }}">{{ $row['label'] }}</span>
                            @if($tall && $row['hint'] !== '')
                                <span class="d-block text-truncate desk-muted fs-8" title="{{ $row['hint'] }}">{{ $row['hint'] }}</span>
                            @endif
                        </div>

                        <div class="desk-bar flex-shrink-0 desk-only-w-md" style="width: 3.5rem;" title="{{ $percent($row['share']) }}">
                            <i class="bg-{{ $row['color'] }}" style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                        </div>

                        @if($sums)
                            <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-lg" title="{{ $money_full($row['amount']) }}">
                                {{ $widget::money($row['amount'], $data['symbol']) }}
                            </span>
                        @endif

                        <span class="badge badge-light-{{ $row['color'] }} flex-shrink-0" title="{{ $title($row) }}">{{ $row['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
