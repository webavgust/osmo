@extends('components.box.box-static-large')

@section('body')

        <h3>ТЗ №{{ $task->id }}</h3>

        <div class="mt-3">
            @foreach($task->objects as $object)
                <div class="once">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center m-0">
                            <x-order-task-object.badge-direction :object="$object" class="me-1"></x-order-task-object.badge-direction>

                            <x-ui.icon.regular icon="fa-industry" class="ms-2"></x-ui.icon.regular>
                            <span class="ms-1 fw-bold font-18">{{ $object->name }}</span>
                        </div>
                        <div>
                            <x-ui.badge.light type="info">{{ $object->lab_object?->chain_name }}</x-ui.badge.light>
                        </div>
                    </div>
                    <div class="addresses mt-2">
                        @foreach($object->addresses as $address)
                             <div>
                                 <div class="d-flex justify-content-start align-items-center">
                                     <x-ui.a.box btn_type="primary" :href="route('visit.box_create', [$address, 'plan' => $model->id])" class="py-1 px-2 font-12">
                                         Выбрать
                                     </x-ui.a.box>

                                     <div class="ms-3">
                                         <x-ui.icon.light icon="fa-location-dot"></x-ui.icon.light>
                                         @if($address->isFinished())
                                             <x-ui.icon.solid icon="fa-circle-check" class="text-success mx-1"></x-ui.icon.solid>
                                         @endif

                                         <span class="ms-1 font-16">{{ $address->address }}</span>
                                     </div>
                                 </div>
                             </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

        </div>
@endsection

