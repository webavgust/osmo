@extends('layouts.layout')

@section('styles')
    @parent
@endsection


@section('content')
    <style>
        tr.hl {
            background: #fffce0;
        }
    </style>
    <div class="container-fluid" id="proposal">
        <div class="row bg-white">
            <table class="table">
                <tr>
                    <th class="text-center">Место</th>
                    <th class="text-start">Базовый сценарий</th>
                    <th class="text-start">Ручной ввод</th>
                    <th class="text-start">Кто использовал</th>
                    <th class="text-center text-nowrap">Кол-во</th>
                </tr>
                @foreach($rating as $line)
                    <tr @class(['hl' => !empty($line['base_name'])])>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <?if(!empty($line['base_name'])):?>
                            <td class="text-start">{{ $line['base_name'] }}</td>
                            <td class="text-start fw-bold text-warning">{{ $line['name'] ?? '' }}</td>
                        <?else:?>
                            <td class="text-start">{{ $line['name'] }}</td>
                            <td class="text-start"></td>
                        <?endif;?>
                        <td class="text-start">{{ $line['users'] ?? '' }}</td>
                        <td class="text-center">{{ $line['count'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection

