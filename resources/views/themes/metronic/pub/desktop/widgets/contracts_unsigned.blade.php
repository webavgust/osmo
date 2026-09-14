{{-- Виджет «Неподписанные договоры» (patch v30): App\Modules\Pub\Desktop\Widgets\Finance\ContractsUnsignedWidget --}}
@php
    // низкий блок — только счётчик и сумма в строку; выше добавляется список договоров
    // (под список нужно хотя бы 5 строк: шапка с итогами занимает три)
    $compact = in_array($dh, ['xs', 'sm'], true) || $rows < 5;
    // широкий блок — таблица; в остальных список (в самом узком — в две строки, см. finance.css)
    $table = in_array($dw, ['lg', 'xl'], true);
    $limit = max(1, min((int) $settings['limit'], $rows_max));
    $list = $compact ? [] : array_slice($data['rows'], 0, $limit);
    $ages = $data['ages'] ?? null;

    // цвет давности: чем дольше договор висит без подписи, тем тревожнее
    $color = fn($days) => $days === null ? 'secondary' : ($days > 180 ? 'danger' : ($days > 60 ? 'warning' : 'secondary'));
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];

    $hint = fn($row) => $row['partner'] . ' · ' . $row['type'] . ' № ' . $row['number']
        . ' · от ' . ($row['date'] ?? 'без даты')
        . ($row['old'] ? ' · архивный' : '');

    // разбивка по давности: [ключ, подпись, цвет]
    $buckets = [
        ['old', 'дольше 6 мес.', 'danger'],
        ['mid', '2–6 мес.', 'warning'],
        ['fresh', 'до 2 мес.', 'gray-400'],
        ['none', 'без даты', 'gray-300'],
    ];
@endphp
@if($data['count'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-circle-check"></i> Все договоры подписаны
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => empty($list)])>
        <div>
            <div class="desk-label desk-nowrap desk-hide-narrow">без подписи</div>

            {{-- счётчик и сумма в одну строку: что не влезло по ширине, прячет подгон --}}
            <div class="fin-line desk-fit" data-fit-axis="x">
                <span class="desk-value text-warning" title="Договоры без отметки «Подписан»">{{ $data['count'] }}</span>
                <span class="desk-muted" title="Спецификации этих договоров, кроме отменённых: {{ $widget::money($data['amount'], $data['symbol'], false) }}">
                    на {{ $widget::money($data['amount'], $data['symbol']) }}
                </span>
            </div>

            <div class="fin-line desk-fit desk-hide-short mt-1" data-fit-axis="x" data-fit-min="0">
                @if($data['max_days'] > 0)
                    <span class="badge badge-light-warning fs-8" title="Самый старый неподписанный договор">
                        дольше всех: {{ $widget::age($data['max_days']) }}
                    </span>
                @endif
                @if($data['specs'] > 0)
                    <span class="desk-muted fs-8">
                        {{ $data['specs'] }} {{ \App\Facades\Tools::morph($data['specs'], 'спецификация', 'спецификации', 'спецификаций') }}
                    </span>
                @endif
                @if($data['no_date'] > 0)
                    <span class="desk-muted fs-8">без даты: {{ $data['no_date'] }}</span>
                @endif
                @if($data['skipped'] > 0)
                    <span class="desk-muted fs-8">без курса: {{ $data['skipped'] }}</span>
                @endif
            </div>

            {{-- высокий блок: разбивка по давности полоской и подписями --}}
            @if($ages && !$compact)
                <div class="desk-only-h-lg mt-3">
                    <div class="desk-split">
                        @foreach($buckets as [$key, $label, $tone])
                            @if($ages[$key] > 0)
                                <i class="bg-{{ $tone }}" style="width: {{ round($ages[$key] / max(1, array_sum($ages)) * 100, 2) }}%"
                                   title="{{ $label }}: {{ $ages[$key] }}"></i>
                            @endif
                        @endforeach
                    </div>
                    <div class="fin-line desk-fit mt-1" data-fit-axis="x" data-fit-min="0">
                        @foreach($buckets as [$key, $label, $tone])
                            @if($ages[$key] > 0)
                                <span class="fs-8 desk-muted"><span class="bullet bullet-dot bg-{{ $tone }} me-1"></span>{{ $label }}: {{ $ages[$key] }}</span>
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
                            <th>Партнёр</th>
                            <th class="desk-only-w-xl">Договор</th>
                            <th class="num">Дата</th>
                            <th class="num">Давность</th>
                            <th class="num desk-only-w-xl">Спец.</th>
                            <th class="num">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <a href="{{ $href($row) }}" class="desk-link d-block text-truncate fw-semibold text-hover-primary"
                                       title="{{ $hint($row) }}">{{ $row['partner'] }}</a>
                                </td>
                                <td class="desk-muted desk-only-w-xl" title="{{ $row['type'] }} № {{ $row['number'] }}">
                                    <i class="fa-light {{ $row['icon'] }} text-{{ $row['color'] }} me-1"></i>{{ $row['number'] }}
                                </td>
                                <td class="num desk-muted">{{ $row['date'] ?? '—' }}</td>
                                <td class="num text-{{ $color($row['days']) }} fw-semibold">{{ $widget::age($row['days']) }}</td>
                                <td class="num desk-muted desk-only-w-xl">{{ $row['specs'] ?: '—' }}</td>
                                <td class="num fw-bold" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                                    {{ $widget::money($row['amount'], $data['symbol']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @elseif(!empty($list))
            <ul class="desk-list fin-list desk-stack-grow desk-fit" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary"
                           title="{{ $hint($row) }}">{{ $row['partner'] }}</a>
                        <span class="badge badge-light-{{ $color($row['days']) }} flex-shrink-0 desk-only-w-md">
                            {{ $widget::age($row['days']) }}
                        </span>
                        <span class="fw-bold fin-amount" title="{{ $widget::money($row['amount'], $data['symbol'], false) }}">
                            {{ $widget::money($row['amount'], $data['symbol']) }}
                        </span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @endif
    </div>
@endif
