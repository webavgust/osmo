<div class="dropdown-action">
    <div class="dropdown todo-action-dropdown">
        <button class=" btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button"  data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <i class="icon-options-vertical"></i>
        </button>
        <div class="dropdown-menu">
            @if($order_task->canEdit())
                <a class="dropdown-item" href="{{ route('order_task.edit_step1', $order_task) }}"><i class="fas fa-edit text-warning me-2"></i>Редактировать</a>
            @endif
            @if($order_task->canAgree())
                <a class="dropdown-item agree" href="javascript:sidebar({href:'{{ route('order_task.agreement.form', $order_task) }}'});"><i class="fas fa-users text-primary me-2"></i>Согласовать</a>
            @endif
            @if($order_task->canAgreeView())
                <a class="dropdown-item agree_view" href="javascript:sidebar({href:'{{ route('order_task.agreement.view', $order_task) }}'});"><i class="fas fa-users text-warning me-2"></i>Согласованты</a>
            @endif
            @if($order_task->canAttach())
                <a class="dropdown-item attach" href="javascript:sidebar({href:'{{ route('order_task.attach.form', $order_task) }}'})"><i class="fas fa-link text-secondary me-2"></i>Прикрепить к заявке</a>
            @endif
            @if($order_task->canViewHistory())
                    <a class="dropdown-item attach" href="javascript:void(0)"><i class="fas fa-rectangle-vertical-history text-info me-2"></i>История</a>
            @endif
            @if($order_task->canFinish())
                <x-ui.a.ajax>
                    <i class="fas fa-xmark text-danger ms-2 me-1"></i> Завершить
                </x-ui.a.ajax>
                <a class="dropdown-item attach" href="javascript:void(0)"><i class="fas fa-xmark text-danger ms-2 me-1"></i> Завершить</a>
            @endif
            @if($order_task->canDelete())
                    <a class="dropdown-item attach" href="javascript:void(0)"><i class="fas fa-xmark text-danger ms-2 me-1"></i> Удалить</a>
            @endif
        </div>
    </div>
</div>
