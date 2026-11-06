{{--
    Попап «Закрыть проект» (patch v48).

    Закрытие = архив + итог (успешно / неуспешно, выбор обязателен) и
    комментарий к итогу. По желанию тут же закрываются спецификации проекта:
    чекбокс предотмечен, если спецификация оплачена полностью (suggest_close),
    закрытые и отменённые выводятся без чекбокса. Если проект уже в архиве,
    меняется только итог.
--}}
@extends('components.box.box-static-large')

@section('body')
    @php
        $finished = [
            \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::CLOSED->value,
            \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::CANCELED->value,
        ];
        $result_icons = ['success' => 'fa-flag-checkered', 'fail' => 'fa-circle-xmark'];
        $result_colors = ['success' => 'success', 'fail' => 'danger'];
    @endphp

    <form id="deal_project_close_form" onsubmit="return false;">
        {{-- Шапка: партнёр, компания, сделки проекта --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-5 fs-7">
            @if($project->partner)
                <span class="text-muted">Партнёр:</span>
                <span class="badge badge-light-warning fs-7">{{ $project->partner->name }}</span>
            @endif

            @if($project->company)
                <span class="text-muted ms-3">Компания:</span>
                <span class="badge badge-light-primary fs-7">{{ $project->company->name }}</span>
            @endif

            <span class="text-muted ms-3">Сделок:</span>
            <span class="badge badge-light fs-7">{{ $deals->count() }}</span>

            @if($project->is_archived)
                <span class="badge badge-light-dark fs-7 ms-3">
                    <i class="fa-light fa-box-archive me-1"></i>
                    в архиве с {{ $project->archived_at?->format('d.m.Y') }}
                </span>
            @endif
        </div>

        @if($project->is_archived)
            <div class="text-muted fs-8 mb-4">
                <i class="fa-light fa-circle-info me-1"></i>
                Проект уже в архиве — сохранится только итог и отмеченные спецификации.
            </div>
        @endif

        {{-- Итог: выбор обязателен --}}
        <div class="mb-4">
            <label class="form-label fw-semibold">Итог <span class="text-danger">*</span></label>
            <div class="d-flex flex-wrap gap-3">
                @foreach($results as $value => $label)
                    <input type="radio" class="btn-check" name="result" value="{{ $value }}"
                           id="deal_project_close_result_{{ $value }}" @checked($project->result === $value)/>
                    <label class="btn btn-outline btn-outline-dashed btn-active-light-{{ $result_colors[$value] ?? 'primary' }} px-6 py-3"
                           for="deal_project_close_result_{{ $value }}">
                        <i class="fa-light {{ $result_icons[$value] ?? 'fa-circle' }} me-2"></i>{{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="mb-5">
            <label class="form-label fw-semibold">Комментарий к итогу <span class="text-muted fw-normal fs-8">(необязательно)</span></label>
            <textarea name="result_comment" class="form-control" rows="2">{{ $project->result_comment }}</textarea>
        </div>

        {{-- Закрыть спецификации проекта --}}
        <div class="mb-2">
            <label class="form-label fw-semibold">Закрыть спецификации?</label>

            @if($hints->isEmpty())
                <div class="text-muted fs-7">У проекта нет спецификаций.</div>
            @else
                <table class="table table-row-bordered table-row-dashed align-middle fs-7 m-0">
                    <thead>
                        <tr class="text-muted fw-semibold fs-8">
                            <th style="width: 30px"></th>
                            <th>Спецификация</th>
                            <th class="text-end">Сумма</th>
                            <th class="text-end">Оплачено</th>
                            <th class="text-end">Остаток</th>
                            <th class="text-end">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hints as $row)
                            @php $is_finished = in_array($row['status'], $finished, true); @endphp
                            <tr>
                                <td>
                                    @unless($is_finished)
                                        <label class="form-check form-check-custom form-check-sm">
                                            <input class="form-check-input close_spec" type="checkbox"
                                                   name="close_specs[]" value="{{ $row['id'] }}"
                                                   @checked($row['suggest_close'])/>
                                        </label>
                                    @endunless
                                </td>
                                <td>
                                    @php
                                        // названия спецификаций ПО и Услуг часто совпадают — различаем типом и номером договора
                                        $type = \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $row['spec']?->contract?->type);
                                    @endphp
                                    @if($type)
                                        <span class="badge badge-light-{{ $type->data()['color'] }} fs-9 me-1">{{ $type->data()['label'] }}</span>
                                    @endif
                                    <span class="fw-semibold">{{ $row['name'] ?: 'без названия' }}</span>
                                    @if($row['from_proposal'])
                                        <span class="badge badge-light-success fs-9 ms-1">из КП</span>
                                    @endif
                                    @if($row['spec']?->contract)
                                        <div class="text-muted fs-8">договор {{ $row['spec']->contract->number ?: 'б/н' }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">{{ tools()->cost_normalize(round($row['amount'])) }} {{ $row['currency'] }}</td>
                                <td class="text-end text-nowrap">{{ tools()->cost_normalize(round($row['paid'])) }} {{ $row['currency'] }}</td>
                                <td class="text-end text-nowrap @if($row['rest'] > 0) text-danger @endif">
                                    {{ tools()->cost_normalize(round($row['rest'])) }} {{ $row['currency'] }}
                                </td>
                                <td class="text-end">
                                    @if($row['status_label'])
                                        <span class="badge badge-light-{{ $row['status_color'] ?: 'dark' }} fs-8">{{ $row['status_label'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="text-muted fs-8 mt-2">
                    Оплата и работы могут расходиться — отметьте только то, что действительно завершено.
                </div>
            @endif
        </div>
    </form>

    <script>
        (function () {
            var results = @json($results);

            /** Закрыть проект: итог обязателен, перед отправкой — подтверждение с перечнем */
            window.deal_project_close_save = function () {
                var $form = $('#deal_project_close_form');
                var result = $form.find('input[name="result"]:checked').val();

                if (!result) {
                    toastr.error('Укажите итог проекта: успешно или нет', 'Это провал!', {progressBar: true, timeOut: 4000});
                    return;
                }

                var count = $form.find('.close_spec:checked').length;
                var text = 'Закрыть проект?\n\n'
                    + 'Итог: ' + results[result] + '\n'
                    + (count ? 'Закрыть спецификаций: ' + count : 'Спецификации не закрываются');

                if (!confirm(text)) return;

                var data = $form.find(':input').serialize() + '&_token=' + csrf_token();

                body_block();
                $form.find(':input').prop('disabled', true);

                $.ajax({
                    url: @json($action),
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        body_unblock();
                        $form.find(':input').prop('disabled', false);

                        if (response.result !== 'success') {
                            toastr.error(response.message || 'Не получилось закрыть проект', 'Это провал!', {progressBar: true, timeOut: 6000});
                            return;
                        }

                        toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});
                        box_close();
                        location.reload();
                    },
                    error: function (xhr) {
                        body_unblock();
                        $form.find(':input').prop('disabled', false);
                        toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                    }
                });
            };
        })();
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
            <span>Отмена</span>
        </x-ui.button.default>

        <x-ui.button.default btn_type="primary" onclick="javascript:deal_project_close_save();">
            <i class="fa-light fa-flag-checkered me-2"></i>
            <span>Закрыть проект</span>
        </x-ui.button.default>
    </div>
@endsection
