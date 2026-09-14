{{-- Виджет «КП без сделки» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ProposalsNoDealWidget --}}
@php
    $count = (int) $data['count'];
    // раскладка по ступеням: низкий — счётчик (и номера КП в строку от 6 колонок);
    // широкий — счётчик слева, таблица справа; остальное — счётчик сверху и список
    $line = in_array($dh, ['xs', 'sm'], true);
    $side = !$line && in_array($dw, ['lg', 'xl'], true);
    $narrow = $dw === 'xs';
    // строк с запасом (число КП уже ограничено настройкой в data()), лишние прячет подгон .desk-fit
    $list = $count > 0 ? array_slice($data['rows'], 0, $rows_max) : [];
    $chips = $line && !in_array($dw, ['xs', 'sm'], true) ? array_slice($list, 0, 12) : [];

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $all_url = $preview ? 'javascript:void(0)' : route('proposal.index');
    // «Привязать» — тот же попап, что в колонке «Сделка» списка КП
    $deal_url = fn($row) => $preview ? null : ($row['deal_url'] ?: null);
    $cost = fn($row) => $row['amount'] === null ? '—' : $widget::money($row['amount'], $row['symbol']);
    $cost_full = fn($row) => $row['amount'] === null ? 'Нет расчёта' : $widget::money($row['amount'], $row['symbol'], false);
    $caption = fn($row) => trim(($row['number'] !== '' ? '№ ' . $row['number'] . ' · ' : '') . $row['name']);
    $company = fn($row) => $row['company'] ?: ($row['partner'] ?: $row['name']);
    $label = 'без сделки Битрикс24' . ($settings['only_in_work'] ? ', в работе' : '');
