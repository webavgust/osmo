<div class="input-group mb-1 position-relative service_row mt-4 mt-sm-0" unbind="1" uid="{{ $uid }}">
        <x-ui.select.single name="data[{{ $uid }}][service_id]" :items="$rows" :value="$service->service->id ?? null" id="id" class="form-select flex-grow-1 service_selector" blank-name="Выберите работу"></x-ui.select.single>

        <input name="data[{{ $uid }}][count]" class="form-control flex-grow-0 count" value="{{ $service->count ?? 1 }}" type="number" min="1" style="width: 70px">


        @if(\App\Modules\Pub\EducationTaskCourseService\Models\EducationTaskCourseService::canChangeCost())
            <input name="data[{{ $uid }}][cost]" class="form-control flex-grow-0 text-right inputmask-cost cost" value="{{ $service->cost ?? 0 }}" type="text" min="0" style="width: 110px">
        @else
            <input name="data[{{ $uid }}][cost]" class="form-control flex-grow-0 text-right inputmask-cost cost" value="{{ $service->cost ?? 0}}" type="hidden" min="0" style="width: 110px">
        @endif

        <span class="input-group-text" style="width: 100px">
                = <span class="total">{{ tools()->cost_normalize( ($service->cost ?? 0) * ($service->count ?? 1) ) }}</span> ₽
        </span>

        <div class="del" onclick="javascript:row_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
</div>
