@php
    $task =    $visit->order_task_address?->object->task;
@endphp
<tr visit_id="{{ $visit->id }}">
    <td>
        @if(!empty($visit->number))
            {!! _docnumber($visit->number->number)  !!}
        @endif
    </td>
    <td>
        <x-order_task.cell.id :task="$task"></x-order_task.cell.id>
    </td>
    <td>
        <div class="d-flex align-items-center">
            <x-order_task_object.badge-direction :object="$visit->order_task_address->object" class="me-1"></x-order_task_object.badge-direction>
            <a href="{{ route('order_task_object.detail', $visit->order_task_address->object) }}">
                {{ $visit->order_task_address->object->name }}
            </a>
        </div>
    </td>
    <td>{{ _date($visit->created_at) }}</td>
    <td>{{ $visit->creator->fullName }}</td>
    <td>
        <x-visit.status :visit="$visit"></x-visit.status>
    </td>
    <td>
            <div class="d-flex">

                <x-ui.a.box title="{{ $visit->button }}" :href="$visit->box" outline="1" btn_type="warning" >
                    <x-ui.icon.solid icon="fa-bolt"></x-ui.icon.solid>
                </x-ui.a.box>

                <x-ui.a.outline title="Перейти в объект исследования" href="{{ route('order_task_object.detail', $visit->order_task_address->object) . (!empty($visit->number) ? '?act=' . $visit->number->number : '') }}
                " btn_type="primary" class="ms-1">
                    <x-ui.icon.solid icon="fa-arrow-right"></x-ui.icon.solid>
                </x-ui.a.outline>
            </div>
    </td>
</tr>
