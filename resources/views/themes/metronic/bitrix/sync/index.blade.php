@extends('layouts.layout')

@section('styles')
    @parent
@endsection


@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">Синхронизация данных</h4>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered m-t-30 table-hover contact-list footable footable-5 footable-paging footable-paging-center breakpoint-lg">
                            <thead>
                                <tr>
                                    <th width="1">№</th>
                                    <th>Таблица</th>
                                    <th>Записей</th>
                                    <th>Дата обновления</th>
                                    <th width="1">Обновление</th>
                                </tr>
                            </thead>

                            @foreach($tables as $table)
                                <tr table="{{ $table }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $table }}</strong>
                                    </td>
                                    <td class="count">
                                        {{ \Illuminate\Support\Facades\DB::connection('bitrix')->table($table)->count() }}
                                    </td>
                                    <td class="date">
                                        @if(!empty($times[$table]))
                                            {{ _date($times[$table], ['format' => "d.m.Y H:i"]) }}
                                        @else
                                            Не обновлялось
                                        @endif
                                    </td>
                                    <td>
                                        <x-ui.a.box btn_type="info" href="{{ route('sync.box.sync', $table) }}">
                                            Синхронизировать
                                        </x-ui.a.box>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

