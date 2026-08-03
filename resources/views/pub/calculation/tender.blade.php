@extends('layouts.layout')


@section('styles')
    <link rel="stylesheet" type="text/css"
          href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection


@section('content')
<div class="container-fluid">
    <div class="row">
            <div class="col--12 col-md-5">
                <div class="card mb-1">
                    <div
                        class="card-body d-flex justify-content-between align-items-center flex-column flex-sm-row">
                        <h4 class="card-title mb-0">Общая информация</h4>
                    </div>
                    <div class="card-body">
                        <div class="card-table">
                            @if(_can('super_user'))
                                <x-ui.card.card_table_tr field="ID"
                                                         value="{{ $education_task->id }}"></x-ui.card.card_table_tr>
                            @endif

                            <x-ui.card.card_table_tr field="Компания для работы"
                                                     value="{{ $education_task->education_application->company->name ?? '?'}}"></x-ui.card.card_table_tr>

                            <x-ui.card.card_table_tr field="Сумма договора">
                                <x-ui.badge.light type="secondary" class="font-16">{{ tools()->cost_normalize($education_task->amount) }} ₽</x-ui.badge.light>
                            </x-ui.card.card_table_tr>

                                <x-ui.card.card_table_tr field="Итого к выплате руководителю">
                                    <a href="{{ route('calculation.supervisor', $education_task) }}">
                                        <x-ui.badge.default type="secondary" class="font-16">{{ tools()->cost_normalize($education_task->supervisor_salary) }} ₽</x-ui.badge.default>
                                    </a>
                                </x-ui.card.card_table_tr>

                            <x-ui.card.card_table_tr field="Итого к выплате за тендер">
                                <x-ui.badge.default type="success" class="font-16">{{ tools()->cost_normalize($education_task->tender_salary) }} ₽</x-ui.badge.default>
                            </x-ui.card.card_table_tr>
                        </div>
                    </div>

                    @can('education_task_view')
                        <x-ui.a.outline href="{{ route('education-task.detail', $education_task) }}" btn_type="info" class="m-3 mt-1">Перейти в карточку ТЗ</x-ui.a.outline>
                    @endcan
                </div>
            </div>
            <div class="col--12 col-md-7">
                <table class="table customize-table mb-0 v-middle">
                    @foreach($salaries as $salary)
                        @php
                            $calculation_total = 0;
                        @endphp
                        <tr><th colspan="80" class="ps-1 py-2"><h4>{{ _datetime($salary->calculation->created_at) }}</h4></th></tr>
                         @foreach($education_task->education_task_courses as $course)
                            @php
                                // уроки на момент слепка
                                $lessons = \App\Modules\Pub\CalculationLesson\Repositories\CalculationLessonRepository::getForEducationTask($course, $salary->calculation);

                                // TODO: получить состояние на N дату
                                $cost_service = (float)$course->education_task_course_services()->where('updated_at', '<', $salary->calculation->created_at)->sum('total');
                                $cost_works = (float)$course->education_task_course_works()->where('updated_at', '<', $salary->calculation->created_at)->sum('total');

                                $cost_documents = 0;
                                $course->document_blanks()->where('updated_at', '<', $salary->calculation->created_at)->with('purchase')->get()->map(function ($item) use (&$cost_documents) {
                                    $cost_documents += $item->purchase->cost;
                                });
                                $supervisor_salary = \App\Modules\Pub\Salary\Models\Salary::where('target_id', $salary->target_id)
                                    ->where('type', \App\Modules\Pub\Salary\Models\Salary::TYPE_SUPERVISOR)
                                    ->where('created_at', '<=', $salary->created_at)
                                    ->sum('amount');


                                $course_cost = $cost_works + $cost_documents + $cost_service + $lessons['state']->sum('amount') + $lessons['not_state']->sum('amount');
                                $calculation_total += $course_cost;
                            @endphp
                            <tr class="bg-white">
                                <td>
                                    <h4>{{ $course->course->name_duration }}</h4>
                                            @if($lessons['state']->isNotEmpty())
                                                <x-ui.badge.light type="primary" class="cursor-help" title="Затратры на штатного преподавателя" class="me-2">
                                                    {{ $lessons['state']->count() }}
                                                    <x-ui.icon.solid icon="fa-chalkboard-user"></x-ui.icon.solid>
                                                    = {{ tools()->cost_normalize($lessons['state']->sum('amount')) }} ₽
                                                </x-ui.badge.light>
                                            @endif

                                            @if($lessons['not_state']->isNotEmpty())
                                                <x-ui.badge.light type="danger" class="cursor-help" title="Затратры на внештатного преподавателя" class="me-2">
                                                    {{ $lessons['not_state']->count() }}
                                                    <x-ui.icon.solid icon="fa-person-walking"></x-ui.icon.solid>
                                                    = {{ tools()->cost_normalize($lessons['not_state']->sum('amount')) }} ₽
                                                </x-ui.badge.light>
                                            @endif

                                            <x-ui.badge.light type="secondary" class="cursor-help" title="Использованные документы" class="me-2">
                                                {{ $course->document_blanks()->where('updated_at', '<', $salary->calculation->created_at)->count() }}
                                                <x-ui.icon.solid icon="fa-file"></x-ui.icon.solid>

                                                = {{ tools()->cost_normalize($cost_documents) }} ₽
                                            </x-ui.badge.light>

                                            <x-ui.badge.light type="info" class="cursor-help" title="Выполненные работы" class="me-2">
                                                {{ $course->education_task_course_works()->where('updated_at', '<', $salary->calculation->created_at)->count() }}
                                                <x-ui.icon.solid icon="fa-helmet-safety"></x-ui.icon.solid>

                                                = {{ tools()->cost_normalize($cost_works) }} ₽
                                            </x-ui.badge.light>

                                            <x-ui.badge.light type="danger" class="cursor-help" title="Оказанные услуги" class="me-2">
                                                {{ $course->education_task_course_services()->where('updated_at', '<', $salary->calculation->created_at)->count() }}
                                                <x-ui.icon.solid icon="fa-thumbs-up"></x-ui.icon.solid>

                                                = {{ tools()->cost_normalize($cost_service) }} ₽
                                            </x-ui.badge.light>
                                </td>
                                <td class="text-right">
                                    <span class="fw-bold"><nobr>{{ tools()->cost_normalize($course_cost) }} ₽</nobr></span>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="100" class="text-right bg-white">
                                <div class="d-inline-flex align-items-center">
                                    <span class="font-20">(</span>
                                    <x-ui.badge.light type="info" class="cursor-help" title="Сумма договора">{{ tools()->cost_normalize($education_task->amount) }} ₽</x-ui.badge.light>
                                    <span class="px-1">&ndash;</span>
                                    <x-ui.badge.light type="warning">{{ tools()->cost_normalize($education_task->amount / 100 * $education_task->minus_rate   ) }} ({{ $education_task->minus_rate }}%)</x-ui.badge.light>
                                    <span class="px-1">&ndash;</span>
                                    <x-ui.badge.light type="danger">{{ tools()->cost_normalize($calculation_total ) }}</x-ui.badge.light>
                                    <span class="px-1">&ndash;</span>
                                    <x-ui.badge.light type="primary">{{ tools()->cost_normalize($supervisor_salary) }}</x-ui.badge.light>
                                    <span class="font-20">)</span>
                                    <x-ui.icon.solid icon="fa-xmark" class="font-16 px-2"></x-ui.icon.solid>
                                    <x-ui.badge.light type="primary">{{ $education_task->tender_rate }}%</x-ui.badge.light>

                                    <x-ui.icon.solid icon="fa-equals" class="font-16 px-2"></x-ui.icon.solid>
                                    <x-ui.badge.light type="primary" class="font-16">
                                        {{ round(($education_task->amount - ($education_task->amount * (1 / 100 * $education_task->minus_rate)) - $calculation_total - $supervisor_salary) * ($education_task->tender_rate / 100), 2) }}
                                    </x-ui.badge.light>
                                    @if($salary->amount > 0)
                                        <x-ui.badge.default type="success" class="ms-2 font-16">
                                            + {{ tools()->cost_normalize($salary->amount) }} ₽
                                        </x-ui.badge.default>
                                    @else
                                        <x-ui.badge.default type="danger" class="ms-2 font-16">
                                            &ndash; {{ tools()->cost_normalize(abs($salary->amount)) }} ₽
                                        </x-ui.badge.default>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach

                </table>
            </div>
        </div>
</div>

@endsection

@section('js')
    @parent
@endsection
