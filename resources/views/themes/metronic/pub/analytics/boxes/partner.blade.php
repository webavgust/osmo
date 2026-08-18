@extends('components.box.box-static-extralarge')

@section('body')
    @php
        $grade = \App\Modules\Pub\Partner\Models\PartnerGrade::tryFrom((string) $partner->grade)?->data();
        $states = [
            'paid' => ['label' => 'Оплачен', 'color' => 'success'],
            'overdue' => ['label' => 'Просрочен', 'color' => 'danger'],
            'planned' => ['label' => 'Ожидается', 'color' => 'primary'],
            'unknown' => ['label' => 'Без даты', 'color' => 'secondary'],
            'canceled' => ['label' => 'Отменён', 'color' => 'dark'],
        ];
        $totals_payments = \App\Modules\Pub\Analytics\Services\PartnerStatsService::paymentTotals($payments);
    @endphp

    {{-- Кто это и за какой период смотрим --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('partner.detail', $partner) }}" class="fs-3 fw-bold"
               style="color: {{ $grade['color']['medal'] ?? '#7e8299' }}">
                <x-ui.icon.solid icon="fa-medal" class="me-1"/>{{ $partner->name }}
            </a>
            <div class="fs-8 text-muted">{{ $grade['label'] ?? '—' }} · {{ $grade['description'] ?? '' }}</div>
        </div>

        <div class="text-end fs-7 text-muted" style="max-width: 340px">
            @if($year)
                Расшифровки показаны за {{ $year }} год. Разбивка по годам — за всю историю.
            @else
                Показана вся история партнёра.
            @endif
        </div>
    </div>

    <ul class="nav nav-tabs nav-line-tabs mb-4 fs-6">
        @php
            $tabs = [
                'stats' => 'По годам',
                'proposals' => 'КП (' . $proposals->count() . ')',
                'volume' => 'Объём (' . $volume->count() . ')',
                'contracts' => 'Договоры (' . $contracts->count() . ')',
                'payments' => 'Платежи (' . $payments->count() . ')',
            ];
        @endphp
        @foreach($tabs as $code => $label)
            <li class="nav-item">
                <a class="nav-link @if($tab === $code) active @endif" data-bs-toggle="tab" href="#partner_tab_{{ $code }}">
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        {{-- По годам --}}
        <div class="tab-pane fade @if($tab === 'stats') show active @endif" id="partner_tab_stats">
            <table class="table table-row-bordered align-middle m-0">
                <thead>
                    <tr class="fw-bold fs-7 text-muted text-uppercase">
                        <th width="70" class="ps-3">Год</th>
                        <th width="110" class="text-center">Место</th>
                        <th width="90" class="text-center">Балл</th>
                        <th class="text-center">КП</th>
                        <th class="text-center">Конверсия</th>
                        <th class="text-end">Объём КП</th>
                        <th class="text-center">Спецификации</th>
                        <th class="text-end">Подписано</th>
                        <th class="text-center">Платежи</th>
                        <th class="text-center">КП → договор</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($years as $item)
                        @php $row = $item['row']; @endphp
                        <tr @class(['fs-5', 'bg-light-primary' => $item['current']])>
                            <td class="fw-bold ps-3">
                                {{ $item['year'] }}
                            </td>
                            <td class="text-center">
                                @if($item['place'])
                                    <span class="fw-bold fs-4 pe-1">{{ $item['place'] }}</span>
                                    <span class="fs-6 text-muted">из {{ $item['total'] }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item['score'] !== null)
                                    <span class="badge badge-{{ $item['rank']['color'] }} fs-6 fw-bold">
                                        {{ $item['rank']['letter'] }} &ndash; {{ $item['score'] }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="fs-4 fw-bold">{{ $row['proposals'] ?? 0 }}</div>
                                <div class="fs-8 text-muted">
                                    <span class="text-success">{{ $row['won'] ?? 0 }}</span> /
                                    <span class="text-danger">{{ $row['lost'] ?? 0 }}</span> /
                                    {{ $row['in_work'] ?? 0 }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if(($row['conversion'] ?? null) !== null)
                                    <span class="fs-5 fw-bold">{{ round($row['conversion']) }}%</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap fs-5 fw-bold">
                                {{ tools()->cost_normalize(round($row['amount_won'] ?? 0)) }} ₽
                            </td>
                            <td class="text-center text-nowrap">
                                <span class="text-success fw-bold">{{ $row['specs_signed'] ?? 0 }}</span>
                                / <span class="fw-bold">{{ $row['specs'] ?? 0 }}</span>
                            </td>
                            <td class="text-end text-nowrap fw-bold">{{ tools()->cost_normalize(round($row['specs_sum'] ?? 0)) }} ₽</td>
                            <td class="text-center text-nowrap">
                                <span class="text-success">{{ $row['payments_paid'] ?? 0 }}</span>
                                @if($row['payments_overdue'] ?? 0)
                                    / <span class="text-danger fw-bold">{{ $row['payments_overdue'] }}</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                {{ \App\Modules\Pub\Analytics\Services\PartnerScoringService::humanPeriod($row['days_to_spec'] ?? null) ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-8">По партнёру ещё нет ни КП, ни спецификаций</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="fs-8 text-muted mt-3">
                Место считается среди всех партнёров, у кого в этом году было хоть одно КП или
                спецификация. Балл каждый год нормируется на лидера этого года, поэтому 100 в
                разные годы — это разные деньги. Спецификации относятся к году по своей дате.
            </div>
        </div>

        {{-- Все КП --}}
        <div class="tab-pane fade @if($tab === 'proposals') show active @endif" id="partner_tab_proposals">
            <table class="table table-row-bordered align-middle m-0">
                <thead>
                    <tr class="fw-bold fs-7 text-muted text-uppercase">
                        <th width="120">Номер</th>
                        <th>Название</th>
                        <th width="110" class="text-center">Отправлено</th>
                        <th width="130" class="text-center">Статус</th>
                        <th width="150" class="text-end">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposals as $row)
                        @php $status = $statuses[(string) $row->status] ?? null; @endphp
                        <tr class="fs-5">
                            <td>
                                <div class="d-flex justify-content-start align-items-center">
                                    <a href="{{ route('proposal.detail', [$row->group, $row->iteration]) }}" target="_blank" class="fw-bold">
                                        {{ $row->number ?: 'б/н' }}
                                    </a>
                                    @if($row->iteration > 1)
                                        <x-ui.badge.light class="ms-2" type="primary">{{ $row->iteration }}</x-ui.badge.light>
                                    @endif
                                </div>
                            </td>
                            <td >{{ $row->name }}</td>
                            <td class="text-center text-nowrap">{{ $row->sended_at?->format('d.m.Y') ?? '—' }}</td>
                            <td class="text-center">
                                @if(!empty($status))
                                    <x-ui.badge.light class="fs-7 px-3" :type="$status['color']">{{ $status['label'] }}</x-ui.badge.light>
                                @else
                                    <span class="text-muted">{{ $row->status }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="fw-bold">{{ tools()->cost_normalize(round($row->cost_total)) }}</span>
                                <span class="fs-8 text-muted">{{ $row->currency_slug }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-8">КП нет</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Объём --}}
        <div class="tab-pane fade @if($tab === 'volume') show active @endif" id="partner_tab_volume">
            <table class="table table-row-bordered align-middle m-0">
                <thead>
                    <tr class="fw-bold fs-7 text-muted text-uppercase">
                        <th width="120">Номер</th>
                        <th>Название</th>
                        <th width="110" class="text-center">Отправлено</th>
                        <th width="150" class="text-end">Сумма</th>
                        <th width="150" class="text-end">В рублях</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($volume as $row)
                        <tr class="fs-5">
                            <td>
                                <a href="{{ route('proposal.detail', [$row->group, $row->iteration]) }}" target="_blank" class="fw-bold">
                                    {{ $row->number ?: 'б/н' }}
                                </a>
                            </td>
                            <td>{{ $row->name }}</td>
                            <td class="text-center text-nowrap">{{ $row->sended_at?->format('d.m.Y') ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                <span class="fw-bold">{{ tools()->cost_normalize(round($row->cost_total)) }}</span>
                                <span class="fs-8 text-muted">{{ $row->currency_slug }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="fw-bold">{{ tools()->cost_normalize(round($row->cost_total * \App\Modules\Pub\Analytics\Services\PartnerScoringService::rate($row->currency_slug))) }}</span> ₽
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-8">Выигранных КП нет</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Договоры и спецификации --}}
        <div class="tab-pane fade @if($tab === 'contracts') show active @endif" id="partner_tab_contracts">
            <table class="table table-bordered align-middle m-0">
                <thead>
                    <tr class="fw-bold fs-7 text-muted text-uppercase">
                        <th width="220">Рамочный договор</th>
                        <th>Спецификация</th>
                        <th width="100" class="text-center">Дата</th>
                        <th width="110" class="text-center">Статус</th>
                        <th width="110" class="text-center">Подписана</th>
                        <th width="90" class="text-center">КП</th>
                        <th width="150" class="text-end">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract_id => $specs)
                        @php $first = $specs->first(); @endphp
                        @foreach($specs as $spec)
                            <tr class="fs-6">
                                @if($loop->first)
                                    <td rowspan="{{ $specs->count() }}">
                                        <div class="d-flex justify-content-between">
                                            @php $type = \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $first->contract_type)?->data(); @endphp
                                            <div class="fs-6 fw-bold text-{{ $type['color'] ?? 'dark' }}">
                                                @if(!empty($type))
                                                    <x-ui.icon.regular :icon="$type['icon']" class="me-1"/>{{ $type['label'] }}
                                                @endif
                                            </div>
                                            <code>{{ $first->contract_number ?: 'б/н' }}</code>
                                        </div>
                                        <div class="fs-8 text-muted">{{ $first->contract_date?->format('d.m.Y') ?? 'без даты' }}</div>
                                    </td>
                                @endif

                                <td>
                                    {{ $spec->name }}
                                </td>
                                <td class="text-center text-nowrap">
                                    {{ $spec->spec_date?->format('d.m.Y') ?? '—' }}
                                </td>
                                <td class="text-center">
                                    @php $status = \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::tryFrom((string) $spec->status)?->data(); @endphp
                                    <x-ui.badge.light :type="$status['color'] ?? 'secondary'" class="fs-6">
                                        {{ $status['label'] ?? $spec->status }}
                                    </x-ui.badge.light>
                                </td>
                                <td class="text-center">
                                    @if($spec->is_signed)
                                        <x-ui.icon.regular icon="fa-check" class="text-success"></x-ui.icon.regular>
                                    @else
                                        <span class="text-muted">нет</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($spec->proposals_count > 0)
                                        <span class="fw-bold">{{ $spec->proposals_count }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <span class="fw-bold">{{ tools()->cost_normalize(round($spec->amount)) }}</span>
                                    <span class="fs-8 text-muted">{{ $spec->currency_slug }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-8">Договоров нет</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Платежи --}}
        <div class="tab-pane fade @if($tab === 'payments') show active @endif" id="partner_tab_payments">
            <div class="d-flex flex-wrap gap-6 mb-3">
                <div>
                    <div class="fs-8 text-uppercase fw-bold text-dark">Оплачено</div>
                    <div class="fs-3 fw-bold text-success">
                        {{ tools()->cost_normalize(round($totals_payments['paid_sum'])) }} ₽
                        <span class="fs-7 text-muted">/ {{ $totals_payments['paid'] }} шт.</span>
                    </div>
                </div>
                <div class="ms-5">
                    <div class="fs-8 text-uppercase fw-bold text-dark">Просрочено</div>
                    <div class="fs-3 fw-bold text-danger">
                        {{ tools()->cost_normalize(round($totals_payments['overdue_sum'])) }} ₽
                        <span class="fs-7 text-muted">/ {{ $totals_payments['overdue'] }} шт.</span>
                    </div>
                </div>
            </div>

            <table class="table table-row-bordered align-middle m-0">
                <thead>
                    <tr class="fw-bold fs-7 text-muted text-uppercase">
                        <th>Спецификация</th>
                        <th width="120" class="text-center">План</th>
                        <th width="120" class="text-center">Факт</th>
                        <th width="140" class="text-end">Сумма</th>
                        <th width="120" class="text-center">Состояние</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $row)
                        @php $state = $states[$row->state] ?? $states['unknown']; @endphp
                        <tr class="fs-6">
                            <td>
                                <span class="fs-5 fw-bold">{{ $row->spec_name }}</span>
                                <div class="fs-8 text-muted">договор {{ $row->contract_number ?: 'б/н' }}</div>
                            </td>
                            <td class="text-center text-nowrap fw-bold">
                                @if(!empty($row->date_plan))
                                    {{ $row->date_plan?->format('d.m.Y') ?? '—' }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap fw-bold">
                                @if(!empty($row->date_fact))
                                    {{ $row->date_fact?->format('d.m.Y') ?? '—' }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="fs-5 fw-bold">{{ tools()->cost_normalize(round($row->amount_fact ?: $row->amount_plan)) }}</span>
                                <span class="fs-8 text-muted">{{ $row->currency_slug }}</span>
                            </td>
                            <td class="text-center">
                                <x-ui.badge.light :type="$state['color']" class="fs-7">{{ $state['label'] }}</x-ui.badge.light>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-8">Платежей нет</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <a href="{{ route('partner.detail', $partner) }}" class="btn btn-primary">
            <i class="fas fa-arrow-right me-1"></i>Карточка партнёра
        </a>
    </div>
@endsection
