@if($course->canModify())
    <div class="mt-4 mb-2 status_block d-flex flex-column flex-grow-1">
        @if($course->canDoStep1())
            <x-ui.a.default onclick="javascript:do_step1();" btn_type="success flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-angles-right" class="me-1"></x-ui.icon.regular>
                Зафиксировать стоимость
            </x-ui.a.default>
        @endif


        @if($course->canFinish())
            <x-ui.a.default onclick="javascript:do_finish();" btn_type="primary" class=" flex-grow-1 mb-1">
                <x-ui.icon.light icon="fa-flag-checkered" class="me-1"></x-ui.icon.light>
                Завершить работу
            </x-ui.a.default>
        @endif



        @if($course->canDoStep2())
            @if($course->study_group->isOver())
                <x-ui.a.default onclick="javascript:do_step2();" btn_type="success" class=" flex-grow-1 mb-1">
                    <x-ui.icon.regular icon="fa-angles-right" class="me-1"></x-ui.icon.regular>
                    Перейти к генерации документов
                </x-ui.a.default>
            @else
                <x-ui.a.default onclick="javascript:do_step2();" btn_type="warning" class=" flex-grow-1 mb-1">
                    <x-ui.icon.regular icon="fa-angles-right" class="me-1"></x-ui.icon.regular>
                    Перейти к генерации документов
                    <x-ui.icon.light icon="fa-unlock-keyhole" class="ms-3"></x-ui.icon.light> админ
                </x-ui.a.default>
            @endif
        @elseif($course->canBackToStep1())
            <x-ui.a.default onclick="javascript:back_step1();" btn_type="warning" class="flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-angles-left" class="me-1"></x-ui.icon.regular>
                Вернуться на прошлый этап
            </x-ui.a.default>
        @endif

        @if($course->canShare())
                <x-ui.a.default onclick="javascript:do_share();" btn_type="info" class="flex-grow-1 mb-1">
                    <x-ui.icon.light icon="fa-fingerprint" class="me-1"></x-ui.icon.light>
                    Предоставить доступ
                </x-ui.a.default>
        @endif

        @if($course->canUnfinished())
                <x-ui.a.default onclick="javascript:do_unfinished();" btn_type="danger" class="flex-grow-1 mb-1">
                    <x-ui.icon.light icon="fa-triangle-exclamation" class="me-1"></x-ui.icon.light>
                    Вернуть в работу
                    <x-ui.icon.light icon="fa-unlock-keyhole" class="ms-3"></x-ui.icon.light> админ
                </x-ui.a.default>
        @endif

        @if($course->canDelete())
            @if($course->deleteWithBlanks())
                <x-ui.a.box href="{{ route('education-task-course.box_delete', $course) }}" btn_type="outline-danger">
                    <x-ui.icon.regular icon="fa-xmark" class="me-1"></x-ui.icon.regular>
                    Отменить
                </x-ui.a.box>
            @else
                <x-ui.a.outline href="javascript:void(0);" onclick="javascript:delete_confirm();" btn_type="danger flex-grow-1 mb-1">
                    <x-ui.icon.regular icon="fa-xmark" class="me-1"></x-ui.icon.regular>
                    Отменить
                </x-ui.a.outline>
            @endif
        @endif

        @if(auth()->user()->isAdmin())
            @if($course->hasPlanCost())
                <x-ui.a.ajax url="{{ route('api.education-task-course.sync_plan', $course) }}" btn_type="outline-secondary" message="Плановая стоимость успешно отправлена!" class="mt-3">
                    <x-ui.icon.regular icon="fa-arrows-rotate" class="me-1"></x-ui.icon.regular>
                    ПЛАН

                    <x-ui.icon.light icon="fa-unlock-keyhole" class="ms-3"></x-ui.icon.light> админ
                </x-ui.a.ajax>
            @endif

            @if($course->isFinished())
                <x-ui.a.ajax url="{{ route('api.education-task-course.sync_fact', $course) }}" btn_type="outline-secondary" message="Фактическая стоимость успешно отправлена!" class="mt-2">
                    <x-ui.icon.regular icon="fa-arrows-rotate" class="me-1"></x-ui.icon.regular>
                    ФАКТ
                    <x-ui.icon.light icon="fa-unlock-keyhole" class="ms-3"></x-ui.icon.light> админ
                </x-ui.a.ajax>
            @endif
        @endif
    </div>
@endif
