<div class="dropdown-action">
    <div class="dropdown todo-action-dropdown">
        <button class=" btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-options-vertical"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item" href="{{ route('proposal.edit', [$row, $row->iteration]) }}">
                <i class="fas fa-edit text-warning me-2"></i> Редактировать
            </a>
            <a class="dropdown-item" href="javascript:row_delete('{{ route('api.proposal.delete', [$row, $row->iteration]) }}')">
                <i class="fas fa-trash text-danger me-2"></i> Удалить
            </a>

            @if($row->iteration > 1)
                <a class="dropdown-item" href="javascript:sidebar({ href: '{{ route('proposal.sidebar_iterations', [$row, $row->iteration]) }}'})">
                    <i class="fas fa-copy text-primary me-2"></i> Посмотреть редакции
                </a>
            @endif
        </div>
    </div>
</div>
