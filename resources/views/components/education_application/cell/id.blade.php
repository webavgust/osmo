@can('education_application_view')
    <a href="{{ route('education_application.detail', [$task->block_id, $task->iteration]) }}">{{ $task->id }}</a>
@else
    {{ $task->id }}
@endcan
