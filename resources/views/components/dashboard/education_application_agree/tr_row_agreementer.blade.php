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
    <td>
{{--        <x-education_application.cell.agreementer :task="$task"></x-education_application.cell.agreementer>--}}
    </td>
    <td>{{ tools()->userByID($task->portal_data['manager_id'] ?? null)->full_name ?? '?' }}</td>
    <td>{{ $task->creator->fullName }}</td>
    <td>{{ _date($task->created_at) }}</td>
</tr>
