@if($task->canView())
    <div class="mt-2 mb-2 status_block d-flex flex-column flex-grow-1">

        @if(!empty($task->education_task) && $task->education_task->hasAccess())
            <x-ui.a.default href="{{ route('education-task.detail', $task->education_task) }}" btn_type="success flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-edit" class="me-1"></x-ui.icon.regular>
                    Перейти в ТЗ
            </x-ui.a.default>
        @endif

        @if($task->canEdit())
            <x-ui.a.default href="{{ route('education_application.edit', $task) }}" btn_type="warning flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-edit" class="me-1"></x-ui.icon.regular>
                @if($task->status == \App\Modules\Pub\EducationApplication\Models\EducationApplication::STATUS_INIT)
                    Продолжить создание
                @else
                    Редактировать
                @endif
            </x-ui.a.default>
        @endif

        @if($task->canAgree())
            <x-ui.a.default href="javascript:void(0);" onclick="javascript:agree_confirm();" btn_type="primary flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-users" class="me-1"></x-ui.icon.regular>
            Отправить на согласование
            </x-ui.a.default>
        @endif

        @if($task->canAttach())
            <x-ui.a.default href="javascript:void(0);" onclick="javascript:sidebar({href:'{{ route('education_application.attach.form', $task) }}'});" btn_type="secondary flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-users" class="me-1"></x-ui.icon.regular>
                Прикрепить
            </x-ui.a.default>
        @endif


        @if($task->canViewHistory())
            <x-ui.a.default href="javascript:void(0);" onclick="javascript:void(0);" btn_type="info flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-rectangle-vertical-history" class="me-1"></x-ui.icon.regular>
                История
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

        {{-- Решение менеджера при отказе --}}
        @switch($task->status)
            @case(\App\Modules\Pub\EducationApplication\Models\EducationApplication::STATUS_DECLINED)
                    <div class="d-flex mt-3">
                        @if($task->canArchieve())
                            <x-ui.button.default btn_type="danger flex-grow-1 me-1" id="order_archive">
                                <x-ui.icon.regular icon="fa-box-archive" class="me-1"></x-ui.icon.regular>
                                В архив
                            </x-ui.button.default>
                        @endif

                        <x-ui.a.default btn_type="success flex-grow-1 ms-1" id="order_recreate">
                            <x-ui.icon.regular icon="fa-arrows-rotate" class="me-1"></x-ui.icon.regular>
                            Пересоздать
                        </x-ui.a.default>
                    </div>
                @break
        @endswitch

    </div>
@endif
