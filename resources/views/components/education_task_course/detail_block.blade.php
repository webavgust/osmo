@if($course->canViewDetail() && (empty($selected) || $selected !== $course->id))
    <a href="{{ route('education-task-course.detail', $course) }}" class="course">
@endif
        <div class="card mb-1 course @if(!empty($selected) && $selected == $course->id) border-3 border-info @endif  "
             id="{{ $course->id }}">
            <div class="card-body p-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fs-3 fw-bold text-dark">{{ $course->course->name_duration ?? '' }}</span>
                </div>

                <div class="mt-2 d-flex justify-content-between align-items-center">

                    <div>
                        <x-education_task_course.status :course="$course"></x-education_task_course.status>
                    </div>

                    <span class="text-dark d-flex align-items-center">
                        <x-ui.badge.light type="secondary" class="me-2">
                            {{ $course_type }}
                        </x-ui.badge.light>

                        <x-ui.icon.solid icon="fa-person"></x-ui.icon.solid>
                            <span class="fs-4 fw-bold ">{{ $course->clients->count() }} / {{ $course->count }}</span>
                    </span>

                </div>

                @if($course->isDeleted() && ($course->spoiled_document_blanks->isNotEmpty() || $course->spoiled_works->isNotEmpty()))

                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <x-ui.a.sidebar href="{{ route('education-task-course.sidebar_spoiled', $course) }}">
                            @if($course->spoiled_document_blanks->isNotEmpty())
                                <x-ui.badge.light type="danger">
                                    {{ $course->spoiled_document_blanks->count() }}
                                    <x-ui.icon.solid icon="fa-file" class="ms-1"></x-ui.icon.solid>
                                </x-ui.badge.light>
                            @endif

                            @if($course->spoiled_works->isNotEmpty())
                                <x-ui.badge.light type="danger">
                                    {{ $course->spoiled_works->count() }}
                                    <x-ui.icon.solid icon="fa-helmet-safety" class="ms-1"></x-ui.icon.solid>
                                </x-ui.badge.light>
                            @endif

                            <x-ui.badge.default type="danger">
                                = {{ tools()->cost_normalize($course->cost_spoiled) }} ₽
                            </x-ui.badge.default>

                        </x-ui.a.sidebar>
                    </div>
                @endif


                @if(empty($short))
                    <div class="my-3 text-dark">
                        <div class="card-table">
                            @if($course->canModify())
                                <x-ui.card.card_table_tr field="Выделены бланки документов">
                                    <i class="fa-solid fa-check text-success"></i>
                                </x-ui.card.card_table_tr>

                                <x-ui.card.card_table_tr field="Назначена учебная группа">
                                    @if(!empty($course->study_group))
                                        <i class="fa-solid fa-check text-success"></i>
                                    @else
                                        <i class="fa-solid fa-xmark text-danger"></i>
                                    @endif
                                </x-ui.card.card_table_tr>

                                @if($course->need_share)
                                    <x-ui.card.card_table_tr field="Предоставлен доступ">
                                        @if($course->is_shared)
                                            <i class="fa-solid fa-check text-success"></i>
                                        @else
                                            <i class="fa-solid fa-xmark text-danger"></i>
                                        @endif
                                    </x-ui.card.card_table_tr>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($selected == $course->id)
                <div class="card-body p-3">
                    <div class="card-table">
                        @if(_can('super_user'))
                            <x-ui.card.card_table_tr field="ID"
                                                     value="{{ $course->id }}"></x-ui.card.card_table_tr>
                        @endif

                        <x-ui.card.card_table_tr field="Нужно предоставить доступ">
                            @if($course->need_share == 1)
                                <x-ui.icon.regular icon="fa-check text-success"></x-ui.icon.regular>
                            @else
                                <x-ui.icon.regular icon="fa-xmark text-danger"></x-ui.icon.regular>
                            @endif
                        </x-ui.card.card_table_tr>

                        @if($course->need_share)
                                <x-ui.card.card_table_tr field="Доступ предоставлен">
                                    @if($course->is_shared)
                                        <x-ui.icon.regular icon="fa-check text-success"></x-ui.icon.regular>
                                    @else
                                        <x-ui.icon.regular icon="fa-xmark text-danger"></x-ui.icon.regular>
                                    @endif
                                </x-ui.card.card_table_tr>
                        @endif

                        <x-ui.card.card_table_tr field="Форма обучения">
                            {{ \App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse::TYPES[$course->course_type]['name'] }}
                        </x-ui.card.card_table_tr>

                        <x-ui.card.card_table_tr field="Слушателей">
                            {{ $course->clients->count() }} / {{ $course->count }}
                        </x-ui.card.card_table_tr>

                        <x-ui.card.card_table_tr field="Стоимость обучения">
                            {{ tools()->cost_normalize($course->cost) }} ₽
                        </x-ui.card.card_table_tr>

                        <x-ui.card.card_table_tr field="Учебный центр">
                            {{ $course->education_center->name }}
                        </x-ui.card.card_table_tr>

                        <x-ui.card.card_table_tr field="Желаемая дата начала">
                            {{ _date($course->date_preferred) }}
                        </x-ui.card.card_table_tr>

                        <x-ui.card.card_table_tr field="Сумма заказа">
                            <x-ui.badge.light type="success font-14">
                                {{ tools()->cost_normalize($course->cost_total) }} ₽
                            </x-ui.badge.light>
                        </x-ui.card.card_table_tr>


                        @if($course->hasPlanCost())
                            <x-ui.card.card_table_tr field="Плановая стоимость" class="mt-4">
                                    <x-ui.badge.light type="secondary font-14" class="cursor-pointer" onclick="javascript:box({href:'{{ route('education-task-course.box_plan', $course) }}'})">
                                        {{ tools()->cost_normalize($course->cost_plan) }} ₽
                                    </x-ui.badge.light>
                            </x-ui.card.card_table_tr>
                        @endif

                        <x-ui.card.card_table_tr field="Текущая стоимость" class="mt-4">
                            <x-ui.badge.light type="info font-14" >
                                {{ tools()->cost_normalize($course->cost_current) }} ₽
                            </x-ui.badge.light>
                        </x-ui.card.card_table_tr>


                        @if(auth()->user()->isAdmin())
                            @if($course->education_task->is_calculated)
                                    <div class="tr">
                                        <span class="th">
                                            <x-ui.icon.duotone icon="fa-angles-right" class="me-1"></x-ui.icon.duotone>
                                            ЗП руководителя (точная)
                                        </span>
                                        <span class="td">
                                            <a href="{{ route('calculation.supervisor', $course->education_task) }}">
                                                <x-ui.badge.light type="success font-14">
                                                    {{ tools()->cost_normalize($course->supervisor_salary) }} ₽
                                                </x-ui.badge.light>
                                            </a>
                                        </span>
                                    </div>
                            @else
                                    <div class="tr">
                                        <span class="th">
                                            <x-ui.icon.duotone icon="fa-angles-right" class="me-1"></x-ui.icon.duotone>
                                            ЗП руководителя (расчётная)
                                        </span>
                                        <span class="td">
                                            <x-ui.badge.light type="danger font-14">
                                                {{ tools()->cost_normalize($course->supervisor_salary) }} ₽
                                            </x-ui.badge.light>
                                        </span>
                                    </div>
                            @endif

                            @if($course->education_task->is_tender)
                                    @if($course->education_task->is_calculated)
                                        <div class="tr">
                                            <span class="th">
                                                <x-ui.icon.duotone icon="fa-angles-right" class="me-1"></x-ui.icon.duotone>
                                                ЗП тендера (точная)
                                            </span>
                                                <span class="td">
                                                <x-ui.badge.light type="success font-14">
                                                    {{ tools()->cost_normalize($course->tender_salary) }} ₽
                                                </x-ui.badge.light>
                                            </span>
                                        </div>
                                    @else
                                        <div class="tr">
                                            <span class="th">
                                                <x-ui.icon.duotone icon="fa-angles-right" class="me-1"></x-ui.icon.duotone>
                                                ЗП тендера (расчётная)
                                            </span>
                                                <span class="td">
                                                <x-ui.badge.light type="danger font-14">
                                                    {{ tools()->cost_normalize($course->tender_salary) }} ₽
                                                </x-ui.badge.light>
                                            </span>
                                        </div>
                                    @endif
                            @endif
                        @endif


                        @if(!empty($course->protocol))
                            <x-ui.card.card_table_tr field="Протокол">
                                <x-ui.badge.light type="warning font-14">
                                       <b>{{ $course->protocol->number->number }}</b> от {{  _date($course->protocol->date) }}
                                </x-ui.badge.light>
                            </x-ui.card.card_table_tr>
                        @endif
                    </div>

                    <x-education_task_course.detail.status_block :course="$course"></x-education_task_course.detail.status_block>
                </div>
            @endif
        </div>
@if($course->canViewDetail())
    </a>
@endif
