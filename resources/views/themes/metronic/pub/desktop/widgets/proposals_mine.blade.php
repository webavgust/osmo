{{-- Виджет «Мои КП» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ProposalsMineWidget --}}
@php
    // широкий блок — таблица (не в две ячейки высотой), узкий — список; в две колонки — значок статуса и номер
    $table = in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);
    $narrow = $dw === 'xs';
    // строк с запасом (число КП уже ограничено настройкой в data()), лишние прячет подгон .desk-fit
    $list = array_slice($data['rows'], 0, $rows_max);

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $all_url = $preview ? 'javascript:void(0)' : route('proposal.index');
    // смена статуса — тот же попап, что в карточке КП и в списке
    $status_url = fn($row) => $preview ? null : ($row['status_url'] ?: null);
    $cost = fn($row) => $row['amount'] === null ? '—' : $widget::money($row['amount'], $row['symbol']);
    $cost_full = fn($row) => $row['amount'] === null ? 'Нет расчёта' : $widget::money($row['amount'], $row['symbol'], false);
    // patch v33: второстепенное КП — ветка под своим главным, как в списке КП; только просмотр,
    // поэтому ни подсветки «старое», ни смены статуса
    $child = fn($row) => !empty($row['is_child']);
    $branch = fn($row) => 'Второстепенное к ' . $row['main_ref'] . ': только просмотр, в расчётах не участвует';
    $stale = fn($row) => !$child($row) && $row['days'] !== null && $row['days'] > (int) $data['stale'];
    $age = fn($row) => $row['days'] === null ? '—' : $row['days'] . ' дн.';
    $age_title = fn($row) => $row['date'] ? 'Дата КП: ' . $row['date'] : 'Дата КП не указана';
    $caption = fn($row) => trim(($row['number'] !== '' ? '№ ' . $row['number'] . ' · ' : '') . $row['name']);
    $company = fn($row) => $row['company'] ?: ($row['partner'] ?: $row['name']);
    // «старше N дн.» считает data() по всем КП отбора; в показанных строках их может быть меньше
    $stale_count = (int) ($data['stale_count'] ?? count(array_filter($data['rows'], $stale)));
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-user-tie"></i> У вас нет таких КП
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
                            <th class="num">Сумма</th>
                            <th class="num">Давность</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr @class(['pl-child' => $child($row)]) @if($child($row)) title="{{ $branch($row) }}" @endif>
                                <td class="desk-nowrap">
                                    @if($child($row))<span class="pl-branch"></span>@endif
                                    <a href="{{ $href($row) }}" class="desk-link fw-semibold text-hover-primary" title="{{ $row['name'] }}">
                                        {{ $row['number'] !== '' ? $row['number'] : '—' }}
                                    </a>
                                </td>
                                <td class="desk-cut" title="{{ $row['company'] ?: $row['partner'] }}">{{ $row['company'] ?: ($row['partner'] ?: '—') }}</td>
                                <td class="desk-cut desk-only-w-xl" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                                <td class="num desk-nowrap" title="{{ $cost_full($row) }}">{{ $cost($row) }}</td>
                                <td @class(['num desk-nowrap', 'text-warning fw-semibold' => $stale($row)]) title="{{ $age_title($row) }}">{{ $age($row) }}</td>
                                <td>
                                    @if($child($row))
                                        <span class="badge badge-light-warning text-nowrap" title="{{ $branch($row) }} · статус: {{ $row['status_label'] }}">второстепенное</span>
                                    @else
                                        <a @if($status_url($row)) href="javascript:box({href: '{{ $status_url($row) }}'})" @else href="javascript:void(0)" @endif
                                           class="badge badge-light-{{ $row['status_color'] }} text-nowrap text-decoration-none"
                                           title="{{ $row['status_label'] }} — сменить статус">
                                            <i class="fa-light {{ $row['status_icon'] }} fs-8 me-1"></i>{{ $row['status_label'] }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    <li @class(['pl-child' => $child($row)])
                        @if($narrow) title="{{ $child($row) ? $branch($row) . ' · ' : '' }}{{ $caption($row) }} · {{ $company($row) }} · {{ $age($row) }} · {{ $cost_full($row) }}" @endif>
                        @if($child($row))
                            {{-- уголок ветки — на месте значка статуса: статус второстепенного не меняют, строка не шире главной --}}
                            <span class="pl-branch" title="{{ $branch($row) }} · статус: {{ $row['status_label'] }}"></span>
                        @else
                            <a @if($status_url($row)) href="javascript:box({href: '{{ $status_url($row) }}'})" @else href="javascript:void(0)" @endif
                               class="text-decoration-none flex-shrink-0" title="{{ $row['status_label'] }} — сменить статус">
                                <i class="fa-light {{ $row['status_icon'] }} text-{{ $row['status_color'] }}"></i>
                            </a>
                        @endif
                        @if($narrow)
                            <a href="{{ $href($row) }}" @class(['desk-link fw-semibold text-nowrap', 'text-warning' => $stale($row)])>{{ $row['number'] !== '' ? $row['number'] : '—' }}</a>
                        @else
                            <a href="{{ $href($row) }}" class="desk-link desk-grow d-flex align-items-baseline gap-1 text-hover-primary" title="{{ $child($row) ? $branch($row) . ' · ' : '' }}{{ $caption($row) }}">
                                <span class="fw-semibold flex-shrink-0 desk-hide-narrow">{{ $row['number'] !== '' ? $row['number'] : '—' }}</span>
                                <span class="desk-muted desk-nowrap min-w-0" title="{{ $company($row) }}">{{ $company($row) }}</span>
                            </a>
                            @if($child($row))
                                <span class="badge badge-light-warning flex-shrink-0 desk-only-w-lg" title="{{ $branch($row) }}">второстепенное</span>
                            @endif
                            <span @class(['text-nowrap fs-8 flex-shrink-0 desk-only-w-md', 'text-warning fw-semibold' => $stale($row), 'desk-muted' => !$stale($row)])
                                  title="{{ $age_title($row) }}">{{ $age($row) }}</span>
                            <span class="fw-semibold text-nowrap flex-shrink-0" title="{{ $cost_full($row) }}">{{ $cost($row) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 text-nowrap flex-shrink-0" data-fit-more="ещё {n}"></div>

        <div class="d-flex gap-2 align-items-baseline flex-nowrap desk-hide-short flex-shrink-0 min-w-0">
            <span class="desk-muted text-nowrap" title="Всего моих КП по отбору"><span class="desk-hide-narrow">всего: </span>{{ $data['total'] }}</span>
            @if($stale_count > 0)
                <span class="text-warning fs-8 fw-semibold text-nowrap desk-only-w-md" title="Из всех моих КП по отбору отправлены больше {{ (int) $data['stale'] }} {{ \App\Facades\Tools::morph((int) $data['stale'], 'дня', 'дней', 'дней') }} назад">
                    старше {{ (int) $data['stale'] }} дн.: {{ $stale_count }}
                </span>
            @endif
            <a href="{{ $all_url }}" class="desk-link text-primary fs-8 text-nowrap ms-auto desk-only-w-md">все КП <i class="fa-light fa-arrow-right fs-8"></i></a>
        </div>
    </div>
@endif
