<tr task_id="{{ $task->id }}">
    <td><x-order_task.cell.id :task="$task"></x-order_task.cell.id></td>
    <td><x-order_task.badge-direction :task="$task"></x-order_task.badge-direction></td>
    <td><x-order_task.status :order-task="$task"></x-order_task.status></td>
    <td>{{ _date($task->created_at) }}</td>
    <td>{{ $task->creator->fullName }}</td>
    <td>
        <div class="d-flex align-items-center justify-content-end">
            @if($task->canStartWorking())
                <x-ui.a.ajax url="{{ route('api.order_task.start_working', $task) }}" btn_type="primary flex-grow-1 mb-1" reload="1" submit-message="Вы действительно хотите запустить ТЗ в работу?">
                    <x-ui.icon.regular icon="fa-play" class="me-1"></x-ui.icon.regular>
                    Передать в работу
                </x-ui.a.ajax>
            @endif
        </div>
    </td>
    <td>
        <div class="d-flex">

            <x-ui.a.light href="{{ route('order_task.detail', $task) }}" btn_type="secondary">
                <x-ui.icon.light icon="fa-table" class="text-secondary"></x-ui.icon.light>
            </x-ui.a.light>

            <x-ui.a.outline href="{{ route('order_task.detail', $task) }}" btn_type="primary" class="ms-1">
                <x-ui.icon.solid icon="fa-arrow-right"></x-ui.icon.solid>
            </x-ui.a.outline>

        </div>
    </td>
</tr>
