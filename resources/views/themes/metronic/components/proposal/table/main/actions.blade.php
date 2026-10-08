@php
    // patch v45: удалять / восстанавливать КП может только Proposal::canDelete()
    $can_delete = \App\Modules\Pub\Proposal\Models\Proposal::canDelete();
@endphp
<div class="dropdown-action">
    <div class="dropdown todo-action-dropdown">
        <button class=" btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa-light fa-ellipsis-vertical"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            @if($row->trashed())
                @if($can_delete)
                    <a class="dropdown-item" href="javascript:row_restore('{{ route('api.proposal.restore', $row->group) }}')">
                        <i class="fas fa-trash-arrow-up text-success me-2"></i> Восстановить
                    </a>
                @endif
            @else
                <a class="dropdown-item" href="{{ route('proposal.edit', [$row, $row->iteration]) }}">
                    <i class="fas fa-edit text-warning me-2"></i> Редактировать
                </a>
                @if($can_delete)
                    <a class="dropdown-item" href="javascript:row_delete('{{ route('api.proposal.delete', [$row, $row->iteration]) }}')">
                        <i class="fas fa-trash text-danger me-2"></i> Удалить
                    </a>
                @endif
            @endif

            @if($row->iteration > 1 && !$row->trashed())
                <a class="dropdown-item" href="javascript:sidebar({ href: '{{ route('proposal.sidebar_iterations', [$row, $row->iteration]) }}'})">
                    <i class="fas fa-copy text-primary me-2"></i> Посмотреть редакции
                </a>
            @endif
        </div>
    </div>
</div>
