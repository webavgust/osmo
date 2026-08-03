@if($task->hasAccess())
    <div class="mt-2 mb-2 status_block d-flex flex-column flex-grow-1">
        @if($task->canEdit())
            <x-ui.a.default href="{{ route('education-task.edit', $task) }}" btn_type="warning flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-edit" class="me-1"></x-ui.icon.regular>
                @if($task->status == \App\Modules\Pub\EducationTask\Models\EducationTask::STATUS_INIT)
                    Продолжить создание
                @else
                    Редактировать
                @endif
            </x-ui.a.default>
        @endif

{{--        @if($task->canTransfer())--}}
        @if($task->canSendToAgreementFO())
            <x-ui.a.default onclick="javascript:transfer_confirm();" btn_type="success flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-angles-right" class="me-1"></x-ui.icon.regular>
                Согласовать в ком.отделе
            </x-ui.a.default>
        @endif

        @if($task->canDelete())
            <x-ui.a.outline href="javascript:void(0);" onclick="javascript:delete_confirm();" btn_type="danger flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-xmark" class="me-1"></x-ui.icon.regular>
                Удалить
            </x-ui.a.outline>
        @elseif($task->canRestore())
            <x-ui.a.outline href="javascript:void(0);" onclick="javascript:restore_confirm();" btn_type="success flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-briefcase-medical" class="me-1"></x-ui.icon.regular>
                Восстановить
            </x-ui.a.outline>
        @endif

        @if($task->canAppoint())
            @if($task->isRefused())

            @endif

            <x-ui.a.sidebar type="danger" class="btn waves-effect waves-light btn-danger" href="{{ route('education-task.sidebar_appoint', $task) }}">
                Назначить методиста <i class="fa-solid fa-user-graduate ms-2"></i>
            </x-ui.a.sidebar>
        @endif
    </div>
@endif

