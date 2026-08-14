@extends('layouts.layout')

@section('breadcrumb_right')
    <x-ui.a.default btn_type="info" href="{{ route('partner.edit', $partner) }}">
        Редактировать
    </x-ui.a.default>
@endsection

@section('content')
    <style>
        #payments[mode='summary'] .payment_pad[mode='table'],
        #payments[mode='table'] .payment_pad[mode='summary'] { display: none }

        .key_row { border-left: 7px solid rgba(0, 0, 0, 0); border-left-width: 4px!important; }
        .key_edit {  opacity: 0 }
        .key_row:hover .key_edit { opacity: .3 }
        .key_row:hover { border-left-color: #1e88e5!important }
        .key_edit:hover { opacity: 1!important }

        tr[status='canceled'] {color: #ffb4b4; background: #ffefef;}
        tr[status='canceled'] .card-table .tr {border-bottom-color: #ffb4b4;}
        tr[status='canceled'] .card-table .tr span {color: #ffb4b4; background: #ffefef;}
    </style>


    <div class="container-fluid">
        <div class="row">
            <div class="col-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="m-0">Общая информация</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="card-table m-4">
                            <x-ui.card.card_table_tr field="Название">{{ $partner->name }}</x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Тип партнёра">
                                @php $partner_data = \App\Modules\Pub\Partner\Models\PartnerGrade::from($partner->grade)->data(); @endphp
                                <span style="color: {{ $partner_data['color']['medal'] }}">
                                    <x-ui.icon.solid icon="fa-medal" class="me-1"/>
                                    {{ $partner_data['label'] }}
                                </span>
                            </x-ui.card.card_table_tr>

                            <x-ui.card.card_table_tr field="Тип">
                                @php $partner_type = \App\Modules\Pub\Partner\Models\PartnerType::from($partner->type)->data(); @endphp
                                {{ $partner_type['description'] }}
                            </x-ui.card.card_table_tr>

                            <x-ui.card.card_table_tr field="Регион">{{ $partner->region ?? '-' }}</x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Контактное лицо">{{ $partner->contact ?? '-' }}</x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Телефон">{{ $partner->phone ?? '-' }}</x-ui.card.card_table_tr>


{{--                            <div class="mt-4 mb-2 fs-4 fw-bold">Финансовые показатели</div>--}}
{{--                            <x-ui.card.card_table_tr field="Оплаты (полученные)">--}}
{{--                                @if($partner->payments['past']->isNotEmpty())--}}
{{--                                    <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('payment.box_past', $partner) }}'})" class="mt-1 ms-1">--}}
{{--                                        {{ $partner->payments['past']->count() }} шт.--}}
{{--                                        на {{ tools()->cost_normalize($partner->payments['past']->sum('amount_fact')) }} ₽</a>--}}
{{--                                @else--}}
{{--                                    нет--}}
{{--                                @endif--}}
{{--                            </x-ui.card.card_table_tr>--}}
{{--                            <x-ui.card.card_table_tr field="Оплаты (будущие)">--}}
{{--                                @if($partner->payments['future']->isNotEmpty())--}}
{{--                                    <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('payment.box_future', $partner) }}'})" class="mt-1 ms-1">--}}
{{--                                        {{ $partner->payments['future']->count() }} шт.--}}
{{--                                        на {{ tools()->cost_normalize($partner->payments['future']->sum('amount_plan')) }} ₽</a>--}}
{{--                                @else--}}
{{--                                    нет--}}
{{--                                @endif--}}
{{--                            </x-ui.card.card_table_tr>--}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-9" id="payments" mode="summary">

                <div class="d-flex justify-content-between mb-2">
                    <h2>Договоры</h2>

                    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                        <x-ui.a.box btn_type="success" href="{{ route('contract.box_add', $partner) }}" class=" ms-2">
                            <x-ui.icon.regular icon="fa-plus-circle"/>
                        </x-ui.a.box>
                    </div>
                </div>


                <div class="table-responsive bg-white">
                    <table class="table table-bordered">
                        <tr>
                            <th>Название</th>
                            <th width="100">Подписано</th>
                            <th width="100" class="text-center">Информация</th>
                            <th>Оплаты</th>
                            <th width="130" class="text-end">Сумма</th>
                            <th width="1" class="p-0"></th>
                        </tr>
                        @foreach($partner->contracts as $contract)
                            @php
                                $type_decorate = \App\Modules\Pub\Contract\Models\ContractType::from($contract->type)->data();
                            @endphp
                            <tr style="background: #e5eeff">
                                <td>
                                     <span class="fw-bold text-{{ $type_decorate['color'] }} fs-5">
                                        <x-ui.icon.regular :icon="$type_decorate['icon']" class="me-1 fs-5"/>
                                        {{ $type_decorate['label'] }}
                                    </span>
                                    <div class="fs-1  text-secondary" style="margin-left: 29px;">{{ $contract->organization->name }}</div>
                                </td>
                                <td class="text-center">
                                    @if($contract->cb_signed)
                                        <x-ui.icon.regular icon="fa-check" class="text-success"/>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!empty($contract->number))
                                        <x-ui.badge.light type="secondary">{{ $contract->number ?? '-'}}</x-ui.badge.light>
                                        <div class="fs-1">{{ $contract->date?->format("d.m.Y") ?? '-' }}</div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold fs-4" colspan="2">


                                    @php
                                        $amounts = $contract->companyAmountByCurrencies();
                                    @endphp
                                    @foreach($amounts as $amount_once)
                                        <div>{{ tools()->cost_normalize($amount_once['amount']) }} {{ $amount_once['currency']->symbol }}</div>
                                    @endforeach
                                </td>
                                <td class="px-2 text-center text-nowrap">
                                    <a class="me-2" href="javascript:void(0)" onclick="javascript:box({href:'{{ route('contract.box_edit', $contract) }}'})">
                                        <x-ui.icon.regular icon="fa-edit"/>
                                    </a>


                                    <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('contract_spec.box_add', $contract) }}'})">
                                        <x-ui.icon.regular icon="fa-plus"/>
                                    </a>

                                </td>
                            </tr>

                            @if(!$contract->contract_specifications->isEmpty())
                                @foreach($contract->contract_specifications->groupBy('company_id') as $company_id => $specs)
                                    @php
                                        $company = \App\Modules\Pub\Company\Models\Company::find($company_id)
                                    @endphp

                                    <tr style="background: #f1f1f1">
                                        <td colspan="4" class="fs-5">
                                            <div class="d-flex justify-content-between">
                                                @if(!empty($company))
                                                    <a href="{{ route('company.detail', $company) }}" class=" text-secondary">
                                                        <x-ui.icon.light icon="fa-building" class="me-1"/>
                                                        {{ $company->name }}
                                                    </a>
                                                @else
                                                    Компания не указана
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold fs-3">

                                            @php
                                                $amounts = $contract->amountByCurrencies($company);
                                            @endphp
                                            @foreach($amounts as $amount_once)
                                                <div>{{ tools()->cost_normalize($amount_once['amount']) }} {{ $amount_once['currency']->symbol }}</div>
                                            @endforeach
                                        </td>
                                        <td/>

                                    </tr>

                                    @foreach($specs as $spec_i => $spec)
                                        @php
                                            $status = \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::from($spec->status);
                                            $status_data = $status->data();
                                        @endphp
                                        <tr status="{{ $status }}">
                                            <td>
                                                <div class="ps-4">
                                                        {{ $spec->name }}
                                                </div>

                                                {{-- дата спецификации и прикреплённые КП (patch v16) --}}
                                                <div class="ps-4 fs-1 text-secondary">
                                                    {{ ($spec->date_create ?? $contract->date)?->format("d.m.Y") ?? 'без даты' }}

                                                    @php $spec_proposals = \App\Modules\Pub\ContractSpecification\Services\SpecProposalService::attached($spec); @endphp
                                                    @foreach($spec_proposals as $spec_proposal)
                                                        <a href="{{ route('proposal.detail', [$spec_proposal, $spec_proposal->iteration]) }}"
                                                           class="ms-2" title="Прикреплённое КП: {{ $spec_proposal->name }}">
                                                            <x-ui.icon.regular icon="fa-file-lines" class="me-1"/>{{ $spec_proposal->number ?: 'б/н' }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($spec->is_signed)
                                                    <x-ui.icon.regular icon="fa-check" class="text-success"/>
                                                @endif
                                            </td>
                                            <td class="text-center">

                                                @if(!empty($contract->number))
                                                    <x-ui.badge.light :type="$status_data['color']">{{ $status_data['label'] }}</x-ui.badge.light>

                                                    @if($spec->contract_specification_scenarios->isNotEmpty())

                                                        <div class="fs-1" data-container="body" data-bs-container="body" data-bs-toggle="popover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{     $scenarioNames = $spec->contract_specification_scenarios
                                                        ->map(fn($css) => '- ' . ($css->scenario?->name ?? $css->name))
                                                        ->implode('<br>') }}">
                                                            {{ tools()->num_rus($spec->contract_specification_scenarios->count(), ['сценария', 'сценарий', 'сценариев'], true) }}
                                                        </div>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="px-0">
                                                @if($spec->payments->isNotEmpty())
                                                    <div class="card-table m-3 ms-4 mt-0">
                                                        @php
                                                            $amount = 0;
                                                        @endphp
                                                        @foreach($spec->payments as $payment)
                                                            @php
                                                                $amount += $payment['amount_fact'];
                                                            @endphp
                                                            <div class="tr">
                                                                <span class="th align-items-center">
                                                                    <span class="me-1 text-center text-{{ $payment->status['color'] }}" style="width: 20px">
                                                                        <x-ui.icon.solid icon="{{ $payment->status['icon'] }}" class=""/>
                                                                    </span>


                                                                    @if($payment->is_unknown)
                                                                        (неизвестно)
                                                                    @endif

                                                                    @if(!empty($payment->date_plan))
                                                                        {{ $payment->date_plan?->format("d.m.Y") ?? '-'}}
                                                                    @endif
                                                                    @if(!empty($payment->date_fact) && !$payment->date_fact->isSameDay($payment->date_plan))
                                                                        @if(!empty($payment->date_plan))
                                                                            <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>
                                                                        @endif

                                                                        {{ $payment->date_fact->format("d.m.Y") }}

                                                                        @if(!empty($payment->delay))
                                                                            (+ {{ tools()->num_rus($payment->delay, ['дня', 'день', 'дней'], true) }})
                                                                        @endif
                                                                    @endif
                                                                </span>
                                                                <span class="td">
                                                                    @if(!empty($payment->amount_plan))
                                                                        {{ $payment->amount_plan ? tools()->cost_normalize($payment->amount_plan) : '?' }} {{ $spec->currency->symbol }}
                                                                    @endif

                                                                    @if(!empty($payment->amount_fact) && $payment->amount_plan !== $payment->amount_fact)
                                                                        @if(!empty($payment->amount_plan))
                                                                            <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>
                                                                        @endif

                                                                        {{ tools()->cost_normalize($payment->amount_fact) }} {{ $spec->currency->symbol }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                {{ tools()->cost_normalize($spec->amount_all) }} {{ $spec->currency->symbol }}
                                            </td>
                                            <td class="px-2 text-center text-nowrap">
                                                <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('contract_spec.box_edit', $spec) }}'})">
                                                    <x-ui.icon.regular icon="fa-edit"/>
                                                </a>
                                                <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('contract_spec.box_proposal', $spec) }}'})" class="ms-2" title="Прикрепление КП">
                                                    <x-ui.icon.regular icon="fa-link"/>
                                                </a>
                                                <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('payment.box_control', $spec) }}'})" class="ms-2" title="Управление оплатами">
                                                    <x-ui.icon.regular icon="fa-coins"/>
                                                </a>
                                            </td>
                                        </tr>


                                        @if($spec->payments->isNotEmpty() && 0)
                                            @foreach($spec->payments as $payment)
                                                <tr>
                                                    <td>
                                                        <span class="me-1 text-center text-{{ $payment->status['color'] }}" style="width: 20px">
                                                            <x-ui.icon.solid icon="{{ $payment->status['icon'] }}" class=""/>
                                                        </span>

                                                        @if(!empty($payment->date_plan))
                                                            {{ $payment->date_plan?->format("d.m.Y") ?? '-'}}
                                                        @endif
                                                        @if(!empty($payment->date_fact) && !$payment->date_fact->isSameDay($payment->date_plan))

                                                            @if(!empty($payment->date_plan))
                                                                <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>
                                                            @endif

                                                            {{ $payment->date_fact->format("d.m.Y") }}

                                                            @if(!empty($payment->delay))
                                                                (+ {{ tools()->num_rus($payment->delay, ['дня', 'день', 'дней'], true) }})
                                                            @endif
                                                        @endif

                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endforeach
                            @endif


                        @endforeach
                    </table>
                </div>



