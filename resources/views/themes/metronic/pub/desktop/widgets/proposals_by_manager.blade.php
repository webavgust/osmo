{{-- Виджет «КП по менеджерам» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ProposalsByManagerWidget --}}
@php
    $metric = (string) $data['metric'];
    $money = $metric === 'amount';

    // низкий блок — строка лидеров; выше: широкий — таблица со статусами, узкий — список с полоской
    $line = in_array($dh, ['xs', 'sm'], true);
    $table = in_array($dw, ['lg', 'xl'], true);
    // строк с запасом, лишние прячет подгон .desk-fit; в строке — по ширине: сколько имён уместится
    $list = array_slice($data['rows'], 0, $rows_max);
    $leaders = array_slice($data['rows'], 0, match ($dw) { 'xs', 'sm' => 1, 'md' => 3, default => 8 });

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $value = fn($row) => $money ? $widget::money($row['amount'], $data['symbol']) : $widget::compact($row['value']);
    $value_full = fn($row) => $money
        ? $widget::money($row['amount'], $data['symbol'], false)
        : (string) (int) $row['value'];

    $unit = match ($metric) {
        'amount' => 'сумма КП',
        'won' => 'выиграно, шт.',
        default => 'КП, шт.',
    };

    $title = fn($row) => $row['name'] . ' · КП: ' . $row['count']
        . ' · в работе: ' . $row['in_work'] . ' · выиграно: ' . $row['won'] . ' · проиграно: ' . $row['lost']
        . ($money ? ' · сумма: ' . $widget::money($row['amount'], $data['symbol'], false) : '');
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-users-line"></i> КП за период нет
    </div>
@elseif($line && $dw === 'xs')
    {{-- две колонки: лидер и его число --}}
    <div class="desk-center" title="{{ $title($leaders[0]) }}">
        <div class="desk-label desk-nowrap">{{ $leaders[0]['name'] }}</div>
        <div class="desk-value desk-value-sm">{{ $value($leaders[0]) }}</div>
    </div>
@elseif($line)
    {{-- низкий блок: лидеры в строку, не влезшие по ширине прячет подгон --}}
    <div class="desk-center">
        <div class="desk-fit d-flex align-items-baseline gap-4 min-w-0" data-fit-axis="x">
            @foreach($leaders as $row)
                <a href="{{ $href($row) }}" class="desk-link d-flex align-items-baseline gap-2 mw-100 min-w-0 flex-shrink-0" title="{{ $title($row) }}">
                    <span class="desk-nowrap min-w-0">{{ $row['name'] }}</span>
                    <span class="fw-bold fs-5 text-nowrap flex-shrink-0">{{ $value($row) }}</span>
                </a>
            @endforeach
        </div>
    </div>
@else
    <div class="desk-stack">
        @if($data['period_label'])
            <div class="desk-label desk-nowrap desk-only-h-md" title="Отправленные: {{ $data['period_label'] }} · {{ $unit }}">
                <span class="desk-only-w-md">отправленные: </span>{{ $data['period_label'] }} · {{ $unit }}
            </div>
        @endif

        @if($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Менеджер</th>
                            <th class="num">{{ $money ? 'Сумма' : 'КП' }}</th>
                            <th class="desk-only-w-lg" style="width: 30%;">Доля</th>
                            <th class="num desk-only-w-xl">В работе</th>
                            <th class="num desk-only-w-xl">Выиграно</th>
                            <th class="num desk-only-w-xl">Проиграно</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $title($row) }}">
                                        {{ $row['name'] }}
                                    </a>
                                </td>
                                <td class="num fw-bold desk-nowrap" title="{{ $value_full($row) }}">{{ $value($row) }}</td>
                                <td class="desk-only-w-lg">
                                    <div class="desk-bar">
                                        <i style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                                    </div>
                                </td>
                                <td class="num desk-muted desk-only-w-xl">{{ $row['in_work'] }}</td>
                                <td class="num text-success desk-only-w-xl">{{ $row['won'] }}</td>
                                <td class="num text-danger desk-only-w-xl">{{ $row['lost'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    <li>
                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $title($row) }}">{{ $row['name'] }}</a>

                        <div class="desk-bar flex-shrink-0 desk-only-w-md" style="width: 4rem;" title="{{ $title($row) }}">
                            <i style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                        </div>

                        <span class="text-success fs-8 text-nowrap flex-shrink-0 desk-only-w-lg" title="Выиграно КП">+{{ $row['won'] }}</span>

                        <span class="fw-semibold text-nowrap flex-shrink-0" title="{{ $value_full($row) }}">{{ $value($row) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 text-nowrap flex-shrink-0" data-fit-more="ещё {n}"></div>

        <div class="d-flex gap-2 align-items-baseline flex-nowrap desk-hide-short flex-shrink-0 min-w-0">
            <span class="desk-muted text-nowrap" title="Всего КП в разрезе"><span class="desk-hide-narrow">всего КП: </span>{{ $data['total'] }}</span>
            <span class="pbm-managers desk-muted fs-8 text-nowrap" title="Менеджеров с КП">менеджеров: {{ $data['managers'] }}</span>
            @if($money)
                <span class="fw-semibold text-nowrap ms-auto desk-only-w-md" title="Сумма основных вариантов: {{ $widget::money($data['total_amount'], $data['symbol'], false) }}">
                    {{ $widget::money($data['total_amount'], $data['symbol']) }}
                </span>
            @else
                <span class="text-success fs-8 text-nowrap ms-auto desk-only-w-md" title="Выиграно КП всего">выиграно: {{ $data['total_won'] }}</span>
            @endif
        </div>
    </div>
@endif
