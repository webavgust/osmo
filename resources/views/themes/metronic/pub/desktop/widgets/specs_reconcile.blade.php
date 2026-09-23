{{-- Виджет «Сверка спецификаций» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\SpecsReconcileWidget --}}
@php
    // низкий блок — только счётчик и итоги; выше добавляется список спецификаций (шапка с итогами — три строки)
    $compact = in_array($dh, ['xs', 'sm'], true) || $rows < 5;
    // широкий блок — таблица; в остальных список (в самом узком — в две строки, см. finance.css)
    $table = in_array($dw, ['lg', 'xl'], true);
    // строк с запасом: лишние спрячет подгон .desk-fit
    $limit = max(1, min((int) $settings['limit'], $rows_max));
    $list = $compact ? [] : array_slice($data['rows'], 0, $limit);

    $color = fn($row) => $row['hard'] ? 'danger' : 'warning';
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $diff = fn($row) => $row['diff'] === null ? '—' : ($row['diff'] > 0 ? '+' : ($row['diff'] < 0 ? '−' : '')) . $widget::money(abs($row['diff']), $data['symbol']);
    // расхождение с КП (платежи сходятся) помечаем, чтобы не путать с расхождением платежей
    $kp = fn($row) => ($row['source'] ?? 'payments') === 'kp';

    // подсказка строки: все причины расхождения, как в поповере на карточке компании
    $hint = fn($row) => $row['company'] . ' · ' . $row['spec']
        . ($row['partner'] ? ' · ' . $row['partner'] : '')
        . "\n" . implode("\n", $row['reasons']);

    // итог сверки долями: жёсткие, мягкие, остальные проверенные
    $hard_only = ($settings['level'] ?? 'any') === 'hard';
    $shares = [
        ['жёсткие', $data['hard'], 'danger'],
        ['мягкие', $data['count'] - $data['hard'], 'warning'],
        [$hard_only ? 'остальные' : 'сходятся', max(0, $data['checked'] - $data['count']), 'gray-300'],
    ];
@endphp
@if($data['count'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-circle-check"></i> <span class="desk-hide-narrow">Спецификации сходятся</span>
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list)])>
        <div>
            <div class="desk-label desk-nowrap desk-hide-narrow">с расхождением</div>

            {{-- счётчик и сумма в одну строку: что не влезло по ширине, прячет подгон --}}
            <div class="fin-line desk-fit" data-fit-axis="x">
                <span class="desk-value text-{{ $data['hard'] > 0 ? 'danger' : 'warning' }}" title="Проверено спецификаций: {{ $data['checked'] }}">
                    {{ $data['count'] }}
                </span>
                <span class="desk-muted" title="Расхождения платежей со спецификациями по модулю (расхождения с КП не суммируются): {{ $widget::money($data['diff'], $data['symbol'], false) }}">
                    на {{ $widget::money($data['diff'], $data['symbol']) }}
                </span>
            </div>

            <div class="fin-line desk-fit desk-hide-short mt-1" data-fit-axis="x" data-fit-min="0">
                @if($data['hard'] > 0)
                    <span class="badge badge-light-danger fs-8" title="Расхождение больше допустимой доли от суммы спецификации">
                        жёстких: {{ $data['hard'] }}
                    </span>
                @endif
                <span class="desk-muted fs-8">проверено: {{ $data['checked'] }}</span>
                @if($data['skipped'] > 0)
                    <span class="desk-muted fs-8">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>

            {{-- высокий блок: итог сверки полоской и подписями --}}
            @if(!$compact && $data['checked'] > 0)
                <div class="desk-only-h-lg mt-3">
                    <div class="desk-split">
                        @foreach($shares as [$label, $value, $tone])
                            @if($value > 0)
                                <i class="bg-{{ $tone }}" style="width: {{ round($value / max(1, $data['checked']) * 100, 2) }}%" title="{{ $label }}: {{ $value }}"></i>
                            @endif
                        @endforeach
                    </div>
                    <div class="fin-line desk-fit mt-1" data-fit-axis="x" data-fit-min="0">
                        @foreach($shares as [$label, $value, $tone])
                            @if($value > 0)
                                <span class="fs-8 desk-muted"><span class="bullet bullet-dot bg-{{ $tone }} me-1"></span>{{ $label }}: {{ $value }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if(!empty($list) && $table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Компания</th>
                            <th class="desk-only-w-xl">Спецификация</th>
                            <th class="num">Сумма</th>
                            <th class="num desk-only-w-xl">Платежи</th>
                            <th class="num">Расхождение</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <a href="{{ $href($row) }}" class="desk-link d-block text-truncate fw-semibold text-hover-primary"
                                       title="{{ $hint($row) }}">{{ $row['company'] }}</a>
                                </td>
                                <td class="desk-cut desk-muted desk-only-w-xl" title="{{ $row['spec'] }}">{{ $row['spec'] }}</td>
                                <td class="num desk-muted" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                                    {{ $widget::money($row['amount'], $data['symbol']) }}
                                </td>
                                <td class="num desk-muted desk-only-w-xl" title="{{ $widget::money($row['payments'], $data['symbol'], false) }}">
                                    {{ $widget::money($row['payments'], $data['symbol']) }}
                                </td>
                                <td class="num fw-bold text-{{ $color($row) }}" title="{{ $row['reason'] }}">@if($kp($row))<span class="desk-muted fw-normal me-1">КП</span>@endif{{ $diff($row) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}" data-fit-extra="{{ max(0, $data['count'] - count($list)) }}"></div>
        @elseif(!empty($list))
            <ul class="desk-list fin-list desk-stack-grow desk-fit" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary"
                           title="{{ $hint($row) }}">{{ $row['company'] }}</a>
                        <span class="badge badge-light-{{ $color($row) }} flex-shrink-0 desk-only-w-md">
                            {{ $row['hard'] ? 'жёсткое' : 'расхождение' }}
                        </span>
                        <span class="fw-bold fin-amount text-{{ $color($row) }}" title="{{ $row['reason'] }}">@if($kp($row))<span class="desk-muted fw-normal me-1">КП</span>@endif{{ $diff($row) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}" data-fit-extra="{{ max(0, $data['count'] - count($list)) }}"></div>
        @endif
    </div>
@endif
