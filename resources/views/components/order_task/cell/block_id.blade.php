@can('order_task_view')
    <a href="{{ route('order_task.detail', $task->block_id) }}">{{ $task->block_id }}</a>
@else
    {{ $task->block_id }}
@endcan
