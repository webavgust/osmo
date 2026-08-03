@if($object->canViewDetail() && (empty($selected) || $selected !== $object->id))
    <a href="{{ route('order_task_object.detail', $object) }}" class="object">
        @endif
        <div class="card mb-1 object @if(!empty($selected) && $selected == $object->id) border-3 border-info @endif  "
             id="{{ $object->id }}">
            <div class="card-body p-2 px-3">
                    @if($short)
                        <h6 class="d-flex ">
                            @if($has_samplers > 0)
                                @if($object->isFinished())
                                    <x-ui.icon.solid icon="fa-circle-check" class="text-success me-2"></x-ui.icon.solid>
                                @endif

                                <x-ui.icon.regular icon="fa-industry" class="me-2"></x-ui.icon.regular>
                            @else
                                <span class="text-danger">
                                    <x-ui.icon.regular icon="fa-industry" class="me-2"></x-ui.icon.regular>
                                </span>
                            @endif
                            <span class="d-flex flex-column">
                                <span>{{ $object->name }}</span>
                                <span class="text-secondary mt-1 font-10" role="alert" style="opacity: .5">
                                    {!! $object->lab_object?->chain_name !!}
                                </span>
                            </span>
                        </h6>

                    @else
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mb-0">
                                @if($has_samplers)
                                    @if($object->isFinished())
                                        <x-ui.icon.solid icon="fa-circle-check" class="text-success me-2"></x-ui.icon.solid>
                                    @endif
                                    <span>
                                        <x-ui.icon.regular icon="fa-industry" class="me-2"></x-ui.icon.regular>
                                    </span>
                                @else
                                    <span class="text-danger">
                                        <x-ui.icon.regular icon="fa-industry" class="me-2"></x-ui.icon.regular>
                                    </span>
                                @endif
                                {{ $object->name }}

                            </h4>
                            <div class="d-flex justify-content-between align-items-center mt-1 mt-md-0 font-12">
                                <div class="alert text-secondary alert-light-secondary p-0 ps-2 pe-2 m-0" role="alert">
                                    {!! $object->lab_object?->chain_name !!}
                                </div>
                            </div>
                        </div>
                    @endif





                @if(empty($short) )
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            {{--                        <x-education_task_course.status :course="$object"></x-education_task_course.status>--}}
                            <div>
                                @if(!$has_samplers)
                                    <x-ui.notification.regular type="danger" class="text-warning px-2 py-1 alert-light-warning font-12" onclick="javascript:function() { e.preventDefault(); }">
                                        <x-ui.icon.solid icon="fa-triangle-exclamation" class=" me-1"></x-ui.icon.solid>
                                        <span class="fw-normal">Не на все точки назначен пробоотборщик</span>
                                    </x-ui.notification.regular>
                                @endif
                            </div>
                            <div class="text-dark d-flex align-items-center justify-content-between ms-3">
                                       <span>
                                           <x-ui.icon.solid icon="fa-location-dot"></x-ui.icon.solid>
                                           <span class="fs-4 fw-bold">{{ $object->addresses->count() }}</span>
                                       </span>

                                <span class="ms-3 text-danger">
                                           <x-ui.icon.solid icon="fa-map-pin"></x-ui.icon.solid>
                                           <span class="fs-4 fw-bold">{{ $points_count }}</span>
                                       </span>

                                <span class="ms-3 text-info">
                                           <x-ui.icon.solid icon="fa-users"></x-ui.icon.solid>
                                           <span class="fs-4 fw-bold">{{ $samplers->count() }}</span>
                                       </span>
                            </div>
                        </div>
                @endif

            @if($selected == $object->id)
                <div class="card-table mt-3 mb-3">
                    @if(_can('super_user'))
                        <x-ui.card.card_table_tr field="ID"
                                                 value="{{ $object->id }}"></x-ui.card.card_table_tr>
                    @endif

                        <x-ui.card.card_table_tr field="Акты">
                            <a href="#">?</a>
                        </x-ui.card.card_table_tr>
                </div>

{{--                        <x-ui.card.card_table_tr field="Нужно предоставить доступ">--}}
{{--                            @if($object->need_share == 1)--}}
{{--                                <x-ui.icon.regular icon="fa-check text-success"></x-ui.icon.regular>--}}
{{--                            @else--}}
{{--                                <x-ui.icon.regular icon="fa-xmark text-danger"></x-ui.icon.regular>--}}
{{--                            @endif--}}
{{--                        </x-ui.card.card_table_tr>--}}

{{--                        @if($object->need_share)--}}
{{--                            <x-ui.card.card_table_tr field="Доступ предоставлен">--}}
{{--                                @if($object->is_shared)--}}
{{--                                    <x-ui.icon.regular icon="fa-check text-success"></x-ui.icon.regular>--}}
{{--                                @else--}}
{{--                                    <x-ui.icon.regular icon="fa-xmark text-danger"></x-ui.icon.regular>--}}
{{--                                @endif--}}
{{--                            </x-ui.card.card_table_tr>--}}
{{--                        @endif--}}

