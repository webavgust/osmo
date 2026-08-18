@extends('components.box.box-static-large')


@section('body')
    <form method="post" id="calendar_add">
        <div class="card">
            <div class="card-body p-0">
                <table class="table customize-table v-middle" id="table_scenarios">
                    <tbody>
                        @foreach($neuroservice->scenarios as $scenario)
                            <tr id="{{ $scenario->id }}">
                                <td colspan=2>
                                    <div>
                                        <span class="search fw-bold">
                                            {{ $scenario->scenario_group->name }}
                                            <x-ui.icon.light icon="fa-arrow-right" class="fs-1 mx-2"/>
                                        </span>
                                        {{ $scenario->name }}
                                    </div>
                                    <div class="d-flex mt-1 justify-content-between">
                                        <div>
                                            <a href="javascript:void(0)" onclick="javascript:$(this).next('.d-none').removeClass('d-none');$(this).remove(); ">{{ tools()->num_rus($scenario->neuroservices->count(), ["нейросервиса", "нейросервис", "нейросервисов"], true) }}</a>

                                            <div class="d-none">
                                                @foreach($scenario->neuroservices as $service)
                                                    <div @class(["fw-bold text-danger" => $service->id == $neuroservice->id]) >
                                                        <span @class(["text-info fw-bold" => $service->id !== $neuroservice->id])>[{{ $service->neuroservice_group->name }}]</span> {{ $service->name }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="text-nowrap">
                                            <x-ui.badge.default type="info" class="p-0 d-flex-inline align-items-center py-1">
                                                <span class="px-2 fs-3 fw-bold me-2 border-end border-1 border-white">1</span>
                                                <span class="fs-2 me-2 d-inline-block" style="width: 60px">{{ tools()->cost_normalize($scenario->cost_year ?? '?') }} ₽</span>
                                            </x-ui.badge.default>

                                            <x-ui.badge.default type="primary" class="p-0 d-flex-inline align-items-center py-1">
                                                    <span class="ps-2 pe-1 fs-3 fw-bold me-2 border-end border-1 border-white">
                                                        <i class="fa-solid fa-infinity"></i>
                                                    </span>
                                                <span class="fs-2 me-2 d-inline-block" style="width: 60px">{{ tools()->cost_normalize($scenario->cost_unlimited ?? '?') }} ₽</span>
                                            </x-ui.badge.default>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>
@endsection