{{--                <div class="card mt-5">--}}
{{--                    <div class="card-header d-flex justify-content-between align-items-center pe-2">--}}
{{--                        <h3 class="m-0">Договоры</h3>--}}

{{--                        @if($partner->companies->isNotEmpty())--}}
{{--                            <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">--}}
{{--                                <x-ui.a.box btn_type="success" href="{{ route('contract.box_add', $partner) }}" class=" ms-2">--}}
{{--                                    <x-ui.icon.regular icon="fa-plus-circle"/>--}}
{{--                                </x-ui.a.box>--}}
{{--                            </div>--}}
{{--                        @endif--}}
{{--                    </div>--}}

{{--                    <div class="payment_pad" mode="summary">--}}
{{--                        @if($partner->contracts->isEmpty())--}}
{{--                            <div class="p-3">--}}
{{--                                Пока нет добавленных договоров--}}
{{--                            </div>--}}
{{--                        @else--}}
{{--                            @foreach($partner->contracts as $contract)--}}
{{--                                @php--}}
{{--                                    $type_decorate = \App\Modules\Pub\Contract\Models\ContractType::from($contract->type)->data();--}}
{{--                                @endphp--}}
{{--                                <div class="card-body p-0">--}}
{{--                                    @if(0 && $contract->old)--}}
{{--                                        <div class="m-1">--}}
{{--                                            <mark>--}}
{{--                                                <code>OLD</code>--}}
{{--                                            </mark>--}}
{{--                                        </div>--}}

{{--                                        <div class="p-3 pb-2 d-flex justify-content-between align-items-center">--}}
{{--                                            <div class="d-flex flex-grow-1">--}}
{{--                                                <span class="fw-bold text-{{ $type_decorate['color'] }} fs-5">--}}
{{--                                                    <x-ui.icon.regular :icon="$type_decorate['icon']" class="me-1 fs-5"/>--}}
{{--                                                    {{ $type_decorate['label'] }}--}}

{{--                                                    @if($contract->cb_signed)--}}
{{--                                                        <x-ui.badge.default type="success">Подписано</x-ui.badge.default>--}}
{{--                                                    @endif--}}
{{--                                                </span>--}}

{{--                                                @if(!empty($contract->number))--}}
{{--                                                    <div class="d-flex justify-content-end ms-2">--}}
{{--                                                    <span class="text-center">--}}
{{--                                                        <x-ui.badge.light type="secondary">{{ $contract->number ?? '-'}}</x-ui.badge.light>--}}
{{--                                                        <div class="fs-1">{{ $contract->date?->format("d.m.Y") ?? '-' }}</div>--}}
{{--                                                    </span>--}}
{{--                                                    </div>--}}
{{--                                                @endif--}}
{{--                                            </div>--}}


{{--                                            <div class="d-flex justify-content-end">--}}
{{--                                                <span class="text-center">--}}
{{--                                                    <x-ui.badge.default type="{{ $type_decorate['color'] }}">{{ tools()->cost_normalize($contract->amount) }} ₽</x-ui.badge.default>--}}

{{--                                                </span>--}}

{{--                                                <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('contract.box_edit', $contract) }}'})" class="ms-2">--}}
{{--                                                    <x-ui.icon.regular icon="fa-edit"/>--}}
{{--                                                </a>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}


{{--                                        @if(!$contract->contract_specifications->isEmpty())--}}
{{--                                            @foreach($contract->contract_specifications as $spec_i => $spec)--}}
{{--                                                <div class="ms-2 ps-5 pe-3 pb-2 d-flex justify-content-between align-items-center">--}}
{{--                                                        <span>--}}
{{--                                                            <x-ui.icon.solid icon="fa-circle-dot" class="me-1"/>--}}
{{--                                                            <span class="fw-bold">{{ $spec_i + 1 }}) {{ $spec->name }}</span>--}}

{{--                                                            @if(!empty($spec->closed_at))--}}
{{--                                                                <span class="text-danger ">--}}
{{--                                                                    <x-ui.icon.solid icon="fa-lock" class="ms-2"/>--}}
{{--                                                                    {{ $spec->closed_at->format("d.m.Y") }}--}}
{{--                                                                </span>--}}
{{--                                                            @endif--}}


{{--                                                            @if($spec->contract_specification_scenarios->isNotEmpty())--}}
{{--                                                                + {{ tools()->num_rus($spec->contract_specification_scenarios->count(), ['сценария', 'сценарий', 'сценариев'], true) }}--}}
{{--                                                            @endif--}}
{{--                                                        </span>--}}

{{--                                                    <div class="d-flex justify-content-end">--}}
{{--                                                            <span class="text-center">--}}
{{--                                                                <x-ui.badge.light type="secondary">--}}
{{--                                                                    {{ tools()->cost_normalize($spec->amount) }} ₽--}}
{{--                                                                </x-ui.badge.light>--}}
{{--                                                            </span>--}}

{{--                                                        <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('contract_spec.box_edit', $spec) }}'})" class="ms-2">--}}
{{--                                                            <x-ui.icon.regular icon="fa-edit"/>--}}
{{--                                                        </a>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}


{{--                                                @if($spec->payments->isNotEmpty())--}}
{{--                                                    <div class="card-table m-3 ms-5 ps-5 mt-0">--}}
{{--                                                        @foreach($spec->payments as $payment)--}}
{{--                                                            <div class="tr">--}}
{{--                                                                <span class="th align-items-center">--}}
{{--                                                                    <span class="me-1 text-center text-{{ $payment->status['color'] }}" style="width: 20px">--}}
{{--                                                                        <x-ui.icon.solid icon="{{ $payment->status['icon'] }}" class=""/>--}}
{{--                                                                    </span>--}}

{{--                                                                    @if(!empty($payment->date_plan))--}}
{{--                                                                        {{ $payment->date_plan?->format("d.m.Y") ?? '-'}}--}}
{{--                                                                    @endif--}}
{{--                                                                    @if(!empty($payment->date_fact) && !$payment->date_fact->isSameDay($payment->date_plan))--}}

{{--                                                                        @if(!empty($payment->date_plan))--}}
{{--                                                                            <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>--}}
{{--                                                                        @endif--}}

{{--                                                                        {{ $payment->date_fact->format("d.m.Y") }}--}}

{{--                                                                        @if(!empty($payment->delay))--}}
{{--                                                                            (+ {{ tools()->num_rus($payment->delay, ['дня', 'день', 'дней'], true) }})--}}
{{--                                                                        @endif--}}
{{--                                                                    @endif--}}
{{--                                                                </span>--}}
{{--                                                                <span class="td">--}}
{{--                                                                    @if(!empty($payment->amount_plan))--}}
{{--                                                                        {{ $payment->amount_plan ? tools()->cost_normalize($payment->amount_plan) : '?' }} ₽--}}
{{--                                                                    @endif--}}

{{--                                                                    @if(!empty($payment->amount_fact) && $payment->amount_plan !== $payment->amount_fact)--}}
{{--                                                                        @if(!empty($payment->amount_plan))--}}
{{--                                                                            <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>--}}
{{--                                                                        @endif--}}

{{--                                                                        {{ tools()->cost_normalize($payment->amount_fact) }} ₽--}}
{{--                                                                    @endif--}}
{{--                                                                </span>--}}
{{--                                                            </div>--}}
{{--                                                        @endforeach--}}
{{--                                                    </div>--}}
{{--                                                @endif--}}

{{--                                                <div class="d-flex justify-content-end ps-5 pe-3 pb-4">--}}
{{--                                                    <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('payment.box_control', $spec) }}'})" class="ms-2">--}}
{{--                                                        <x-ui.icon.regular icon="fa-edit"/> Управлять платежами--}}
{{--                                                    </a>--}}
{{--                                                </div>--}}
{{--                                            @endforeach--}}
{{--                                        @endif--}}
{{--                                    @else--}}
{{--                                        @if($contract->old)--}}
{{--                                            <div class="m-1">--}}
{{--                                                <mark>--}}
{{--                                                    <code>OLD</code>--}}
{{--                                                </mark>--}}
{{--                                            </div>--}}
{{--                                        @endif--}}
{{--                                        <div class="p-3 pb-2 d-flex justify-content-between align-items-center">--}}
{{--                                            <div class="d-flex flex-grow-1">--}}
{{--                                                <span class="fw-bold text-{{ $type_decorate['color'] }} fs-5">--}}
{{--                                                    <x-ui.icon.regular :icon="$type_decorate['icon']" class="me-1 fs-5"/>--}}
{{--                                                    {{ $type_decorate['label'] }}--}}
{{--                                                    [{{ $contract->id }}]--}}

{{--                                                    @if($contract->cb_signed)--}}
{{--                                                        <x-ui.badge.default type="success">Подписано</x-ui.badge.default>--}}
{{--                                                    @endif--}}
{{--                                                </span>--}}

{{--                                                @if(!empty($contract->number))--}}
{{--                                                    <div class="d-flex justify-content-end ms-2">--}}
{{--                                                    <span class="text-center">--}}
{{--                                                        <x-ui.badge.light type="secondary">{{ $contract->number ?? '-'}}</x-ui.badge.light>--}}
{{--                                                        <div class="fs-1">{{ $contract->date?->format("d.m.Y") ?? '-' }}</div>--}}
{{--                                                    </span>--}}
{{--                                                    </div>--}}
{{--                                                @endif--}}
{{--                                            </div>--}}


{{--                                            <div class="d-flex justify-content-end">--}}
{{--                                                <span class="text-center">--}}
{{--                                                    <x-ui.badge.default type="{{ $type_decorate['color'] }}">{{ tools()->cost_normalize($contract->amount) }} ₽</x-ui.badge.default>--}}
{{--                                                    <div class="fs-1">{{ $contract->organization->name }}</div>--}}
{{--                                                </span>--}}

{{--                                                <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('contract.box_edit', $contract) }}'})" class="ms-2">--}}
{{--                                                    <x-ui.icon.regular icon="fa-edit"/>--}}
{{--                                                </a>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}


{{--                                        @if(!$contract->contract_specifications->isEmpty())--}}
{{--                                            @foreach($contract->contract_specifications->groupBy('company_id') as $company_id => $specs)--}}
{{--                                                @php--}}
{{--                                                    $company = \App\Modules\Pub\Company\Models\Company::find($company_id)--}}
{{--                                                @endphp--}}

{{--                                                <div class="ms-5 pe-3 pb-2 ">--}}
{{--                                                    <h3>--}}
{{--                                                        <a href="{{ route('company.detail', $company) }}">--}}
{{--                                                            <x-ui.icon.regular icon="fa-building" class="fs-4 me-1"/>--}}
{{--                                                            {{ $company->name }}--}}
{{--                                                            [{{ $company->id }}]--}}
{{--                                                        </a>--}}
{{--                                                    </h3>--}}

{{--                                                    <div class=" ps-3 mt-3 border-start border-4">--}}
{{--                                                        @foreach($specs as $spec_i => $spec)--}}
{{--                                                        <div class="pb-2 d-flex justify-content-between align-items-center">--}}
{{--                                                                <span class="d-inline-flex align-items-center justify-content-start">--}}
{{--                                                                    <span class="fw-bold">{{ $spec_i + 1 }}) {{ $spec->name }}</span>--}}

{{--                                                                    @if($spec->is_signed)--}}
{{--                                                                        <x-ui.badge.default type="success" class="text-white p-1  fs-2 ms-2 mb-1">--}}
{{--                                                                            <x-ui.icon.solid icon="fa-badge-check" class="me-1"/>--}}
{{--                                                                            Подписано--}}
{{--                                                                        </x-ui.badge.default>--}}
{{--                                                                    @endif--}}

{{--                                                                    @if(!empty($spec->closed_at))--}}
{{--                                                                        <span class="text-danger ">--}}
{{--                                                                            <x-ui.icon.solid icon="fa-lock" class="ms-2"/>--}}
{{--                                                                            {{ $spec->closed_at->format("d.m.Y") }}--}}
{{--                                                                        </span>--}}
{{--                                                                    @endif--}}


{{--                                                                    @if($spec->contract_specification_scenarios->isNotEmpty())--}}
{{--                                                                        + {{ tools()->num_rus($spec->contract_specification_scenarios->count(), ['сценария', 'сценарий', 'сценариев'], true) }}--}}
{{--                                                                    @endif--}}
{{--                                                                </span>--}}

{{--                                                                <div class="d-flex justify-content-end">--}}
{{--                                                                    @php--}}
{{--                                                                        $status = \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::from($spec->status)->data();--}}
{{--                                                                    @endphp--}}
{{--                                                                    <span class="text-center me-1">--}}
{{--                                                                        <x-ui.badge.light :type="$status['color']">--}}
{{--                                                                            {{ $status['label'] }}--}}
{{--                                                                        </x-ui.badge.light>--}}
{{--                                                                    </span>--}}

{{--                                                                    <span class="text-center">--}}
{{--                                                                        <x-ui.badge.light type="secondary">--}}
{{--                                                                            {{ tools()->cost_normalize($spec->amount) }} ₽--}}
{{--                                                                        </x-ui.badge.light>--}}
{{--                                                                    </span>--}}


{{--                                                                </div>--}}
{{--                                                            </div>--}}


{{--                                                            @if($spec->payments->isNotEmpty())--}}
{{--                                                                <div class="card-table m-3 ms-4 mt-0">--}}
{{--                                                                    @foreach($spec->payments as $payment)--}}
{{--                                                                        <div class="tr">--}}
{{--                                                                            <span class="th align-items-center">--}}
{{--                                                                                <span class="me-1 text-center text-{{ $payment->status['color'] }}" style="width: 20px">--}}
{{--                                                                                    <x-ui.icon.solid icon="{{ $payment->status['icon'] }}" class=""/>--}}
{{--                                                                                </span>--}}

{{--                                                                                @if(!empty($payment->date_plan))--}}
{{--                                                                                    {{ $payment->date_plan?->format("d.m.Y") ?? '-'}}--}}
{{--                                                                                @endif--}}
{{--                                                                                @if(!empty($payment->date_fact) && !$payment->date_fact->isSameDay($payment->date_plan))--}}

{{--                                                                                    @if(!empty($payment->date_plan))--}}
{{--                                                                                        <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>--}}
{{--                                                                                    @endif--}}

{{--                                                                                    {{ $payment->date_fact->format("d.m.Y") }}--}}

{{--                                                                                    @if(!empty($payment->delay))--}}
{{--                                                                                        (+ {{ tools()->num_rus($payment->delay, ['дня', 'день', 'дней'], true) }})--}}
{{--                                                                                    @endif--}}
{{--                                                                                @endif--}}
{{--                                                                            </span>--}}
{{--                                                                            <span class="td">--}}
{{--                                                                                @if(!empty($payment->amount_plan))--}}
{{--                                                                                    {{ $payment->amount_plan ? tools()->cost_normalize($payment->amount_plan) : '?' }} ₽--}}
{{--                                                                                @endif--}}

{{--                                                                                @if(!empty($payment->amount_fact) && $payment->amount_plan !== $payment->amount_fact)--}}
{{--                                                                                    @if(!empty($payment->amount_plan))--}}
{{--                                                                                        <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>--}}
{{--                                                                                    @endif--}}

{{--                                                                                    {{ tools()->cost_normalize($payment->amount_fact) }} ₽--}}
{{--                                                                                @endif--}}
{{--                                                                            </span>--}}
{{--                                                                        </div>--}}
{{--                                                                    @endforeach--}}
{{--                                                                </div>--}}
{{--                                                            @endif--}}
{{--                                                        @endforeach--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            @endforeach--}}
{{--                                        @endif--}}
{{--                                    @endif--}}



{{--
{{--                                </div>--}}
{{--                            @endforeach--}}
{{--                        @endif--}}
{{--                    </div>--}}
{{--                </div>--}}

            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $("#payments button[mode]").on("click", function() {
                var mode = $(this).attr("mode");

                $("#payments").attr("mode", mode);
                $("#payments").find("button[mode]").addClass("btn-light-info text-info btn-info");
                $("#payments").find("button[mode='" + mode + "']").addClass("btn-info").removeClass("btn-light-info text-info");

            });
        });
    </script>
@endsection