{{--                        <x-ui.card.card_table_tr field="Форма обучения">--}}
{{--                            {{ \App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse::TYPES[$object->course_type]['name'] }}--}}
{{--                        </x-ui.card.card_table_tr>--}}

{{--                        <x-ui.card.card_table_tr field="Слушателей">--}}
{{--                            {{ $object->clients->count() }} / {{ $object->count }}--}}
{{--                        </x-ui.card.card_table_tr>--}}

{{--                        <x-ui.card.card_table_tr field="Стоимость обучения">--}}
{{--                            {{ tools()->cost_normalize($object->cost) }} ₽--}}
{{--                        </x-ui.card.card_table_tr>--}}

{{--                        <x-ui.card.card_table_tr field="Учебный центр">--}}
{{--                            {{ $object->education_center->name }}--}}
{{--                        </x-ui.card.card_table_tr>--}}

{{--                        <x-ui.card.card_table_tr field="Желаемая дата начала">--}}
{{--                            {{ _date($object->date_preferred) }}--}}
{{--                        </x-ui.card.card_table_tr>--}}

{{--                        <x-ui.card.card_table_tr field="Сумма заказа">--}}
{{--                            <x-ui.badge.light type="success font-14">--}}
{{--                                {{ tools()->cost_normalize($object->cost_total) }} ₽--}}
{{--                            </x-ui.badge.light>--}}
{{--                        </x-ui.card.card_table_tr>--}}


{{--                        @if($object->hasPlanCost())--}}
{{--                            <x-ui.card.card_table_tr field="Плановая стоимость" class="mt-4">--}}
{{--                                <x-ui.badge.light type="secondary font-14" class="cursor-pointer"--}}
{{--                                                  onclick="javascript:box({href:'{{ route('education-task-course.box_plan', $object) }}'})">--}}
{{--                                    {{ tools()->cost_normalize($object->cost_plan) }} ₽--}}
{{--                                </x-ui.badge.light>--}}
{{--                            </x-ui.card.card_table_tr>--}}
{{--                        @endif--}}

{{--                        <x-ui.card.card_table_tr field="Текущая стоимость" class="mt-4">--}}
{{--                            <x-ui.badge.light type="info font-14">--}}
{{--                                {{ tools()->cost_normalize($object->cost_current) }} ₽--}}
{{--                            </x-ui.badge.light>--}}
{{--                        </x-ui.card.card_table_tr>--}}


{{--                        @if(auth()->user()->isAdmin())--}}
{{--                            @if($object->education_task->is_calculated)--}}
{{--                                <div class="tr">--}}
{{--                                        <span class="th">--}}
{{--                                            <x-ui.icon.duotone icon="fa-angles-right" class="me-1"></x-ui.icon.duotone>--}}
{{--                                            ЗП руководителя (точная)--}}
{{--                                        </span>--}}
{{--                                    <span class="td">--}}
{{--                                            <a href="{{ route('calculation.supervisor', $object->education_task) }}">--}}
{{--                                                <x-ui.badge.light type="success font-14">--}}
{{--                                                    {{ tools()->cost_normalize($object->supervisor_salary) }} ₽--}}
{{--                                                </x-ui.badge.light>--}}
{{--                                            </a>--}}
{{--                                        </span>--}}
{{--                                </div>--}}
{{--                            @else--}}
{{--                                <div class="tr">--}}
{{--                                        <span class="th">--}}
{{--                                            <x-ui.icon.duotone icon="fa-angles-right" class="me-1"></x-ui.icon.duotone>--}}
{{--                                            ЗП руководителя (расчётная)--}}
{{--                                        </span>--}}
{{--                                    <span class="td">--}}
{{--                                            <x-ui.badge.light type="danger font-14">--}}
{{--                                                {{ tools()->cost_normalize($object->supervisor_salary) }} ₽--}}
{{--                                            </x-ui.badge.light>--}}
{{--                                        </span>--}}
{{--                                </div>--}}
{{--                            @endif--}}

{{--                            @if($object->education_task->is_tender)--}}
{{--                                @if($object->education_task->is_calculated)--}}
{{--                                    <div class="tr">--}}
{{--                                            <span class="th">--}}
{{--                                                <x-ui.icon.duotone icon="fa-angles-right"--}}
{{--                                                                   class="me-1"></x-ui.icon.duotone>--}}
{{--                                                ЗП тендера (точная)--}}
{{--                                            </span>--}}
{{--                                        <span class="td">--}}
{{--                                                <x-ui.badge.light type="success font-14">--}}
{{--                                                    {{ tools()->cost_normalize($object->tender_salary) }} ₽--}}
{{--                                                </x-ui.badge.light>--}}
{{--                                            </span>--}}
{{--                                    </div>--}}
{{--                                @else--}}
{{--                                    <div class="tr">--}}
{{--                                            <span class="th">--}}
{{--                                                <x-ui.icon.duotone icon="fa-angles-right"--}}
{{--                                                                   class="me-1"></x-ui.icon.duotone>--}}
{{--                                                ЗП тендера (расчётная)--}}
{{--                                            </span>--}}
{{--                                        <span class="td">--}}
{{--                                                <x-ui.badge.light type="danger font-14">--}}
{{--                                                    {{ tools()->cost_normalize($object->tender_salary) }} ₽--}}
{{--                                                </x-ui.badge.light>--}}
{{--                                            </span>--}}
{{--                                    </div>--}}
{{--                                @endif--}}
{{--                            @endif--}}
{{--                        @endif--}}


