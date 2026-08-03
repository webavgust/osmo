@if($task->hasAccess())
    <div class="mt-2 mb-2 status_block d-flex flex-column flex-grow-1">
        @if($task->canEdit())
            <x-ui.a.default href="{{ route('order_task.edit_step1', $task) }}" btn_type="warning flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-edit" class="me-1"></x-ui.icon.regular>
                @if($task->status == \App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_STARTED)
                    Продолжить создание
                @else
                    Редактировать
                @endif
            </x-ui.a.default>
        @endif

        @if($task->canAgree())
            <x-ui.a.default href="javascript:void(0);" onclick="javascript:sidebar({href:'{{ route('order_task.agreement.form', $task) }}'});" btn_type="primary flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-users" class="me-1"></x-ui.icon.regular>
                Отправить на согласование
            </x-ui.a.default>
        @endif

        @if($task->canAttach())
            <x-ui.a.default href="javascript:void(0);" onclick="javascript:sidebar({href:'{{ route('order_task.attach.form', $task) }}'});" btn_type="secondary flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-users" class="me-1"></x-ui.icon.regular>
                Прикрепить заявку (убрать)
            </x-ui.a.default>
        @endif

        @if($task->canStartWorking())
            <x-ui.a.ajax url="{{ route('api.order_task.start_working', $task) }}" btn_type="primary flex-grow-1 mb-1" :redirect="route('order_task.index')" submit-message="Вы действительно хотите запустить ТЗ в работу?">
                <x-ui.icon.regular icon="fa-play" class="me-1"></x-ui.icon.regular>
                Передать в работу
            </x-ui.a.ajax>
        @endif


        @if($task->canViewHistory())
            <x-ui.a.default href="javascript:void(0);" onclick="javascript:void(0);" btn_type="info flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-rectangle-vertical-history" class="me-1"></x-ui.icon.regular>
                История
            </x-ui.a.default>
        @endif

        @if($task->canDelete())
            <x-ui.a.outline href="javascript:void(0);" onclick="javascript:void(0);" btn_type="danger flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-xmark" class="me-1"></x-ui.icon.regular>
                Удалить
            </x-ui.a.outline>
        @endif

        @if($task->canFinish())
            <x-ui.a.ajax url="{{ route('api.order_task.finish', $task) }}" btn_type="primary flex-grow-1 mb-1" reload="1" submit-message="Вы действительно хотите завершить работу над ТЗ?">
                <x-ui.icon.regular icon="fa-flag-checkered" class="me-1"></x-ui.icon.regular>
                Завершить работу
            </x-ui.a.ajax>
        @endif


        {{-- Решение менеджера при отказе --}}
        @switch($task->status)
            @case(\App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_DECLINED)
                    <div class="d-flex mt-3">
                        <x-ui.button.default btn_type="danger flex-grow-1 me-1" id="order_archive">
                            <x-ui.icon.regular icon="fa-box-archive" class="me-1"></x-ui.icon.regular>
                            В архив
                        </x-ui.button.default>

                        @if($task->canRemake())
                            <x-ui.a.default btn_type="success flex-grow-1 ms-1" id="order_recreate">
                                <x-ui.icon.regular icon="fa-arrows-rotate" class="me-1"></x-ui.icon.regular>
                                Пересоздать
                            </x-ui.a.default>
                        @endif
                    </div>
                @break
        @endswitch

    </div>
@endif
