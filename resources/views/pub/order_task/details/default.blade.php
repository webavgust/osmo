@foreach($order_task->objects as $object)
    <div class="card object mb-0">
        <div class="card-body d-flex justify-content-between align-items-center flex-column flex-md-row">
            <h4 class="card-title mb-0">
                <x-ui.icon.regular icon="fa-industry" class="me-2"></x-ui.icon.regular>
                {{ $object->name }}
            </h4>
            <div class="d-flex justify-content-between align-items-center mt-1 mt-md-0">
                <div class="alert text-primary alert-light-primary p-1 ps-2 pe-2 m-0" role="alert">
                    <x-order_task_object.badge-direction :object="$object" class="p-1 ps-2 pe-2 m-0 me-2"></x-order_task_object.badge-direction>

                    {!! $object->lab_object?->chain_name !!}
                </div>
            </div>
        </div>
        @foreach($object->addresses as $address)
            <div class="address">
                <div
                    class="card-body title pt-3 pb-3  d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <x-ui.icon.solid icon="fa-location-dot" class="ms-2 me-2"></x-ui.icon.solid>
                        <span>{{ $address->address }}</span>
                    </h5>

                </div>
                <div class="card-body pb-1">
                    @foreach($address->points as $point)
                        <div class="row mb-3">
                            <div class="col-12">
                                            <span class="text-danger point_name_pad d-flex align-items-center">
                                                  <x-ui.icon.solid icon="fa-map-pin"
                                                                   class="ms-4 me-2"></x-ui.icon.solid>
                                                <span class="point_name">@if(!empty($point->number))
                                                        <span
                                                            class="mb-1 badge bg-danger mr-1">{{ $point->number }}</span>
                                                    @endif
                                                    {{ $point->name }}</span>
                                            </span>
                            </div>
                            <div class="col-12 ps-5">
                                <div class="card-table ms-2 mt-2 font-14">
                                    @foreach($point->measures as $measure)
                                        <div class="tr">
                                                        <span class="th">
                                                            {{$measure->measure->name }}
                                                            @if(!empty($measure->comment))
                                                                <span class="ms-2">({{ $measure->comment }})</span>
                                                            @endif
                                                        </span>
                                            <span class="td flex-grow-1">
                                                            <nobr>
                                                                @if($measure->cost !== $measure->cost_real)
                                                                    <x-ui.icon.solid icon="fa-triangle-exclamation"
                                                                                     class="text-warning cursor-help"
                                                                                     title="Цена отличается от справочника ({{ $measure->cost_real }} р.)"></x-ui.icon.solid>
                                                                @endif
                                                                {{ _cost($measure->cost) }}
                                                                <x-ui.icon.thin icon="fa-xmark"
                                                                                class="ms-1 me-1 font-10"></x-ui.icon.thin>
                                                                {{ $measure->count }}
                                                                <x-ui.icon.thin icon="fa-equals"
                                                                                class="ms-1 me-1 font-10"></x-ui.icon.thin>
                                                                <span style="min-width: 50px"
                                                                      class="d-inline-flex align-items-center justify-content-end">
                                                                    <strong>{{ _cost($measure->cost_total) }}</strong>
                                                                    <x-ui.icon.solid icon="fa-ruble-sign"
                                                                                     class="font-12 ms-1"></x-ui.icon.solid>
                                                                </span>
                                                           </nobr>
                                                        </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="row mb-3">
                        <div class="col-12 ps-4">
                            <div class="card-table ms-3 mt-2 font-14">
                                @if(!empty($address->expanses))
                                    <div class="tr">
                                                    <span class="th">
                                                        <x-ui.icon.light icon="fa-suitcase-rolling" class="me-1"></x-ui.icon.light>
                                                        <span>Командировочные расходы</span>
                                                    </span>
                                        <span class="td">
                                                        <span style="min-width: 50px"
                                                              class="d-inline-flex align-items-center justify-content-end">
                                                            <strong>{{ _cost($address->expanses) }}</strong>
                                                            <x-ui.icon.solid icon="fa-ruble-sign"
                                                                             class="font-12 ms-1"></x-ui.icon.solid>
                                                        </span>
                                                    </span>
                                    </div>
                                @endif

                                @if(!empty($address->transport))
                                    <div class="tr">
                                        <span class="th">
                                            <x-ui.icon.light icon="fa-plane-up" style="margin-right: 2px"></x-ui.icon.light>
                                            <span>Транспортные расходы</span>
                                        </span>
                                        <span class="td">
                                            <span style="min-width: 50px"
                                                  class="d-inline-flex align-items-center justify-content-end">
                                                <strong>{{ _cost($address->transport) }}</strong>
                                                <x-ui.icon.solid icon="fa-ruble-sign"
                                                                 class="font-12 ms-1"></x-ui.icon.solid>
                                            </span>
                                        </span>
                                    </div>
                                @endif

                                @if(!empty($address->specialist))
                                    <div class="tr">
                                        <span class="th">
                                            <x-ui.icon.light icon="fa-plane-up" style="margin-right: 2px"></x-ui.icon.light>
                                            <span>Выезд специалиста ({{ $address->specialist['count'] }})</span>
                                        </span>
                                        <span class="td">
                                            <span style="min-width: 50px"
                                                  class="d-inline-flex align-items-center justify-content-end">
                                                <strong>{{ _cost($address->specialist['total']) }}</strong>
                                                <x-ui.icon.solid icon="fa-ruble-sign"
                                                                 class="font-12 ms-1"></x-ui.icon.solid>
                                            </span>
                                        </span>
                                    </div>
                                @endif

                                <div class="col-12 text-end mt-3 font-16 text-danger">
                                    <strong>= {{ _cost($address->cost_total) }}</strong>
                                    <x-ui.icon.solid icon="fa-ruble-sign"
                                                     class="font-12"></x-ui.icon.solid>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @endforeach

        @if($object->services->count())
            <div class="services">
                <div
                    class="card-body title pt-3 pb-3  d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <x-ui.icon.light icon="fa-coin" class="ms-2 me-1"></x-ui.icon.light>
                        <span>Услуги</span>
                    </h5>


                    <x-ui.badge.light_rounded type="secondary" class="mt-1">
                        <strong class="font-16">{{ $object->service_cost_total }}</strong>
                        <x-ui.icon.solid icon="fa-ruble-sign" class="font-12 ms-1"></x-ui.icon.solid>
                    </x-ui.badge.light_rounded>
                </div>
                <div class="card-body">
                    <div class="card-table ms-2 mt-2 font-14 ms-4 ps-3">
                        @foreach($object->services as $service)
                            <div class="tr">
                                            <span class="th">
                                                {{$service->name}}
                                                @if(!empty($service->pivot['comment']))
                                                    <span class="ms-2">({{ $service->pivot['comment'] }})</span>
                                                @endif
                                            </span>
                                <span class="td">
                                                @if(!empty($service->pivot->link_object_id))
                                        Привязано к
                                        <a href="{{ route('order_task.detail', $service->getLinkObject()->order_task_id) }}"
                                           target="_blank">#{{$service->getLinkObject()->order_task_id}}: {{ $service->getLinkObject()->name }}</a>
                                    @else
                                        {{ _cost($service->cost) }}
                                        <x-ui.icon.thin icon="fa-xmark"
                                                        class="ms-1 me-1 font-10"></x-ui.icon.thin>
                                        {{ $service->pivot['count'] }}
                                        <x-ui.icon.thin icon="fa-equals"
                                                        class="ms-1 me-1 font-10"></x-ui.icon.thin>
                                        <span style="min-width: 50px"
                                              class="d-inline-flex align-items-center justify-content-end">
                                                    <strong>{{ _cost($service->pivot['cost_total']) }}</strong>
                                                    <x-ui.icon.solid icon="fa-ruble-sign"
                                                                     class="font-12 ms-1"></x-ui.icon.solid>
                                                </span>
                                    @endif
                                            </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>


    <div class="d-flex justify-content-end">
        <div class="mb-1 badge bg-light-secondary text-secondary mt-1  font-18 mt-2 mb-4"
             type="secondary">
            = {{ _cost($object->cost_total) }}
            <x-ui.icon.light icon="fa-ruble-sign" class="font-14 ms-1"></x-ui.icon.light>
        </div>
    </div>
@endforeach

@if($order_task->discount > 0)
    <div
        class="d-flex text-danger justify-content-end align-items-center font-24 me-1 mb-1">
        <strong>&ndash; {{ _cost($order_task->discount) }}</strong>
        <x-ui.icon.solid icon="fa-ruble-sign" class="font-20 ms-1"></x-ui.icon.solid>
    </div>
@endif

<div
    class="d-flex justify-content-end align-items-center font-24 me-1 border-top border-secondary pt-3">
    <strong>= {{ _cost($order_task->cost_total) }}</strong>
    <x-ui.icon.solid icon="fa-ruble-sign" class="font-20 ms-1"></x-ui.icon.solid>
</div>

