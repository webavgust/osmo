@extends('layouts.layout')

@section('content')
    <div class="d-flex flex-column gap-6">

        {{-- Шапка --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h2 class="fw-bold mb-2">{{ $proposal->name }}</h2>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <x-proposal.status :proposal="$proposal" editable="1"/>
                        <x-proposal.deal :proposal="$proposal"/>
                        @if($proposal->number)
                            <span class="badge badge-light fs-7">№ {{ $proposal->number }}</span>
                        @endif
                    </div>
                </div>

                <div class="card-toolbar gap-2">
                    <a href="{{ route('proposal_tools.price_history', $proposal) }}" class="btn btn-sm btn-light">
                        <i class="fa-light fa-chart-line fs-5 me-2"></i>История цен
                    </a>
                    <a href="javascript:box({href: '{{ route('proposal_tools.box_clone', [$proposal, $proposal->iteration]) }}'})"
                       class="btn btn-sm btn-light">
                        <i class="fa-light fa-clone fs-5 me-2"></i>Клонировать
                    </a>
                    <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}" class="btn btn-sm btn-light-primary">
                        <i class="fa-light fa-file-invoice fs-5 me-2"></i>Открыть КП
                    </a>
                </div>
            </div>

            <div class="card-body py-5">
                <div class="row g-5">
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Компания</div>
                        <div class="fw-semibold fs-4">
                            @if($proposal->company)
                                <a href="{{ route('company.detail', $proposal->company) }}" class="text-gray-900 text-hover-primary">
                                    {{ $proposal->company->name }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Партнёр</div>
                        <div class="fw-semibold fs-4">
                            @if($proposal->partner)
                                <a href="{{ route('partner.detail', $proposal->partner) }}" class="text-gray-900 text-hover-primary">
                                    {{ $proposal->partner?->name }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Менеджер</div>
                        <div class="fw-semibold fs-4">{{ $proposal->manager?->name ?: '—' }}</div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Отправлено</div>
                        <div class="fw-semibold fs-4">{{ $proposal->sended_at?->format('d.m.Y') ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Где встала сделка --}}        @if($bottleneck)
            <div class="alert alert-{{ $bottleneck['state'] === 'empty' ? 'danger' : 'warning' }} d-flex align-items-center mb-0">
                <i class="fa-light {{ $bottleneck['icon'] }} fs-2 me-4"></i>
                <div>
                    <div class="fs-5 fw-bold">Остановилось на шаге «{{ $bottleneck['title'] }}»</div>
                    <div class="fs-6">{{ $bottleneck['hint'] }}</div>
                </div>
            </div>
        @else
            <div class="alert alert-success d-flex align-items-center mb-0">
                <i class="fa-light fa-circle-check fs-2 me-4"></i>
                <div class="fw-bold">Цепочка пройдена полностью: от КП до выданных лицензий</div>
            </div>
        @endif

        {{-- Цепочка --}}
        <div class="row g-4">
            @foreach($steps as $index => $step)
                @php
                    $color = match($step['state']) {
                        'ok' => 'success',
                        'warn' => 'warning',
                        default => 'gray-400',
                    };
                    $bg = match($step['state']) {
                        'ok' => 'bg-light-success',
                        'warn' => 'bg-light-warning',
                        default => 'bg-light',
                    };
                @endphp

                <div class="col-6 col-lg-4 col-xxl-2">
                    <div class="card h-100 border-0 {{ $bg }}">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <i class="fa-light {{ $step['icon'] }} fs-2 text-{{ $color }}"></i>
                                <span class="badge badge-circle bg-white fw-bold badge-light fs-6">{{ $index + 1 }}</span>
                            </div>

                            <div class="fs-7 fw-bold text-{{ $color }} text-uppercase mb-1">{{ $step['title'] }}</div>

                            <div class="fw-bold text-gray-900 mb-2 fs-6" style="word-break: break-word;">
                                @if($step['url'])
                                    <a href="{{ $step['url'] }}" class="text-gray-900">{{ $step['value'] }}</a>
                                @else
                                    {{ $step['value'] }}
                                @endif
                            </div>

                            <div class="fs-9 text-muted mt-auto">{{ $step['hint'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Деньги --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-0">Деньги по сделке</h4>
                    <span class="text-muted fs-7">Отменённые спецификации в суммы не входят</span>
                </div>
            </div>

            <div class="card-body py-5">
                <div class="row g-5 mb-5">
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Сумма спецификаций</div>
                        <div class="fs-3 fw-bold">{{ tools()->cost_normalize(round($money['spec'])) }}</div>
                        @if($money['canceled'])
                            <div class="fs-8 text-muted">
                                отменено на {{ tools()->cost_normalize(round($money['canceled'])) }}
                            </div>
                        @endif
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">План платежей</div>
                        <div class="fs-3 fw-bold">{{ tools()->cost_normalize(round($money['plan'])) }}</div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Поступило</div>
                        <div class="fs-3 fw-bold text-success">{{ tools()->cost_normalize(round($money['fact'])) }}</div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Осталось получить</div>
                        <div class="fs-3 fw-bold text-{{ $money['left'] > 0 ? 'warning' : 'muted' }}">
                            {{ tools()->cost_normalize(round($money['left'])) }}
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="progress h-8px flex-grow-1 bg-light">
                        <div class="progress-bar bg-success" style="width: {{ $money['progress'] }}%"></div>
                    </div>
                    <span class="fw-bold">{{ $money['progress'] }}%</span>
                </div>

                @if($money['mismatch'])
                    <div class="alert alert-warning d-flex align-items-center mt-5 mb-0">
                        <i class="fa-light fa-scale-unbalanced fs-2 me-4"></i>
                        <div class="fs-6">
                            Сумма спецификаций и план платежей расходятся на
                            <b>{{ tools()->cost_normalize(round(abs($money['spec'] - $money['plan']))) }}</b> —
                            стоит проверить график платежей.
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Договоры и спецификации --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-0">Договоры и спецификации</h4>
                </div>
            </div>

            <div class="card-body p-0">
                @if($contracts->isEmpty())
                    <div class="text-center text-muted py-10">По этому КП договоров нет</div>
                @else
                    @foreach($contracts as $contract)
                        <div class="border-bottom p-5">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                <a href="javascript:box({href: '{{ route('contract.box_edit', $contract->id) }}'})"
                                   class="fw-bold fs-5 text-gray-900 text-hover-primary">
                                    Договор {{ $contract->number ?: '№ не указан' }}
                                </a>
                                <span class="badge badge-light-{{ $contract->cb_signed ? 'success' : 'warning' }}">
                                    {{ $contract->cb_signed ? 'подписан' : 'не подписан' }}
                                </span>
                                @if($contract->date)
                                    <span class="text-muted fs-7">от {{ $contract->date->format('d.m.Y') }}</span>
                                @endif
                                @if($contract->company_id)
                                    <a href="{{ route('company.detail', $contract->company_id) }}"
                                       class="text-muted fs-7 text-hover-primary">
                                        {{ $contract->company_name }}
                                    </a>
                                @endif
                                @if($contract->old)
                                    <span class="badge badge-light">архивный</span>
                                @endif
                            </div>

                            @php $contract_specs = $specifications->where('contract_id', $contract->id); @endphp

                            @if($contract_specs->isEmpty())
                                <div class="text-muted fs-7">Спецификаций по договору нет</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                                        <thead>
                                        <tr class="fw-bold text-muted bg-light">
                                            <th class="ps-3">СПЕЦИФИКАЦИЯ</th>
                                            <th width="220">СТАТУС</th>
                                            <th class="text-end" width="150">СУММА</th>
                                            <th class="text-end" width="200">ПЛАТЕЖИ</th>
                                            <th class="text-end pe-3" width="120">ЛИЦЕНЗИИ</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($contract_specs as $spec)
                                            @php
                                                $spec_payments = $payments->where('contract_specification_id', $spec->id);
                                                $spec_keys = $license_keys->where('contract_specification_id', $spec->id);
                                                $spec_status = \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::tryFrom((string) $spec->status)?->data();
                                            @endphp
                                            <tr @class(['opacity-75' => $spec->is_canceled])>
                                                <td class="ps-3">
                                                    <a href="javascript:box({href: '{{ route('contract_spec.box_edit', $spec->id) }}'})"
                                                       class="fw-semibold text-gray-900 text-hover-primary fs-6">
                                                        {{ $spec->name ?: 'без названия' }}
                                                    </a>
                                                    @if($spec->closed_at)
                                                        <div class="fs-7 text-muted">закрыта {{ $spec->closed_at->format('d.m.Y') }}</div>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @if($spec_status)
                                                            <span class="badge badge-light-{{ $spec_status['color'] }} fs-7">
                                                                {{ $spec_status['label'] }}
                                                            </span>
                                                        @endif

                                                        @if(!$spec->is_canceled)
                                                            <span class="badge badge-light-{{ $spec->is_signed ? 'success' : 'warning' }} fs-7">
                                                                {{ $spec->is_signed ? 'Подписана' : 'Не подписана' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td class="text-end text-nowrap">
                                                    <span class="fs-5 fw-semibold @if($spec->is_canceled) text-decoration-line-through text-muted @endif">
                                                        {{ tools()->cost_normalize(round((float) $spec->amount)) }}
                                                    </span>
                                                    <span class="text-muted fs-8 ms-1">{{ $spec->currency_slug }}</span>
                                                </td>

                                                <td class="text-end">
                                                    @if($spec_payments->isEmpty())
                                                        <span class="badge badge-light-{{ $spec->is_canceled ? 'secondary' : 'danger' }} fs-7">нет платежей</span>
                                                    @else
                                                        <div class="d-flex flex-wrap justify-content-end gap-1">
                                                            @foreach($spec_payments as $payment)
                                                                @php
                                                                    $color = match($payment->state) {
                                                                        'paid' => 'success',
                                                                        'overdue' => 'danger',
                                                                        'unknown' => 'secondary',
                                                                        'canceled' => 'dark',
                                                                        default => 'info',
                                                                    };
                                                                @endphp
                                                                <span class="badge badge-light-{{ $color }} fs-7"
                                                                      title="{{ $payment->state === 'paid'
                                                                            ? 'Оплачен ' . $payment->date_fact?->format('d.m.Y')
                                                                            : 'План ' . ($payment->date_plan?->format('d.m.Y') ?: 'без даты') }}">
                                                                    {{ tools()->cost_normalize(round((float) ($payment->date_fact ? $payment->amount_fact : $payment->amount_plan))) }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="text-end pe-3">
                                                    @if($spec_keys->isEmpty())
                                                        <span class="text-muted">—</span>
                                                    @else
                                                        @foreach($spec_keys as $key)
                                                            <div class="fs-8 text-nowrap">
                                                                <span class="badge badge-light-{{ $key->days_left < 0 ? 'dark' : ($key->days_left <= 30 ? 'danger' : 'info') }} fs-7">
                                                                    до {{ $key->active_to?->format('d.m.Y') }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Сделки Битрикса --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-1">Сделки Битрикс24</h4>
                    <span class="text-muted fs-7">
                        Привязано: {{ $deal_links->count() }}. Сумма сделок сверяется с последним вариантом КП
                        без пересчёта валюты.
                    </span>
                </div>

                <div class="card-toolbar">
                    <a href="javascript:box({href: '{{ route('proposal.box_deal', [$proposal, $proposal->iteration]) }}'})"
                       class="btn btn-sm btn-light-primary">
                        <i class="fa-light fa-link fs-5 me-2"></i>Привязка сделок
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                @if($deal_links->isEmpty())
                    <div class="text-center text-muted py-10">Сделка Битрикса не привязана</div>
                @else
                    @if($deal_check['has_errors'])
                        <div class="alert alert-danger d-flex align-items-center m-5">
                            <i class="fa-light fa-scale-unbalanced fs-2 me-4"></i>
                            <div class="fs-7">
                                Сделки в CRM не совпадают с последним вариантом КП:
                                в Битриксе <b>{{ tools()->cost_normalize(round($deal_check['deals_amount'])) }}</b>,
                                в КП <b>{{ tools()->cost_normalize(round($deal_check['amount'])) }} {{ $deal_check['currency'] }}</b>.
                                Подробности — в строках ниже.
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                            <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-5" width="120">СДЕЛКА</th>
                                <th>НАЗВАНИЕ И РАСХОЖДЕНИЯ</th>
                                <th width="200">СТАДИЯ</th>
                                <th width="180">ОТВЕТСТВЕННЫЙ</th>
                                <th class="text-end pe-5" width="170">СУММА</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($deal_links as $link)
                                @php $errors = $deal_check['errors'][$link->crm_deal_id] ?? []; @endphp
                                <tr @class(['bg-light-danger' => !empty($errors)])>
                                    <td class="ps-5">
                                        @if($link->is_main)
                                            <span class="badge badge-light-success fs-6">#{{ $link->crm_deal_id }}</span>
                                        @else
                                            <span class="fw-bold">#{{ $link->crm_deal_id }}</span>
                                        @endif

                                        @if(!empty($errors))
                                            <div class="fs-8 text-danger mt-1">
                                                <i class="fa-light fa-triangle-exclamation fs-8 me-1"></i>ошибка
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            @if(!empty($link->deal?->title))
                                                <span class="fw-bold">{{ $link->deal?->title }}</span>
                                            @else
                                                Сделки нет в выгрузке Битрикса
                                            @endif
                                        </div>

                                        @if($link->deal?->company_name)
                                            <div class="fs-8 text-muted">{{ $link->deal->company_name }}</div>
                                        @endif

                                        @foreach($errors as $error)
                                            <div class="fs-8 text-danger">{{ $error }}</div>
                                        @endforeach
                                    </td>
                                    <td class="fs-6 fw-bold">{{ $link->deal?->stage_name ?: '—' }}</td>
                                    <td class="fs-6 fw-bold">
                                        {{ $link->deal?->manager ?: ($link->deal?->assigned_by ?: '—') }}
                                    </td>
                                    <td class="text-end pe-5 text-nowrap">
                                        @if($link->deal?->opportunity)
                                            <span @class(['fs-5 fw-semibold ', 'text-danger' => !empty($errors)])>
                                                {{ tools()->cost_normalize(round($link->deal->opportunity)) }}
                                            </span>
                                            <span class="text-muted fs-8">{{ $link->deal->currency_id }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>

                            <tfoot>
                            <tr class="fw-bold border-top border-gray-300">
                                <td class="ps-5" colspan="4">
                                    <span class="fs-4">ИТОГО по сделкам</span>
                                    <div class="fs-7 fw-normal text-muted">
                                        Последний вариант КП:
                                        {{ tools()->cost_normalize(round($deal_check['amount'])) }} {{ $deal_check['currency'] }}
                                    </div>
                                </td>
                                <td class="text-end pe-5 text-nowrap text-{{ $deal_check['has_errors'] ? 'danger' : 'success' }}">
                                    <span class="fs-4">{{ tools()->cost_normalize(round($deal_check['deals_amount'])) }}</span>
                                    @if(abs($deal_check['diff']) > 1)
                                        <div class="fs-8 fw-normal">
                                            {{ $deal_check['diff'] > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($deal_check['diff'])) }}
                                            к КП
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Итерации КП --}}
        @if($iterations->count() > 1)
            <div class="card">
                <div class="card-header min-h-auto py-5 border-bottom">
                    <div class="card-title flex-column align-items-start">
                        <h4 class="fw-bold mb-0">Редакции КП</h4>
                    </div>
                    <div class="card-toolbar">
                        <a href="{{ route('proposal_tools.price_history', $proposal) }}" class="btn btn-sm btn-light">
                            <i class="fa-light fa-chart-line fs-5 me-2"></i>Как менялась цена
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                            <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-5" width="100">Редакция</th>
                                <th>Название</th>
                                <th width="140">Отправлено</th>
                                <th class="text-end pe-5">Сумма</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($iterations as $item)
                                <tr>
                                    <td class="ps-5">
                                        <a href="{{ route('proposal.detail', [$item, $item->iteration]) }}" class="fw-bold">
                                            #{{ $item->iteration }}
                                        </a>
                                    </td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->sended_at?->format('d.m.Y') ?: '—' }}</td>
                                    <td class="text-end pe-5">{{ tools()->cost_normalize(round($item->cost_total)) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
