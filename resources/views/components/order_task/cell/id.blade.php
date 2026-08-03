@can('order_task_view')
    <a href="{{ route('order_task.detail', [$task->id]) }}">{{ $task->id }}</a>
@else
    {{ $task->id }}
@endcan
