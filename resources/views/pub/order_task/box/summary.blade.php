@extends('components.box.box-static-extralarge')

@section('body')
    <style>
        th.border-left-3, td.border-left-3 {
            border-left-width: 2px;
            border-left-color: #CCC;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <ul class="nav nav-tabs " role="tablist">
                @foreach($task->objects as $object)
                    <li class="nav-item">
                        <a @class(["nav-link", "active" => $loop->iteration == 1]) data-bs-toggle="tab" href="#tab_{{ $object->id }}" role="tab" aria-selected="true">
                            <span class="font-20">
                                <x-ui.icon.solid icon="fa-industry" class="me-1"></x-ui.icon.solid>
                                {{ $object->name }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content mt-2">
                @foreach($task->objects as $object)
                    <div @class(["tab-pane", "active" => $loop->iteration == 1]) id="tab_{{ $object->id }}" role="tabpanel">
                        <div class="col-12">
                            <ul class="nav nav-pills mb-3" role="tablist">
                                @foreach($object->addresses as $address)
                                    <li class="nav-item">
                                        <a @class(["nav-link", "active" => $loop->iteration == 1]) data-bs-toggle="tab" href="#tab_{{ $object->id }}_{{ $address->id}}" role="tab" aria-selected="true">
                                            <span>
                                                <x-ui.icon.solid icon="fa-location-dot" class="me-1"></x-ui.icon.solid>
                                                {{ $address->address }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content border mt-2">
                                @foreach($object->addresses as $address)
                                    <div @class(["tab-pane", "active" => $loop->iteration == 1]) id="tab_{{ $object->id }}_{{ $address->id}}" role="tabpanel">
                                        <table class="tablesaw table-bordered table-hover table no-wrap tablesaw-sortable tablesaw-swipe m-0">
                                            <x-order_task_address.summary.table :address="$address"></x-order_task_address.summary.table>
                                        </table>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                @endforeach

            </div>

        </div>
    </div>
@endsection
