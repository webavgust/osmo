<li uid="{{$uid}}" class="p-3 needbind">
    <div class="del" onclick="javascript:li_del(this)">
        <i class="fa-solid fa-delete-left"></i>
    </div>

    <x-ui.select.single name="contact_preset" :items="$courses" recalc="true" name="program[{{$uid}}][course]" class="select2 course mb-2" id="id" value="{{ $course->course_id ?? 0 }}"></x-ui.select.single>

{{--    <div>--}}
{{--        <div class="form-check mt-1">--}}
{{--            <input class="form-check-input" type="checkbox" value="1" id="course{{$uid}}" name="program[{{$uid}}][share]" @if(!empty($course) && $course->need_share) checked @endif >--}}
{{--            <label class="form-check-label" for="course{{$uid}}">--}}
{{--                Предоставить доступ--}}
{{--            </label>--}}
{{--        </div>--}}
{{--    </div>--}}

    <div class="card-table mt-2">
        <x-ui.card.card_table_tr field="Предоставить доступ">
            <x-ui.select.single name="program[{{$uid}}][share]" :items="[ 1 => 'Да', -1 => 'Нет']" blank-name="Не выбрано" value="{{ $course->need_share ?? null }}"></x-ui.select.single>
        </x-ui.card.card_table_tr>

        <x-ui.card.card_table_tr field="Кол-во обучающихся" required="1">
            <input type="text" class="form-control text-center count" name="program[{{$uid}}][count]" value="{{ $course->count ?? 1 }}" min="1" max="999" style="width: 60px" maxlength="3" recalc="true">
        </x-ui.card.card_table_tr>

        <x-ui.card.card_table_tr field="Стоимость обучения (1 чел.)" required="1">
            <input class="form-control inputmask-cost cost" name="program[{{$uid}}][cost]" value="{{ $course->cost ?? 0 }}" style="width: 120px" recalc="true">
        </x-ui.card.card_table_tr>

        <x-ui.card.card_table_tr field="Форма обучения" required="1">
            <x-ui.select.single name="program[{{$uid}}][course_type]" :items="$program_types" class="type course_type" value="{{ $course->course_type ?? 0 }}"></x-ui.select.single>
        </x-ui.card.card_table_tr>

        <x-ui.card.card_table_tr field="Учебный центр">
            <x-ui.select.single name="program[{{$uid}}][edu_center]" :items="$edu_centers" id="id" value="{{ $course->education_center->id ?? 0 }}"></x-ui.select.single>
        </x-ui.card.card_table_tr>

        <x-ui.card.card_table_tr field="Желаемая дата начала">
            <input class="form-control datepicker select text-center" name="program[{{$uid}}][date]" value="{{ !empty($course->date_preferred) ? $course->date_preferred->format('d.m.Y') : date('d.m.Y') }}" style="width: 100px">
        </x-ui.card.card_table_tr>
    </div>
</li>
