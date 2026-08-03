@extends('layouts.layout')

@section('styles')
    @parent

@endsection


@section('content')
    <div class="container-fluid">
        <div class="row">
{{--            @foreach(['scenarios' => 'Сценарии', 'services' => 'Нейросервисы'] as $mode => $caption)--}}
                <div class="col-6">
                    <h4>Сценарии</h4>

                    <table class="table customize-table v-middle">
                        <thead class="table-secondary">
                        <tr>
                            <th class="text-secondary p-1 px-2 fs-3 fw-bold" width="1">Место</th>
                            <th class="text-secondary p-1 px-2 fs-3 fw-bold">Нейросервис</th>
                            <th class="text-secondary text-nowrap p-1 px-2 fs-3 fw-bold">Кол-во</th>
                            <th class="text-secondary text-nowrap p-1 px-2 fs-3 fw-bold">Продано</th>
                        </tr>
                        </thead>
                        <tbody>
                            @php
                                $arNeuroHave = [];
                            @endphp
                            @foreach($data['scenarios']['rows'] as $row)
                                @php
                                    $first = reset($data['scenarios']['rows']);
                                    $percent = round($row['count'] / $first['count'] * 100);
                                @endphp
                                <tr style="background: linear-gradient(to right, #e2ffd07d {{ $percent }}%, white {{ $percent }}%);">
                                    <td class="text-nowrap text-center p-1 px-2 fw-bold">
                                        @if($row['place'] == $row['end_place'])
                                            <span class="fs-2">{{ $row['place'] }}</span>
                                        @else
                                            <span class="fs-1">{{ $row['place'] }}&ndash;{{ $row['end_place'] }}</span>
                                        @endif
                                    </td>
                                    <td @class([
                                            "p-1 px-2 fs-4 cursor-help",
                                            "text-dark" => !$row['instance']->cb_registered,
                                            "text-decoration-line-through" => $row['instance']->cb_registered
                                        ]) title="{{ $row['instance']->number }}">
                                        {{ $row['instance']->name }}
                                    </td>
                                    <td class="p-1 px-2 fs-3 text-center">
                                        {{ $row['count'] }}
                                    </td>
                                    <td class="p-1 px-2 fs-3 text-center">
                                        {{ $row['sold'] }}
                                    </td>
                                </tr>

                                @foreach($row['instance']->neuroservices as $neuro)
                                    @php
                                        if($row['instance']->cb_registered || $neuro->cb_registered) $arNeuroHave[] = $neuro->id;
                                    @endphp
                                    <tr @class(["bg-white", "text-decoration-line-through" => in_array($neuro->id, $arNeuroHave)])>
                                        <td class="p-0"></td>
                                        <td class="fs-3 p-1 ps-4">{{ $neuro->name }}</td>
                                        <td/>
                                        <td/>
                                    </tr>
                                @endforeach


                            @endforeach
                            <tr>
                                <td colspan="2"></td>
                                <td class="fw-bold text-nowrap p-0 text-center">= {{ $data['scenarios']['count'] }}</td>
                                <td class="fw-bold text-nowrap p-0 text-center">= {{ $data['scenarios']['sold'] }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
{{--            @endforeach--}}
        </div>
    </div>
@endsection
