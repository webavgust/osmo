<tr task_id="{{ $task->id }}">
    <td><x-order_task.cell.id :task="$task"></x-order_task.cell.id></td>
    <td><x-order_task.status :order-task="$task"></x-order_task.status></td>
    <td>{{ _date($task->created_at) }}</td>
    <td>{{ $task->creator->fullName }}</td>
    <td>
        <div class="d-flex align-items-center justify-content-end">
            @if($task->canStartWorking())
                <x-ui.a.ajax url="{{ route('api.order_task.start_working', $task) }}" btn_type="primary flex-grow-1 mb-1" reload="1" submit-message="Вы действительно хотите запустить ТЗ в работу?">
                    Передать в работу <i class="fa-solid fa-user-graduate ms-2"></i>
                </x-ui.a.ajax>
            @endif
        </div>
    </td>
    <td>
        <a href="{{ route('order_task.detail', $task) }}" class="btn waves-effect waves-light btn-outline-primary d-flex align-items-center justify-content-between">
            Подробнее <i class="fa-regular fa-arrow-right ms-2"></i>
        </a>
    </td>
</tr>
