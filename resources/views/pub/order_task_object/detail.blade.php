@extends('layouts.layout')
@section('content')
    <div class="container-fluid" page="order_task.detail">
        <div class="row">
            <div class="col-12 col-md-3 objects">
                @if($object->task->objectsA->isNotEmpty() && _can('direction_A'))
                    <h4>Направление А</h4>
                    @foreach($object->task->objectsA as $object_nav)
                        <x-order_task_object.detail_block
                            :object="$object_nav" :selected="$object->id" :short="1"></x-order_task_object.detail_block>
                    @endforeach
                @endif


                @if($object->task->objectsB->isNotEmpty() && _can('direction_B'))
                    <h4>Направление Б</h4>
                    @foreach($object->task->objectsB as $object_nav)
                        <x-order_task_object.detail_block
                            :object="$object_nav" :selected="$object->id" :short="1"></x-order_task_object.detail_block>
                    @endforeach
                @endif
            </div>
            <div class="col-12 col-md-9 mt-4">
                @foreach($object->addresses as $address)
                    <div class="card mb-0">
                        <div class=" p-3 pb-0">
                            <div class="d-flex justify-content-between">
                                <h4>
                                    @if($address->isFinished())
                                        <x-ui.icon.solid icon="fa-circle-check" class="text-success me-2"></x-ui.icon.solid>
                                    @endif
                                    <x-ui.icon.regular icon="fa-location-dot" class="me-2"></x-ui.icon.regular>
                                    <span>{{ $address->address }}</span>
                                </h4>

                                <x-ui.a.box :href="route('order_task_address.box_summary', $address)">
                                    <div class="d-flex align-items-center">
                                        <x-ui.icon.light icon="fa-table me-2"></x-ui.icon.light>
                                        <span>Сводная таблица</span>
                                    </div>
                                </x-ui.a.box>
                            </div>
                            <ul class="list-style-none ms-3 mb-3">
                                @foreach($address->points as $point)
                                    <li class="font-14">
                                        <div class="d-flex align-items-center mb-1">
                                            <x-ui.icon.solid icon="fa-map-pin"
                                                             class="me-2"></x-ui.icon.solid>


                                            @if($address->hasSamplers())
                                                <x-ui.a.sidebar href="{{ route('order_task_point.sidebar_samplers', $point) }}" class="d-inline p-0">
                                                    <x-ui.icon.solid icon="fa-users"
                                                                     class="me-2 text-light-info" title="Пробоотборщики"></x-ui.icon.solid>
                                                </x-ui.a.sidebar>
                                            @else
                                                <x-ui.icon.duotone icon="fa-users-slash"
                                                                   class="me-2 text-secondary" title="Не незначены пробоотборщики"></x-ui.icon.duotone>
                                            @endif

                                            <span class="me-2">
                                                {{ $point->name }}
                                            </span>

                                            <x-order_task_point.icon_status class="text-success" :point="$point" success="light-success" failed="light-secondary" ></x-order_task_point.icon_status>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <h6 class="mt-4">
                                <x-ui.icon.light icon="fa-file"></x-ui.icon.light>
                                Выезды и акты
                            </h6>
                        </div>

                        <div class="border-bottom"></div>

                        <div class="mb-3 p-3 d-flex">
                            @can('visit_create', $address)
                                @if(!$object->isFinished())
                                    <x-ui.button.outline btn_type="light-secondary" class="border-2 me-2 d-inline" style="border-style: dashed; height: 86px;" onclick="javascript:box({href:'{{ route('visit.box_create', $address) }}'})">
                                        <div class="text-secondary d-flex flex-column align-items-center justify-content-center">
                                            <x-ui.icon.solid icon="fa-plus"></x-ui.icon.solid>
                                            <span class="font-14">Создать выезд</span>
                                        </div>
                                    </x-ui.button.outline>
                                @endif
                            @endcan

                            <div class="d-flex">
                                @foreach($address->visits as $visit)
                                    <x-visit.order_task_object_button :visit="$visit"></x-visit.order_task_object_button>
                                @endforeach
                            </div>

                        </div>


                        <x-order_task.progress :progress="$address->getProgress()"></x-order_task.progress>

                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" type="text/css"
          href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/locales/bootstrap-datepicker.ru.min.js"></script>
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>

@endsection
