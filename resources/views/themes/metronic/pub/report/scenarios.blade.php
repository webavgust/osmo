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
            <table class="bg-white table table-bordered fs-6" id="payments">
                <tr>
                    <th>Группа</th>
                    <th>Номер</th>
                    <th>Название</th>
                    <th>Нейросервисы</th>
                </tr>
                @foreach($data as $row)
                    <tr @class(['sep_partner' => !empty($row[0]['rowspan']), "sep_company" => empty($row[0]['rowspan']) && !empty($row[1]['rowspan'])])>
                        {{-- ГРУППА --}}
                        @if(!empty($row[0]['cell']))
                            <td rowspan="{{ $row[0]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start"], $row[0]['class'] ?? []))>
                                {{ $row[0]['cell'] }}
                            </td>
                        @endif

                        {{-- НОМЕР --}}
                        @if(!empty($row[1]['cell']))
                            <td rowspan="{{ $row[1]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start"], $row[1]['class'] ?? []))>
                                {{ $row[1]['cell'] }}
                            </td>
                        @endif


                        {{-- НАЗВАНИЕ --}}
                        @if(!empty($row[2]['cell']))
                            <td rowspan="{{ $row[2]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start"], $row[2]['class'] ?? []))>
                                {{ $row[2]['cell'] }}
                            </td>
                        @endif

                        {{-- НЕЙРОСЕРВИС --}}
                        @if(!empty($row[3]['cell']))
                            <td rowspan="{{ $row[3]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start"], $row[3]['class'] ?? []))>
                                {{ $row[3]['cell'] }}
                            </td>
                        @endif

                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
