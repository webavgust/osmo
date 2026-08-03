@extends('components.box.box-static-large')

@section('body')
    <style>
        .modal-backdrop {
            z-index: 100;
        }

        #offcanvas {
            z-index: 2000 !important;
        }

        #offcanvas .modal-backdrop {
            z-index: 2010 !important;
        }

        body.modal-open #offcanvas .offcanvas {
            z-index: 2020 !important;
        }


        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--multiple {
            border-color: #DDD;
        }

        .tr:hover .td, .tr:hover .th, .tr:hover .td a {
            color: #2b5bff!important;
        }
    </style>
    <form id="samplers">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-table">
                        <ul class="list-style-none">
                            @foreach($objects_source as $type => $data)
                                <li id="global{{ $type }}">
                                    @if(!empty($data['objects']) && $data['objects']->isNotEmpty())
                                        <div class="tr mb-2">
                                            <div class="th"><h3>{{ $data['name'] }}</h3></div>
                                            <div class="td">
                                                <a href="javascript:void(0);"
                                                   onclick="javascript:sidebar({href:'{{ route('order_task.sidebar_set_samplers', ['order_task', $orderTask->id]) }}', data: {type: '{{ $type }}', selected: $(this).next('input').val() }})">
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-1 font-12 mb-1">
                                                            @if($data['users']->isNotEmpty())
                                                                {{ tools()->num_rus(count($data['users']), ["человека", "человек", "людей"], true) }}
                                                            @else
                                                                Выбрать
                                                            @endif
                                                        </span>
                                                        <x-ui.icon.solid icon="fa-chevrons-right"></x-ui.icon.solid>
                                                    </div>
                                                </a>
                                                <input type="hidden" name="samplers[global][{{ $type }}]"
                                                       value="{{ $data['users']->isNotEmpty() ? $data['users']->implode(",") : ""}}">
                                            </div>
                                        </div>

                                        <ul class="list-style-none ms-3">
                                            @foreach($data['objects'] as $object)
                                                <li class="mb-3" id="object_{{ $object->id }}">
                                                    <div class="tr">
                                                        <div class="th">
                                                            <x-ui.icon.light icon="fa-industry"
                                                                             class="me-2"></x-ui.icon.light>
                                                            {{ $object->name }}
                                                        </div>
                                                        <div class="td">

                                                            <a href="javascript:void(0);"
                                                               onclick="javascript:sidebar({href:'{{ route('order_task.sidebar_set_samplers', ['object', $object->id]) }}', data: { selected: $(this).next('input').val() }})">
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-1 font-12 mb-1">
                                                                        @if($object->samplers->isNotEmpty())
                                                                            {{ tools()->num_rus(count($object->samplers), ["человека", "человек", "людей"], true) }}
                                                                        @else
                                                                            Выбрать
                                                                        @endif
                                                                    </span>
                                                                    <x-ui.icon.solid
                                                                        icon="fa-chevrons-right"></x-ui.icon.solid>
                                                                </div>
                                                            </a>

                                                            <input type="hidden"
                                                                   name="samplers[object][{{ $object->id }}]"
                                                                   value="{{ $object->samplers->pluck('user_id')->implode(",") }}">
                                                        </div>
                                                    </div>

                                                    <ul class="list-style-none ms-2">
                                                        @foreach($object->addresses as $address)
                                                            <li class="mt-1" id="address_{{ $address->id }}">
                                                                <div class="tr">
                                                                    <div class="th ps-3">
                                                                        <x-ui.icon.light icon="fa-location-dot"
                                                                                         class="me-2"></x-ui.icon.light>
                                                                        {{ $address->address }}
                                                                    </div>
                                                                    <div class="td">
                                                                        <a href="javascript:void(0);"
                                                                           onclick="javascript:sidebar({href:'{{ route('order_task.sidebar_set_samplers', ['address', $address->id]) }}', data: { selected: $(this).next('input').val() }})">
                                                                            <div class="d-flex align-items-center">
                                                                            <span class="me-1 font-12 mb-1">
                                                                                @if($address->samplers->isNotEmpty())
                                                                                    {{ tools()->num_rus(count($address->samplers), ["человека", "человек", "людей"], true) }}
                                                                                @else
                                                                                    Выбрать
                                                                                @endif
                                                                            </span>
                                                                                <x-ui.icon.solid
                                                                                    icon="fa-chevrons-right"></x-ui.icon.solid>
                                                                            </div>
                                                                        </a>

                                                                        <input type="hidden"
                                                                               name="samplers[address][{{ $address->id }}]"
                                                                               value="{{ $address->samplers->pluck('user_id')->implode(",") }}">
                                                                    </div>
                                                                </div>

                                                                <ul class="list-style-none ms-4">
                                                                    @foreach($address->points as $point)
                                                                        <li class="mt-1" id="point_{{ $point->id }}" type="point">
                                                                            <div class="tr">
                                                                                <div @class(['th', 'ps-3', 'text-danger' => !$point->hasSamplers()])>
                                                                                        <x-ui.icon.solid icon="fa-map-pin"
                                                                                                         class="me-2"></x-ui.icon.solid>
                                                                                        {{ $point->name }}
                                                                                </div>
                                                                                <div class="td d-flex align-items-center font-12 me-1">

                                                                                    <a href="javascript:void(0);"
                                                                                       @class(['text-danger' => !$point->hasSamplers()])
                                                                                       onclick="javascript:sidebar({href:'{{ route('order_task.sidebar_set_samplers', ['point', $point->id]) }}', data: { selected: $(this).next('input').val() }})">
                                                                                        <div
                                                                                            class="d-flex align-items-center">
                                                                                            <span
                                                                                                class="me-1 font-12 mb-1">
                                                                                                    @if($point->samplers->isNotEmpty())
                                                                                                        <span>
                                                                                                            {{ tools()->num_rus(count($point->samplers), ["человека", "человек", "людей"], true) }}
                                                                                                        </span>
                                                                                                    @else
                                                                                                        Выбрать
                                                                                                    @endif

                                                                                            </span>
                                                                                            <x-ui.icon.solid
                                                                                                icon="fa-chevrons-right"></x-ui.icon.solid>
                                                                                        </div>
                                                                                    </a>

                                                                                    <input type="hidden"
                                                                                           name="samplers[point][{{ $point->id }}]"
                                                                                           value="{{ $point->samplers->pluck('user_id')->implode(",") }}">
                                                                                </div>
                                                                            </div>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        $(document).ready(function () {
            $(".select2").each(function () {
                parent = $(this).parents('li[id]');
                $(this).select2({
                    dropdownParent: parent,
                });
            });
        });

        function save() {
            if (!confirm('Вы действительно хотите сохранить пробоотборщиков?'))
                return false;

            $.ajax({
                method: "POST",
                url: '{!! route('api.order_task.set_samplers', [$orderTask, 'type' => $type, '_token' => _token()]) !!}',
                data: $("form#samplers").serialize(),
                success: function () {
                    location.reload();
                },
                error: function () {
                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                }
            });
        }
    </script>

@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default btn_type="info" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection


