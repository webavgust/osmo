@can('direction_A')
    @if($order_task->objectsA->isNotEmpty())
        <div class="mb-2 d-flex justify-content-between">
            <h3>Направление A</h3>

            @if($order_task->canSetSamplers())
                @php
                    $samplers = $order_task->globalSamplers('A');
                @endphp
                <x-ui.a.box :btn_type="$samplers > 0 ? 'success' : 'danger'"
                            href="{{ route('order_task.box_set_samplers', [$order_task, 'type' => 'A']) }}">
                    <span @class(['text-white' => $samplers == 0, 'text-white' => $samplers > 0])>
                        <x-ui.icon.duotone :icon="$samplers == 0 ? 'fa-users-slash' : 'fa-users'"
                                           class="me-1"></x-ui.icon.duotone>
                        Пробоотборщики
                        @if($samplers > 0)
                            <sup>{{ $samplers }}</sup>
                        @endif
                    </span>
                </x-ui.a.box>
            @endif
        </div>
        <div class="mb-4">
            @foreach($order_task->objectsA as $object)
                <x-order_task_object.detail_block
                    :object="$object"></x-order_task_object.detail_block>
            @endforeach
        </div>
    @endif
@endcan

@can('direction_B')
    @if($order_task->objectsB->isNotEmpty())
        <div class="mb-2 d-flex justify-content-between">
            <h3>Направление Б</h3>

            @if($order_task->canSetSamplers())
                @php
                    $samplers = $order_task->globalSamplers('B');
                @endphp
                <x-ui.a.box :btn_type="$samplers > 0 ? 'success' : 'danger'"
                            href="{{ route('order_task.box_set_samplers', [$order_task, 'type' => 'B']) }}">
                    <span @class(['text-white' => $samplers == 0, 'text-white' => $samplers > 0])>
                        <x-ui.icon.duotone :icon="$samplers == 0 ? 'fa-users-slash' : 'fa-users'"
                                           class="me-1"></x-ui.icon.duotone>
                        Пробоотборщики
                        @if($samplers > 0)
                            <sup>{{ $samplers }}</sup>
                        @endif
                    </span>
                </x-ui.a.box>
            @endif
        </div>
        @foreach($order_task->objectsB as $object)
            <x-order_task_object.detail_block
                :object="$object"></x-order_task_object.detail_block>
        @endforeach
    @endif
@endcan
