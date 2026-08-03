<tr task_id="{{ $task->id }}">
    <td><x-education_task.cell.id :task="$task"></x-education_task.cell.id></td>
    <td><x-education_task.status :order-task="$task"></x-education_task.status></td>
    <td>{{ \App\Models\User::find($task->education_application->portal_data['manager_id'])->full_name }}</td>
    <td>{{ $task->creator->fullName }}</td>
    <td>{{ _date($task->created_at) }}</td>
    <td>
        <div class="d-flex align-items-center justify-content-end">
            <a href="{{ route('education-task.detail', $task) }}" class="btn waves-effect waves-light btn-outline-primary d-flex align-items-center justify-content-between">
                Подробнее <i class="fa-regular fa-arrow-right ms-2"></i>
            </a>
        </div>
    </td>
</tr>
