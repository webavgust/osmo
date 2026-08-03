@extends('layouts.layout')

@section('styles')
    @parent
@endsection


@section('content')
    @php
        $data = \App\Modules\Bitrix\Dashboard\Services\DashboardDataService::temp__table();
    @endphp
    <div class="container-fluid" id="proposal">
        <div class="row bg-white">
            <table class="table table-bordered mb-0">
                <tr>
                    <th>Страна</th>
                    <th>Компания</th>
                    <th>Кто создал компанию</th>
                    <th>Ответственный по сделкам</th>

                </tr>

                @foreach($data as $row)
                    <tr @class(['sep_partner' => !empty($row[0]['rowspan']), "sep_company" => empty($row[0]['rowspan']) && !empty($row[1]['rowspan'])])>
                        @if(!empty($row[0]))
                            <td rowspan="{{ $row[0]['rowspan'] ?? 1 }}" class="p-2 text-start">
                                {{ $row[0]['cell'] }}
                            </td>
                        @endif

                        @if(!empty($row[1]))
                            <td rowspan="{{ $row[1]['rowspan'] ?? 1 }}" class="p-2 text-start">
                                {{ $row[1]['cell'] }}
                            </td>
                        @endif

                        @if(!empty($row[2]))
                            <td rowspan="{{ $row[2]['rowspan'] ?? 1 }}" class="p-2 text-start">
                                {{ $row[2]['cell'] }}
                            </td>
                        @endif

                        @if(!empty($row[3]))
                            <td rowspan="{{ $row[3]['rowspan'] ?? 1 }}" class="p-2 text-start">
                                {!! $row[3]['cell'] !!}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

@endsection
