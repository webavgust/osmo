@can('education_application_view')
    <a href="{{ route('education-task.detail', [$task->id]) }}">{{ $task->id }}</a>
@else
    {{ $task->id }}
@endcan
