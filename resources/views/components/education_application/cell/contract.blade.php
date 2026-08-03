@can('contract_view')
    <a href="{{ route('contract.detail', $task->sub_contract->contract_id) }}">{{ $task->sub_contract->contract_id }}</a>
@else
    {{ $task->sub_contract->contract_id }}
@endif
