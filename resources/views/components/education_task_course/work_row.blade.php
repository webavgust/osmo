<div class="input-group mb-1 position-relative work_row mt-4 mt-sm-0" unbind="1" uid="{{ $uid }}">
        <x-ui.select.single name="data[{{ $uid }}][work_id]" :items="$rows" :value="$work->work->id ?? null" id="id" class="form-select flex-grow-1 work_selector" blank-name="Выберите работу"></x-ui.select.single>

        <div id="contractor_{{ $uid }}">
            <x-education_task_course.work_contractors :uid="$uid" work-id="{{ $work->work->id ?? null }}" selected="{{ $work->contractor->id ?? null }}"></x-education_task_course.work_contractors>
        </div>

        <input name="data[{{ $uid }}][count]" class="form-control flex-grow-0 count" value="{{ $work->count ?? 1 }}" type="number" min="1" style="width: 70px">


        @if(\App\Modules\Pub\EducationTaskCourseWork\Models\EducationTaskCourseWork::canChangeCost())
            <input name="data[{{ $uid }}][cost]" class="form-control flex-grow-0 text-right inputmask-cost cost" value="{{ $work->cost ?? 0 }}" type="text" min="0" style="width: 110px">
        @else
            <input name="data[{{ $uid }}][cost]" class="form-control flex-grow-0 text-right inputmask-cost cost" value="{{ $work->cost ?? 0}}" type="hidden" min="0" style="width: 110px">
        @endif

        <span class="input-group-text" style="width: 100px">
                = <span class="total">{{ tools()->cost_normalize( ($work->cost ?? 0) * ($work->count ?? 1) ) }}</span> ₽
        </span>

        <div class="del" onclick="javascript:row_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
</div>

<script>
    $("[name='data[{{ $uid }}][work_id]']").on("change", function() {
        elem = $(this).parents(".work_row");
        elem.find('.count').val(1);
        elem.find('.cost').val('');
        $(elem).block(block_default);
        $.ajax({
            url: "{{ route('api.education-task-course.component_contractors') }}?_token={{ _token() }}",
            method: 'GET',
            data: {
                uid: '{{ $uid }}',
                work_id: $(this).val()
            },
            dataType: 'html',
            success: function(html) {
                $("#contractor_{{ $uid }}").html(html);
                $(elem).unblock();
            },
            error: function() {
                toastr.error("Не получилось обновить подрядчиков", "Это провал!", {
                    progressBar: true,
                    "timeOut": 3000,
                });
            }
        });
    });
</script>
