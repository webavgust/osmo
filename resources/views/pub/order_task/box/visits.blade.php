@extends('components.box.box-static-extralarge')

@section('body')
    <style>
        th.border-left-3, td.border-left-3 {
            border-left-width: 2px;
            border-left-color: #CCC;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="fixed-table-container">
                <div class="fixed-table-header">
                    <table id="visit_table" class="tablesaw table-bordered table-hover table no-wrap tablesaw-sortable tablesaw-swipe m-0">

                        <tr>
                            <th width="100"  class="p-2 font-12 text-center" rowspan="2" data-field="number" data-sortable="true">Номер акта</th>
                            <th class="px-0 py-2 font-12 text-center" colspan="4">Дата</th>
                            <th class="px-0 py-2 font-12 text-start ps-2" rowspan="2">Пробоотборщик(и)</th>
                            <th class="px-0 py-2 font-12 text-start ps-2" rowspan="2">Измерения</th>
                            <th class="px-0 py-2 font-12 text-start ps-2" rowspan="2">Статус</th>
                        </tr>
                        <tr>
                            <th width="100" class="p-2 font-12 text-center">План</th>
                            <th width="100" class="p-2 font-12 text-center">Факт</th>
                            <th width="100" class="p-2 font-12 text-center">Завершено</th>
                            <th width="100" class="p-2 font-12 text-center">Проверено</th>
                        </tr>


                        @foreach($visits as $visit)
                            <tr>
                                <td class="text-center">{{ $visit->number->number }}</td>
                                <td class="text-center">{{ $visit->plan_visit_at?->format("d.m.Y") ?? '-' }}</td>
                                <td class="text-center">{{ $visit->fact_visit_at?->format("d.m.Y") ?? '-' }}</td>
                                <td class="text-center">{{ $visit->finished_at?->format("d.m.Y") ?? '-' }}</td>
                                <td class="text-center">{{ $visit->finalized_at?->format("d.m.Y") ?? '-' }}</td>
                                <td>
                                    @foreach($visit->users as $sampler)
                                        <div>{{ $loop->iteration }}) {{ $sampler->full_name }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    <div class="input-group mb-3">
                                        <span style="width: 30px" class="justify-content-center input-group-text px-2 py-1 font-12 cursor-help" title="Кол-во проб для отбора">{{ $visit->getMeasuresCount() }}</span>
                                        <span style="width: 30px" class="justify-content-center input-group-text px-2 py-1 font-12 bg-light-info fw-bold text-info cursor-help" title="Кол-во отобранных проб">@if($visit->getMeasuresAssetsCount() > 0) {{ $visit->getMeasuresAssetsCount() }} @endif</span>
                                        <span style="width: 30px" class="justify-content-center input-group-text px-2 py-1 font-12 bg-light-primary fw-bold text-primary cursor-help" title="Кол-во обработанных проб">@if($visit->getMeasuresFinishedCount() > 0) {{ $visit->getMeasuresFinishedCount() }} @endif</span>
                                    </div>
                                </td>
                                <td>
                                    <x-visit.status :visit="$visit"/>
                                </td>
                            </tr>
                        @endforeach


                        {{--                <x-order_task_address.summary.table :address="$address"></x-order_task_address.summary.table>--}}

                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {


        });
    </script>
@endsection
