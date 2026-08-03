@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>
    <link rel="stylesheet" href="/dist/modules/daterangepicker/daterangepicker.css" />
    <style>
        th.bl, td.bl {
            border-left: 3px solid #b3d2fa;
        }
        tr.sep_partner td {
            border-top: 3px solid #CCC;
        }
        tr.sep_company td {
            border-top: 2px solid #ccc;
        }

        .separator {
            border-bottom: 1px solid #DDD;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        td.manual {
            background: #ffeeee;
        }
        td.bold_red {
            font-weight: 400;
            color: #df1212;
        }
    </style>
@endsection


@section('content')
    <div class="container-fluid">
        <div class="table-responsive">
            <table class="bg-white table table-bordered" id="payments">
                <tr>
                    <th class="text-start py-1 px-2">Сценарий</th>
                    <th class="text-start py-1 px-2">Нейросервис</th>
                    <th class="text-start py-1 px-2">Тех.название</th>
                </tr>
                @foreach(\App\Modules\Pub\Scenario\Models\Scenario::orderBy('scenario_group_id')->get() as $scenario)
                    @foreach($scenario->neuroservices()->orderBy('neuroservice_group_id')->get() as $neuro)
                        <tr @if($loop->first) style="border-top: 3px solid #999" @endif>
                            @if($loop->first)
                                <td class="p-2 fs-4" rowspan="{{ $scenario->neuroservices->count() }}">{{ $scenario->name }}</td>
                            @endif
                            <td class="px-2 py-1 fs-2" >{{ $neuro->name }}</td>
                            <td class="px-2 py-1 fs-2" >{{ $neuro->tech_name }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </table>

            <table class="bg-white table table-bordered mt-4">
                <tr>
                    <th class="text-start py-1 px-2">Нейросервис</th>
                    <th class="text-start py-1 px-2">Тех.название</th>
                </tr>
                @foreach(\App\Modules\Pub\Neuroservice\Models\Neuroservice::whereDoesntHave('scenarios')->orderBy('sort')->get() as $neuro)
                    <tr>
                        <td class="px-2 py-1 fs-2" >{{ $neuro->name }}</td>
                        <td class="px-2 py-1 fs-2" >{{ $neuro->tech_name }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

@endsection
