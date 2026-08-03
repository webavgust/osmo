
@can('contract_view')
    <a href="{{ route('sub_contract.detail', [$task->sub_contract->contract_id, $task->sub_contract]) }}">{{ $task->sub_contract->slug }}</a>
@else
    {{ $task->sub_contract->slug }}
@endif
