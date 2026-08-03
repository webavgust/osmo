<tr task_id="{{ $task->id }}">
    <td><x-order_task.cell.id :task="$task"></x-order_task.cell.id></td>
    <td><x-order_task.status :order-task="$task"></x-order_task.status></td>
    <td>{{ _date($task->created_at) }}</td>
    <td>{{ $task->creator->fullName }}</td>
    <td>
        <div class="d-flex align-items-center justify-content-end">

            <x-order_task.progress :progress="$task->getProgress()"></x-order_task.progress>
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