@endphp
<div @class([
    'desk-center' => $line && empty($chips),
    'd-flex align-items-center gap-4 h-100 min-w-0' => !empty($chips),
    'd-flex gap-4 h-100 min-w-0' => $side,
    'desk-stack' => !$line && !$side,
])>
    <div @class(['min-w-0', 'nd-side flex-shrink-0' => $side || !empty($chips)])>
        {{-- в колонке сбоку подпись короче: «Битрикс24» не влезает и режется посередине --}}
        <div class="desk-label desk-nowrap" title="{{ $label }}">без сделки@if(!$side && empty($chips))<span class="desk-only-w-md"> Битрикс24</span>@endif{{ $settings['only_in_work'] ? ', в работе' : '' }}</div>
        <div @class(['desk-value', 'nd-value-list' => !empty($list) && !$line, 'text-warning' => $count > 0])>{{ $count }}</div>
        @if($count > 0)
            <div class="desk-muted fs-8 desk-hide-short desk-hide-narrow">не видно в воронке и расхождениях</div>
        @elseif(!$line)
            <div class="desk-muted fs-8 desk-hide-short">Все КП привязаны к сделкам</div>
        @endif
    </div>

    @if(!empty($chips))
        {{-- низкий широкий блок: номера КП в строку, не влезшие прячет подгон --}}
        <div class="nd-chips desk-fit d-flex align-items-center gap-2 flex-grow-1" data-fit-axis="x">
            @foreach($chips as $row)
                <a href="{{ $href($row) }}" class="badge badge-light-warning text-nowrap flex-shrink-0 text-decoration-none" title="{{ $caption($row) }} · {{ $company($row) }}">
                    {{ $row['number'] !== '' ? '№ ' . $row['number'] : $company($row) }}
                </a>
            @endforeach
        </div>
    @elseif($side && !empty($list))
        <div class="nd-main d-flex flex-column flex-grow-1 min-w-0 min-h-0">
            <div class="desk-fit flex-grow-1" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Компания</th>
                            <th class="desk-only-w-xl">Название</th>
                            <th class="num">Сумма</th>
                            <th class="desk-only-w-xl">Менеджер</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-nowrap"><a href="{{ $href($row) }}" class="desk-link fw-semibold text-hover-primary" title="{{ $row['name'] }}">{{ $row['number'] !== '' ? $row['number'] : '—' }}</a></td>
                                <td class="desk-cut" title="{{ $company($row) }}">{{ $company($row) }}</td>
                                <td class="desk-cut desk-only-w-xl" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                                <td class="num desk-nowrap" title="{{ $cost_full($row) }}">{{ $cost($row) }}</td>
                                <td class="desk-cut desk-only-w-xl" title="{{ $row['manager'] }}">{{ $row['manager'] ?: '—' }}</td>
                                <td class="num">
                                    <a @if($deal_url($row)) href="javascript:box({href: '{{ $deal_url($row) }}'})" @else href="javascript:void(0)" @endif
                                       class="badge badge-light-secondary text-decoration-none bg-hover-light-primary text-hover-primary" title="Привязать сделку Битрикс24">
                                        <i class="fa-light fa-link fs-8"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="desk-muted fs-8 text-nowrap flex-shrink-0" data-fit-more="ещё {n}"></div>
            {{-- итог внизу: высокий блок с короткой таблицей не пустует снизу --}}
            <div class="d-flex gap-2 align-items-baseline flex-nowrap desk-hide-short flex-shrink-0 min-w-0 mt-auto pt-1">
                <span class="desk-muted fs-8 text-nowrap"><i class="fa-light fa-link fs-8"></i> — привязать сделку</span>
                <a href="{{ $all_url }}" class="desk-link text-primary fs-8 text-nowrap ms-auto">все КП <i class="fa-light fa-arrow-right fs-8"></i></a>
            </div>
        </div>
    @elseif(!$line && !empty($list))
        <ul class="desk-list desk-stack-grow desk-fit">
            @foreach($list as $row)
                <li>
                    @if($narrow)
                        <a href="{{ $href($row) }}" class="desk-link fw-semibold text-nowrap" title="{{ $caption($row) }} · {{ $company($row) }}">{{ $row['number'] !== '' ? $row['number'] : '—' }}</a>
                    @else
                        <a href="{{ $href($row) }}" class="desk-link desk-grow d-flex align-items-baseline gap-1 text-hover-primary" title="{{ $caption($row) }}">
                            <span class="fw-semibold flex-shrink-0 desk-hide-narrow">{{ $row['number'] !== '' ? $row['number'] : '—' }}</span>
                            <span class="desk-muted desk-nowrap min-w-0" title="{{ $company($row) }}">{{ $company($row) }}</span>
                        </a>
                        <span class="fw-semibold text-nowrap flex-shrink-0 desk-only-w-md" title="{{ $cost_full($row) }}">{{ $cost($row) }}</span>
                    @endif
                    <a @if($deal_url($row)) href="javascript:box({href: '{{ $deal_url($row) }}'})" @else href="javascript:void(0)" @endif
                       class="badge badge-light-secondary text-decoration-none bg-hover-light-primary text-hover-primary flex-shrink-0 ms-auto"
                       title="Привязать сделку Битрикс24">
                        <i class="fa-light fa-link fs-8"></i>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 text-nowrap flex-shrink-0" data-fit-more="ещё {n}"></div>
        {{-- итог внизу, только в высоком блоке: в средней высоте под счётчиком остаётся одна строка списка.
             desk-only-h-lg — на обёртке: d-flex с !important перебил бы его display: none --}}
        <div class="desk-only-h-lg flex-shrink-0 mt-auto">
            <div class="d-flex gap-2 align-items-baseline flex-nowrap min-w-0">
                <span class="desk-muted fs-8 text-nowrap desk-only-w-md"><i class="fa-light fa-link fs-8"></i> — привязать сделку</span>
                <a href="{{ $all_url }}" class="desk-link text-primary fs-8 text-nowrap ms-auto desk-only-w-md">все КП <i class="fa-light fa-arrow-right fs-8"></i></a>
            </div>
        </div>
    @endif
</div>