{{--                        @if(!empty($object->protocol))--}}
{{--                            <x-ui.card.card_table_tr field="Протокол" class="mt-4">--}}
{{--                                <x-ui.badge.light type="warning font-14">--}}
{{--                                    <b>{{ $object->protocol->number->number }}</b>--}}
{{--                                    от {{  _date($object->protocol->date) }}--}}
{{--                                </x-ui.badge.light>--}}
{{--                            </x-ui.card.card_table_tr>--}}
{{--                        @endif--}}

{{--                        @if(!empty($object->expired_at))--}}
{{--                            <x-ui.card.card_table_tr field="Срок удостоверений">--}}
{{--                                {{ _date($object->expired_at) }}--}}
{{--                            </x-ui.card.card_table_tr>--}}

{{--                            @if(!empty($object->expired_notify))--}}
{{--                                @if(empty($object->expired_notified_at))--}}
{{--                                    <div--}}
{{--                                        class="text-warning d-flex align-items-center font-12 mt-2 justify-content-end">--}}
{{--                                        <x-ui.icon.duotone icon="fa-triangle-exclamation"></x-ui.icon.duotone>--}}
{{--                                        <span--}}
{{--                                            class="ms-1">Уведомление будет отправлено <strong>{{ _date($object->expired_at->subMonth()) }}</strong></span>--}}
{{--                                    </div>--}}
{{--                                @else--}}
{{--                                    <div--}}
{{--                                        class="text-success d-flex align-items-center font-12 mt-2 justify-content-end">--}}
{{--                                        <x-ui.icon.duotone icon="fa-triangle-exclamation"></x-ui.icon.duotone>--}}
{{--                                        <span class="ms-1">Уведомление было отправлено <strong>{{ _date($object->expired_notified_at) }}</strong></span>--}}
{{--                                    </div>--}}
{{--                                @endif--}}

{{--                                <div class="d-flex align-items-center font-12 justify-content-end">--}}
{{--                                    @foreach($object->expired_notify as $slug)--}}
{{--                                        @php--}}
{{--                                            switch($slug) {--}}
{{--                                                case \App\Modules\Pub\Course\Models\Course::COURSE_EXPIRED__SUPERVISOR:--}}
{{--                                                    $email = $object->education_task->supervisor?->email; break;--}}
{{--                                                case \App\Modules\Pub\Course\Models\Course::COURSE_EXPIRED__METHODIST:--}}
{{--                                                    $email = $object->education_task->methodist?->email; break;--}}
{{--//                                                case \App\Modules\Pub\Course\Models\Course::COURSE_EXPIRED__CONTACT:--}}
{{--//                                                    $email = $object->education_task->contact_email; break;--}}
{{--                                            }--}}
{{--                                        @endphp--}}
{{--                                        <x-ui.badge.light type="secondary" class="ms-1 px-1">--}}
{{--                                            <span title="{{ $email }}" class="cursor-help">{{ \App\Modules\Pub\Course\Models\Course::COURSE_EXPIRED_DATA[$slug]['name_d'] }}</span>--}}
{{--                                        </x-ui.badge.light>--}}
{{--                                    @endforeach--}}
{{--                                </div>--}}
{{--                            @else--}}
{{--                                <div class="text-danger d-flex align-items-center font-12 mt-2 justify-content-end">--}}
{{--                                    <x-ui.icon.duotone icon="fa-triangle-exclamation"></x-ui.icon.duotone>--}}
{{--                                    <span class="ms-1">Уведомление отправлено не будет</span>--}}
{{--                                </div>--}}
{{--                            @endif--}}

{{--                        @endif--}}
{{--                    </div>--}}

{{--                    <x-education_task_course.detail.status_block--}}
{{--                        :course="$object"></x-education_task_course.detail.status_block>--}}
{{--                </div>--}}

            @endif

                    <div class="mt-4 mb-2 status_block d-flex flex-column flex-grow-1">
                        <x-ui.a.box btn_type="light-secondary" class="text-secondary" :href="route('order_task_object.box_summary', $object)">
                            <x-ui.icon.light icon="fa-table" class="me-1"></x-ui.icon.light>
                            Сводная таблица
                        </x-ui.a.box>
                    </div>

            </div>


            <x-order_task.progress :progress="$progress" :short="!empty($selected)"></x-order_task.progress>

        </div>
        @if($object->canViewDetail() && (empty($selected) || $selected !== $object->id))
    </a>
@endif
