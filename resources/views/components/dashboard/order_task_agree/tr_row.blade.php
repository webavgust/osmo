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
    <td width="1">
        @switch($task->status_decorate['chr'])
            @case(\App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_STARTED)
                @can('order_task_agree')
                    <x-ui.a.outline btn_type="secondary" href="{{ route('order_task.create_step2', $task) }}">
                        <i class="fa-duotone fa-pen-to-square"></i>
                        Продолжить
                    </x-ui.a.outline>
                @endcan
            @break
            @case(\App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_CREATED)
                @can('order_task_agree')
                    <x-ui.a.outline btn_type="primary" href="javascript:sidebar({href:'{{ route('order_task.agreement.form', $task) }}'})">
                        <i class="fa-duotone fa-users me-2"></i>
                        Согласовать
                    </x-ui.a.outline>
                @endcan
            @break
            @case(\App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_AGREEMENT)
                @can('order_task_agree')
                    <x-ui.a.outline btn_type="warning" href="javascript:sidebar({href:'{{ route('order_task.agreement.view', $task) }}'})">
                        <i class="fa-duotone fa-users me-2"></i>
                        Согласованты
                    </x-ui.a.outline>
                @endcan
            @break
        @endswitch
    </td>
</tr>
