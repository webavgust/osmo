@extends('layouts.layout')

@section('styles')
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-lg-3">
                <div class="card">
                    <div class="card-body d-flex justify-content-between">
                        <h4 class="card-title mb-0">Информация о договоре</h4>
                    </div>
                    <div class="card-body">
                        <div class="card-table">
                            <x-ui.card.card_table_tr field="Номер" value="{{ $contract->id }}"
                                                     link="{{ env('PORTAL_URL') }}/projects/orders/{{ $contract->id }}/"></x-ui.card.card_table_tr>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-9">
                <div class="card">
                    <div class="card-body d-flex justify-con    tent-between">
                        <h4 class="card-title mb-0">Версии договоров и ТЗ</h4>
                    </div>


                    @foreach($contract->sub_contracts as $sub_contract)
                        @if(!empty($filter_sub_contract) && $sub_contract->id !== $filter_sub_contract)
                            @continue
                        @endif
                        <div class="card-body d-flex justify-content-between pb-3">
                            <h5 class="card-title mb-0">
                                <a href="{{ route('sub_contract.detail', [$contract, $sub_contract])  }}">{{ $sub_contract->slug }}</a>
                            </h5>
                        </div>

                        <div class="card-body p-0">
                            <div class="agree-table table-responsive">
                                <table class="table stylish-table v-middle mb-0 no-wrap">
                                    <thead>
                                    <tr>
                                        <th class="border-0 text-muted fw-normal" style="width: 1px">ID</th>
                                        <th class="border-0 text-muted fw-normal" style="width: 1px">Блок</th>
                                        <th class="border-0 text-muted fw-normal">Дата создания</th>
                                        <th class="border-0 text-muted fw-normal">Автор</th>
                                        <th class="border-0 text-muted fw-normal" style="width: 1px">Версий</th>
                                        <th class="border-0 text-muted fw-normal" style="width: 1px">Статус</th>
                                        <th class="border-0 text-muted fw-normal" style="width: 1px"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($sub_contract->order_tasks()->orderBy('iteration', 'asc')->get()->keyBy('block_id') as $order_task)
                                        <tr>
                                            <td>
                                                <x-order_task.cell.id :task="$order_task"></x-order_task.cell.id>
                                            </td>
                                            <td>
                                                @can('order_task_view')
                                                    <a href="{{ route('order_task.detail', $order_task->block_id) }}">{{ $order_task->block_id }}</a>
                                                @else
                                                    {{ $order_task->block_id }}
                                                @endcan
                                            </td>
                                            <td>
                                                {{ _date($order_task->created_at) }}
                                            </td>
                                            <td>
                                                <x-user.table_card :user="$order_task->creator"></x-user.table_card>
                                            </td>
                                            <td class="text-center">
                                                {{ $order_task->iteration }}
                                            </td>
                                            <td>
                                                <x-order_task.status :order_task="$order_task"
                                                                     font="14"></x-order_task.status>
                                            </td>
                                            <td>
                                                <x-order_task.actions :order_task="$order_task"></x-order_task.actions>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
@endsection
