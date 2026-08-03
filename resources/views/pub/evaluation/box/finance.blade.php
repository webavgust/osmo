@extends('components.box.box-static-large')

@section('body')
    <div class="row">
        <div class="col-12">
            <table class="tablesaw table-bordered table-hover table no-wrap tablesaw-sortable tablesaw-swipe">
                @foreach($evaluation->objects as $object)
                    @php
                        $rate = match($object->lab_object->root_id) {
                            \App\Modules\Pub\LabObject\Models\LabObject::GROUP_WATER,
                            \App\Modules\Pub\LabObject\Models\LabObject::GROUP_EARTH => (float)\App\Modules\Pub\Constant\Models\Constant::get('protocol_cost_water_earth'),

                            \App\Modules\Pub\LabObject\Models\LabObject::GROUP_AIR,
                            \App\Modules\Pub\LabObject\Models\LabObject::GROUP_PHYSICAL => (float)\App\Modules\Pub\Constant\Models\Constant::get('protocol_cost_air_physical'),

                            default => 0
                        };
                    @endphp
                    <tr>
                        <th colspan="4">
                            <div class="d-flex align-items-center justify-content-between">
                                <span>
                                    <x-ui.icon.regular icon="fa-industry" class="me-2"></x-ui.icon.regular>
                                    {{ $object->name }}
                                </span>

                                <div>
                                    <span class="alert text-primary alert-light-primary p-1 ps-2 pe-2 m-0" role="alert">
                                        {{ $object->lab_object?->chain_name }}
                                    </span>
                                    <span class="alert text-primary alert-light-primary p-1 ps-2 pe-2 m-0 ms-1" role="alert">
                                        = {{ tools()->cost_normalize($rate) }} ₽
                                    </span>
                                </div>
                            </div>
                        </th>
                    </tr>
                    @foreach($object->addresses as $address)
                        <tr>
                            <th colspan="4" class="ps-3">
                                <x-ui.icon.solid icon="fa-location-dot" class="ms-2 me-2"></x-ui.icon.solid>
                                <span>{{ $address->address }}</span>
                            </th>
                        </tr>
                        @if(!empty($address->expanses))
                            <tr>
                                <td class="ps-5 p-1">
                                    <x-ui.icon.light icon="fa-suitcase-rolling" class="me-1"></x-ui.icon.light>
                                    <span class="font-14">Командировочные расходы</span>
                                </td>
                                <td align="right" class="font-14 p-1">
                                    {{ tools()->cost_normalize($address->expanses) }} ₽
                                </td>
                            </tr>
                        @endif
                        @if(!empty($address->transport))
                            <tr>
                                <td class="ps-5 p-1">
                                    <x-ui.icon.light icon="fa-plane-up" style="margin-right: 2px"></x-ui.icon.light>
                                    <span class="font-14">Транспортные расходы</span>
                                </td>
                                <td align="right" class="font-14 p-1">
                                    {{ tools()->cost_normalize($address->transport) }} ₽
                                </td>
                            </tr>
                        @endif
                        @if(!empty($address->specialist))
                            <tr>
                                <td class="ps-5 p-1">
                                    <x-ui.icon.light icon="fa-plane-up" style="margin-right: 2px"></x-ui.icon.light>
                                    <span class="font-14">Выезд специалиста ({{ $address->specialist['count'] }})</span>
                                </td>
                                <td align="right" class="font-14 p-1">
                                    {{ tools()->cost_normalize($address->specialist['total']) }} ₽
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="ps-5 p-1">
                                <strong><span class="font-14">Общая стоимость</span></strong>
                            </td>
                            <td align="right" class="font-14 p-1">
                                <strong>{{ tools()->cost_normalize($address->cost_total) }} ₽</strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-5 p-1">
                                <strong><span class="font-14">Промежуточная себестоимость</span></strong>
                            </td>
                            <td align="right" class="font-14 p-1">
                                <strong>{{ tools()->cost_normalize($address->cost_raw) }} ₽</strong>
                            </td>
                        </tr>

                        @foreach($address->points as $point)
                            <tr>
                                <th colspan="4" class="ps-4">
                                   <span class="text-danger point_name_pad d-flex align-items-center">
                                          <x-ui.icon.solid icon="fa-map-pin"
                                                           class="ms-4 me-2"></x-ui.icon.solid>
                                        <span class="point_name">@if(!empty($point->number))
                                                <span
                                                    class="mb-1 badge bg-danger mr-1">{{ $point->number }}</span>
                                            @endif
                                            {{ $point->name }}</span>
                                    </span>
                                </th>
                            </tr>
                            <tr>
                                <td class="ps-5 p-1">
                                    <span class="font-14">Себестоимость измерений</span>

                                </td>
                                <td align="right" class="font-14 p-1">
                                    {{ tools()->cost_normalize($point->cost_raw) }} ₽
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-5 p-1">
                                    <span class="font-14 ms-3">
                                        Сумма за кол-во измерений
                                        &nbsp;&nbsp;&nbsp;
                                        <x-ui.badge.default type="warning">V = {{ $point->measures->max('count') }}</x-ui.badge.default>
                                        *
                                        <x-ui.badge.default type="primary">{{ $rate }} ₽</x-ui.badge.default>

                                    </span>
                                </td>
                                <td align="right" class="font-14 p-1">

                                    {{ tools()->cost_normalize($point->measures->max('count') * $rate) }} ₽
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-5 p-1">
                                    <span class="font-14 ms-3">
                                        Бонусная часть измерений
                                    </span>
                                </td>
                                <td align="right" class="font-14 p-1">
                                    @php
                                        $sum = 0;
                                        $point->measures->each(function($item) use (&$sum) {
                                            $sum += ($item->measure->bonus * $item->count);
                                        });
                                    @endphp
                                    {{ tools()->cost_normalize($sum) }} ₽
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-5 p-1">
                                    <span class="font-14">Стоимость измерений</span>
                                </td>
                                <td align="right" class="font-14 p-1">
                                    {{ tools()->cost_normalize($point->cost_total) }} ₽
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach

                    <tr>
                        <th>
                            <div class="d-flex align-items-center justify-content-between">
                                <span>
                                    <x-ui.icon.regular icon="fa-ruble-sign" class="me-2"></x-ui.icon.regular>
                                    Бонусная часть начальника лаборатории
                                </span>
                            </div>
                        </th>
                        <td align="right" class="font-14">
                            {{ tools()->cost_normalize($evaluation->plan_supervisor_salary) }} ₽
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3 p-1" colspan="2">
                            @php
                                $address_cost_total = $address_cost_raw = 0;
                                $evaluation->objects->each(function($object) use (&$address_cost_total, &$address_cost_raw) {
                                    $object->addresses->each(function($address) use (&$address_cost_total, &$address_cost_raw) {
                                        // 1. вычтем ставку
                                        $address_cost_total += $address->cost_total;
                                        $address_cost_raw += $address->cost_raw;
                                    });
                                });
                            @endphp

                            <div class="d-flex align-items-center justify-content-start" style="margin-left: 20px">
                                <span class="font-20">(</span>
                                <x-ui.badge.light type="info" class="cursor-help" title="Общая стоимость">{{ tools()->cost_normalize($address_cost_total) }} ₽</x-ui.badge.light>
                                <span class="px-1">&ndash;</span>
                                <x-ui.badge.light type="warning" >{{ tools()->cost_normalize($address_cost_total * ($evaluation->minus_rate / 100)) }} ₽ ({{ $evaluation->minus_rate }}%)</x-ui.badge.light>
                                <span class="px-1">&ndash;</span>
                                <x-ui.badge.light type="danger" class="cursor-help" title="Промежуточная себестоимость">{{ tools()->cost_normalize($address_cost_raw) }} ₽</x-ui.badge.light>
                                <span class="font-20">)</span>
                                <x-ui.icon.solid icon="fa-xmark" class="font-16 px-2"></x-ui.icon.solid>
                                <x-ui.badge.light type="primary">{{ $evaluation->supervisor_rate }}%</x-ui.badge.light>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <div class="d-flex align-items-center justify-content-between">
                                <span>
                                    <x-ui.icon.regular icon="fa-calendar-circle-minus" class="me-2"></x-ui.icon.regular>
                                    Конечная плановая себестоимость
                                </span>
                            </div>
                        </th>
                        <td align="right" class="font-14">
                            {{ tools()->cost_normalize($evaluation->plan_cost_total) }} ₽
                        </td>
                    </tr>
            </table>
        </div>
    </div>
@endsection
