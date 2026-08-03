@can('education_application_view')
    <a href="{{ route('education_application.detail', $task->block_id) }}">{{ $task->block_id }}</a>
@else
    {{ $task->block_id }}
@endcan
