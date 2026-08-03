@php
    $task = $visit->order_task_address?->object?->task;
@endphp

@if(empty($task))
    <tr>
        <td colspan="5">?</td>
    </tr>
@else
    <tr visit_id="{{ $visit->id }}">
        <td>
            <a href="{{ route('visit.task', $task) }}/?act={{ $visit->number->number }}">
                {!! _docnumber($visit->number->number)  !!}
            </a>
        </td>
        <td>
            <x-order_task.cell.id :task="$task"></x-order_task.cell.id>
        </td>
        <td>
            <div class="d-flex align-items-center">
                <x-order_task_object.badge-direction :object="$visit->order_task_address->object" class="me-1"></x-order_task_object.badge-direction>

                {{ $visit->order_task_address->object->name }}
            </div>
        </td>
        <td>{{ _date($visit->created_at) }}</td>
        <td>{{ $visit->creator->fullName }}</td>
        <td>
            <x-visit.status :visit="$visit"></x-visit.status>
        </td>
        <td>
            <div class="d-flex">
                <x-ui.a.outline href="{{ route('visit.lab', $visit->number->number) }}" btn_type="primary" class="ms-1">
                    <x-ui.icon.solid icon="fa-arrow-right"></x-ui.icon.solid>
                </x-ui.a.outline>
            </div>
        </td>
    </tr>
@endif
