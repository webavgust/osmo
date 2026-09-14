{{-- Виджет «Скидки» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\DiscountsWidget --}}
@php
    $percent = fn($value) => $value === null ? '—' : number_format((float) $value, 1, ',', ' ') . ' %';

    // высота ≥3 ячеек — разбивка; высота ≥6 — плитки показателей и подробные строки;
    // широкий блок — таблица вместо списка
    $split = !empty($data['rows']) && !in_array($dh, ['xs', 'sm'], true);
    $tall = $dh === 'xl' || ($dh === 'lg' && !in_array($dw, ['xs', 'sm'], true));
    $table = $split && in_array($dw, ['lg', 'xl'], true);
    $list = $split ? array_slice($data['rows'], 0, $rows_max) : [];
    $value_class = $dh === 'xl' && $dw !== 'xs' ? 'desk-value-lg' : 'desk-value';
    // невысокий блок шириной ≥6: показатели слева, разбивка справа
    $side = $split && $dh === 'md' && !in_array($dw, ['xs', 'sm'], true);

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $title = fn($row) => $row['label'] . ' · скидка ' . $percent($row['value'])
        . ($row['count'] !== null ? ' · ' . $row['count'] . ' КП' : '')
        . ($row['sub'] !== '' ? ' · ' . $row['sub'] : '')
        . ($row['alert_text'] !== '' ? ' · ' . $row['alert_text'] : '');
    $alerts_title = 'КП выше потолка ' . $data['hard_limit'] . ' % или на ' . $data['grade_alert_pp'] . ' п. п. выше среднего по грейду';
    $amount_title = $data['amount'] === null ? '' : 'Скидки заказчику и партнёру вместе: ' . $widget::money($data['amount'], $data['symbol'], false);
@endphp
@if($data['count'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-tags"></i> КП со скидками нет: {{ $data['label'] }}
    </div>
@else
    <div @class(['desk-stack', 'desk-discounts-side' => $side])>
        <div @class(['desk-center' => empty($list) || $side, 'desk-discounts-head'])>
            <div class="desk-label desk-nowrap" title="Средняя скидка: {{ $data['label'] }}">
                {{ $dw === 'xs' ? 'скидка' : 'средняя скидка' }}@unless($side)<span class="desk-only-w-md">: {{ $data['label'] }}</span>@endunless
            </div>
            <div class="{{ $value_class }}" title="Средневзвешенная совокупная скидка по {{ $data['count'] }} КП; потолок {{ $data['hard_limit'] }} %">
                {{ $percent($data['average']) }}
            </div>

            @unless($tall)
                <div class="d-flex column-gap-2 align-items-baseline flex-wrap desk-hide-short">
                    <span class="desk-muted text-nowrap" title="Максимальная скидка: {{ $data['max_label'] ?: 'самая большая совокупная скидка' }}">
                        <span class="desk-hide-narrow">макс.</span> {{ $percent($data['max']) }}
                    </span>
                    @if($data['alerts'] > 0)
                        <span class="badge badge-light-danger" title="{{ $alerts_title }}">
                            <i class="fa-light fa-flag fs-8 me-1"></i>{{ $data['alerts'] }}
                        </span>
                    @endif
                </div>

                @if($data['amount'] !== null)
                    <div class="desk-muted fs-8 desk-only-h-md" title="{{ $amount_title }}">
                        <span @class(['desk-hide-narrow', 'desk-only-w-lg' => $side])>отдано скидками</span>
                        <span class="text-nowrap">{{ $widget::money($data['amount'], $data['symbol']) }}</span>
                    </div>
                @endif
            @endunless
        </div>

        @if($tall)
            {{-- высокий блок: показатели плитками --}}
            <div class="desk-tiles desk-discounts-tiles">
                <div title="Самая большая совокупная скидка: {{ $data['max_label'] }}">
                    <div class="desk-label">макс.</div>
                    <div class="fw-bold text-nowrap">{{ $percent($data['max']) }}</div>
                </div>
                <div title="{{ $alerts_title }}">
                    <div class="desk-label">выделено</div>
                    <div class="fw-bold {{ $data['alerts'] > 0 ? 'text-danger' : '' }}">{{ $data['alerts'] }}</div>
                </div>
                <div title="КП со скидкой: {{ $data['label'] }}">
                    <div class="desk-label">КП</div>
                    <div class="fw-bold">{{ $data['count'] }}</div>
                </div>
                @if($data['amount'] !== null)
                    <div title="{{ $amount_title }}">
                        <div class="desk-label">отдано</div>
                        <div class="fw-bold">{{ $widget::money($data['amount'], $data['symbol']) }}</div>
                    </div>
                @endif
            </div>
        @endif

        @if(!empty($list) && $table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>{{ $data['split'] === 'top' ? 'КП' : 'Грейд' }}</th>
                            <th class="desk-only-w-xl">{{ $data['split'] === 'top' ? 'Партнёр' : 'Описание' }}</th>
                            @if($data['split'] !== 'top')
                                <th class="num">КП</th>
                            @endif
                            <th class="desk-only-w-lg" style="width: 28%;">Скидка</th>
                            <th class="num">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    @if($row['url'] && !$preview)
                                        <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $title($row) }}">{{ $row['label'] }}</a>
                                    @else
                                        <span class="d-block text-truncate fw-semibold" title="{{ $title($row) }}">{{ $row['label'] }}</span>
                                    @endif
                                </td>
                                <td class="desk-muted desk-only-w-xl desk-cut" title="{{ $row['sub'] }}">
                                    <span class="d-block text-truncate">{{ $row['sub'] }}</span>
                                </td>
                                @if($data['split'] !== 'top')
                                    <td class="num desk-muted">{{ $row['count'] ?? '' }}</td>
                                @endif
                                <td class="desk-only-w-lg">
                                    <div class="desk-bar">
                                        <i class="bg-{{ $row['alert'] ? 'danger' : 'primary' }}" style="width: {{ max(0, min(100, $row['value'])) }}%;"></i>
                                    </div>
                                </td>
                                <td class="num fw-bold {{ $row['alert'] ? 'text-danger' : '' }}"
                                    title="{{ $row['alert_text'] ?: $title($row) }}">{{ $percent($row['value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif(!empty($list))
            <ul class="desk-list desk-stack-grow desk-fit desk-discounts-list" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <div class="desk-grow">
                            @if($row['url'] && !$preview)
                                <a href="{{ $href($row) }}" class="desk-link d-block text-truncate text-hover-primary" title="{{ $title($row) }}">{{ $row['label'] }}</a>
                            @else
                                <span class="d-block text-truncate" title="{{ $title($row) }}">{{ $row['label'] }}</span>
                            @endif
                            @if($tall && ($row['sub'] !== '' || $row['count'] !== null))
                                <span class="d-block text-truncate desk-muted fs-8" title="{{ $title($row) }}">
                                    {{ $row['count'] !== null ? $row['count'] . ' КП' : '' }}{{ $row['count'] !== null && $row['sub'] !== '' ? ' · ' : '' }}{{ $row['sub'] }}
                                </span>
                            @endif
                        </div>

                        <div class="desk-bar flex-shrink-0 desk-only-w-md" style="width: 3.5rem;">
                            <i class="bg-{{ $row['alert'] ? 'danger' : 'primary' }}" style="width: {{ max(0, min(100, $row['value'])) }}%;"></i>
                        </div>

                        <span class="badge badge-light-{{ $row['alert'] ? 'danger' : 'primary' }} flex-shrink-0"
                              title="{{ $row['alert_text'] ?: $title($row) }}">{{ $percent($row['value']) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
