<tr task_id="{{ $task->id }}">
    <td>
        <x-education_application.cell.id :task="$task"></x-education_application.cell.id>
    </td>
    <td class="text-center">
        <x-education_application.cell.iteration :task="$task"></x-education_application.cell.iteration>
    </td>
    <td>
        <x-education_application.status :order-task="$task"></x-education_application.status>
    </td>
    <td>
        <x-education_application.cell.contract :task="$task"></x-education_application.cell.contract>
    </td>
    <td>
        <x-education_application.cell.sub_contract :task="$task"></x-education_application.cell.sub_contract>
    </td>
    <td>
        <x-education_application.cell.block_id :task="$task"></x-education_application.cell.block_id>
    </td>
    <td>{{ tools()->userByID($task->portal_data['manager_id'] ?? null)->full_name ?? '?' }}</td>
    <td>{{ $task->creator->fullName }}</td>
    <td>{{ _date($task->created_at) }}</td>
    <td width="1">
        @switch($task->status_decorate['chr'])
            @case(\App\Modules\Pub\EducationApplication\Models\EducationApplication::STATUS_INIT)
                @can('order_task_agree')
                    <x-ui.a.outline btn_type="secondary" href="{{ route('order_task.create_step2', $task) }}">
                        <i class="fa-duotone fa-pen-to-square"></i>
                        Продолжить
                    </x-ui.a.outline>
                @endcan
            @break
            @case(\App\Modules\Pub\EducationApplication\Models\EducationApplication::STATUS_CREATED)
                @can('order_task_agree')
                    <x-ui.a.outline btn_type="primary" href="javascript:sidebar({href:'{{ route('order_task.agreement.form', $task) }}'})">
                        <i class="fa-duotone fa-users me-2"></i>
                        Согласовать
                    </x-ui.a.outline>
                @endcan
            @break
            @case(\App\Modules\Pub\EducationApplication\Models\EducationApplication::STATUS_AGREEMENT)
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
