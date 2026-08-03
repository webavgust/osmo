<tr task_id="{{ $task->id }}">
    <td>
        <x-order_task.cell.id :task="$task"></x-order_task.cell.id>
    </td>
    <td class="text-center">
        <x-order_task.cell.iteration :task="$task"></x-order_task.cell.iteration>
    </td>
    <td>
        <x-order_task.status :order-task="$task"></x-order_task.status>
    </td>
    <td>
        <x-order_task.cell.contract :task="$task"></x-order_task.cell.contract>
    </td>
    <td>
        <x-order_task.cell.sub_contract :task="$task"></x-order_task.cell.sub_contract>
    </td>
    <td>
        <x-order_task.cell.block_id :task="$task"></x-order_task.cell.block_id>
    </td>
    <td>{{ _date($task->created_at) }}</td>
</tr>
