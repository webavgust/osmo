{{-- Виджет «Последние КП» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ProposalsRecentWidget --}}
@php
    // широкий блок — таблица (но не в две ячейки высотой: шапка и строка не влезут), узкий — список;
    // в две колонки — только номера
    $table = in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);
    $narrow = $dw === 'xs';
    // строк с запасом (число КП уже ограничено настройкой в data()), лишние прячет подгон .desk-fit
    $list = array_slice($data['rows'], 0, $rows_max);

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $all_url = $preview ? 'javascript:void(0)' : route('proposal.index');
    $cost = fn($row) => $row['amount'] === null ? '—' : $widget::money($row['amount'], $row['symbol']);
    $cost_full = fn($row) => $row['amount'] === null ? 'Нет расчёта' : $widget::money($row['amount'], $row['symbol'], false);
    $caption = fn($row) => trim(($row['number'] !== '' ? '№ ' . $row['number'] . ' · ' : '') . $row['name']);
    $fresh = count(array_filter($data['rows'], fn($row) => $row['fresh']));
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-clock-rotate-left"></i> Нет КП
    </div>
@else
    <div class="desk-stack">
        @if($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Компания</th>
                            <th class="desk-only-w-xl">Название</th>
                            @if($settings['show_cost'])<th class="num">Сумма</th>@endif
                            <th>Статус</th>
                            <th class="num desk-only-w-xl">Изменено</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-nowrap">
                                    <a href="{{ $href($row) }}" class="desk-link fw-semibold text-hover-primary" title="{{ $row['name'] }}">
                                        {{ $row['number'] !== '' ? $row['number'] : '—' }}
                                    </a>
                                    @if($row['fresh'])
                                        <i class="fa-light fa-sparkles text-primary ms-1 fs-8" title="Изменено за последнюю неделю"></i>
                                    @endif
                                </td>
                                <td class="desk-cut" title="{{ $row['company'] ?: $row['partner'] }}">{{ $row['company'] ?: ($row['partner'] ?: '—') }}</td>
                                <td class="desk-cut desk-only-w-xl" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                                @if($settings['show_cost'])
                                    <td class="num desk-nowrap" title="{{ $cost_full($row) }}">{{ $cost($row) }}</td>
                                @endif
                                <td>
                                    <span class="badge badge-light-{{ $row['status_color'] }} text-nowrap" title="{{ $row['status_label'] }}">
                                        <i class="fa-light {{ $row['status_icon'] }} fs-8 me-1"></i>{{ $row['status_label'] }}
                                    </span>
                                </td>
                                <td class="num desk-muted desk-nowrap desk-only-w-xl">{{ $row['updated'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    @if($narrow)
                        {{-- две колонки: точка статуса и номер, остальное — в подсказке --}}
                        <li title="{{ $caption($row) }} · {{ $row['company'] ?: $row['partner'] }} · {{ $row['status_label'] }} · {{ $cost_full($row) }}">
                            <span class="bullet bullet-dot bg-{{ $row['status_color'] }} w-8px h-8px flex-shrink-0"></span>
                            <a href="{{ $href($row) }}" class="desk-link fw-semibold text-nowrap">{{ $row['number'] !== '' ? $row['number'] : '—' }}</a>
                        </li>
                    @else
                        <li>
                            <span class="bullet bullet-dot bg-{{ $row['status_color'] }} w-8px h-8px flex-shrink-0" title="{{ $row['status_label'] }}"></span>
                            <a href="{{ $href($row) }}" class="desk-link desk-grow d-flex align-items-baseline gap-1 text-hover-primary" title="{{ $caption($row) }}">
                                <span class="fw-semibold flex-shrink-0 desk-hide-narrow">{{ $row['number'] !== '' ? $row['number'] : '—' }}</span>
                                <span class="desk-muted desk-nowrap min-w-0" title="{{ $row['company'] ?: ($row['partner'] ?: $row['name']) }}">{{ $row['company'] ?: ($row['partner'] ?: $row['name']) }}</span>
                            </a>
                            @if($row['fresh'])
                                <i class="fa-light fa-sparkles text-primary flex-shrink-0 fs-8 desk-only-w-md" title="Изменено за последнюю неделю"></i>
                            @endif
                            <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-lg" title="Изменено">{{ $row['updated'] ?? '—' }}</span>
                            @if($settings['show_cost'])
                                <span class="fw-semibold text-nowrap flex-shrink-0" title="{{ $cost_full($row) }}">{{ $cost($row) }}</span>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 text-nowrap flex-shrink-0" data-fit-more="ещё {n}"></div>

        <div class="d-flex gap-2 align-items-baseline flex-nowrap desk-hide-short flex-shrink-0 min-w-0">
            <span class="desk-muted text-nowrap" title="Всего КП по отбору"><span class="desk-hide-narrow">всего КП: </span>{{ $data['total'] }}</span>
            @if($fresh > 0)
                <span class="text-primary fs-8 text-nowrap desk-only-w-md" title="Изменено за последнюю неделю">
                    <i class="fa-light fa-sparkles fs-8"></i> за неделю: {{ $fresh }}
                </span>
            @endif
            <a href="{{ $all_url }}" class="desk-link text-primary fs-8 text-nowrap ms-auto desk-only-w-md">все КП <i class="fa-light fa-arrow-right fs-8"></i></a>
        </div>
    </div>
@endif
