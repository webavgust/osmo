@extends('components.box.box-static-large')

@section('body')
    <div class="card-body">
        <table class="table table-bordered">
            <tr>
                <th rowspan="2">КП</th>
                <th rowspan="2">Тип</th>
                <th rowspan="2">Спецификация</th>
                <th colspan="2" class="p-1 text-center">Дата</th>
                <th colspan="2" class="p-1 text-center">Оплата</th>
            </tr>
            <tr>
                <th class="p-1 text-center" width="100">План</th>
                <th class="p-1 text-center" width="100">Факт</th>
                <th class="p-1 text-center" width="100">План</th>
                <th class="p-1 text-center" width="100">Факт</th>
            </tr>
            @php
                $contract_count = 0;
                $group_index = 0;
                $amount_plan = $amount_fact = 0;
            @endphp
            @foreach($company->contracts as $contract)
                @php
                    $contractTypeDecorate = \App\Modules\Pub\Contract\Models\ContractType::from($contract->type)->data();
                @endphp
                @if($contract->contract_specifications->isNotEmpty())
                    @foreach($contract->contract_specifications as $spec)
                        @if($spec->payments->isNotEmpty())
                            @php
                                $contract_count++;
                            @endphp
                            @foreach($spec->payments as $payment_index => $payment)
                                @php
                                    $amount_plan += $payment->amount_plan;
                                    $amount_fact += $payment->amount_fact;
                                @endphp
                                <tr style="
                                            @if($contract_count % 2 == 1) background: #F8F8F8; @endif
                                            @if($group_index > 1 && $payment_index == 0) border-top: 2px solid; @endif
                                        ">
                                    <td>
                                        @if(empty($group['proposal']))
                                            Без КП
                                        @else
                                            <a href="{{ route('proposal.detail', [$group['proposal'], $group['proposal']->iteration]) }}">
                                                {{ $group['proposal']->name }}
                                                <sup>{{ $group['proposal']->iteration }}</sup>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-{{ $contractTypeDecorate['color'] }}">{{ $contractTypeDecorate['label'] }}</td>
                                    <td>{{ $spec->name }}</td>
                                    <td class="text-center">{{ $payment->date_plan?->format("d.m.Y") ?? '-' }}</td>
                                    <td class="text-center">{{ $payment->date_fact?->format("d.m.Y") ?? '-' }}</td>
                                    <td class="text-center">
                                        @if(!empty($payment->amount_plan))
                                            {{ tools()->cost_normalize($payment->amount_plan) }} ₽
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(!empty($payment->amount_fact))
                                            {{ tools()->cost_normalize($payment->amount_fact) }} ₽
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                @endif
            @endforeach


            <tr>
                <td colspan="5"/>
                <td class="text-end fw-bold text-nowrap">= {{ tools()->cost_normalize($amount_plan) }} ₽</td>
                <td class="text-end fw-bold text-nowrap">= {{ tools()->cost_normalize($amount_fact) }} ₽</td>
            </tr>

        </table>
    </div>
@endsection
