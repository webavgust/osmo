@php
    $task =    $visit->order_task_address->object->task;
@endphp
<tr visit_id="{{ $visit->id }}">
    <td>
        @if(!empty($visit->number))
            {!! _docnumber($visit->number->number)  !!}
        @else
            Еще не присвоен
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
</tr>
