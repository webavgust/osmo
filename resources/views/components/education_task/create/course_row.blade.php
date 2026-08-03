<li uid="{{$uid}}" class="p-3">
    <div class="d-flex justify-content-between">
        <h4>{{ $courses[$course->course_id]->name }}</h4>
    </div>


    <div class="card-table">

        <x-ui.card.card_table_tr field="Кол-во обучающихся">
            <span class="text-success cursor-help" title="Взято из приложения">{{ $course->count }}</span>
        </x-ui.card.card_table_tr>

        <x-ui.card.card_table_tr field="Стоимость обучения (1 чел.)">
            <span class="text-success cursor-help" title="Взято из приложения">{{ tools()->cost_normalize($course->cost) }} ₽</span>
        </x-ui.card.card_table_tr>

        <x-ui.card.card_table_tr field="Форма обучения">
            <span class="text-success cursor-help" title="Взято из приложения">{{ $program_types[$course->course_type]['name'] }}</span>
        </x-ui.card.card_table_tr>

        @if($course->education_application_course->education_center_id)
            <x-ui.card.card_table_tr field="Учебный центр">
                <span class="text-success cursor-help" title="Взято из приложения">{{ $course->education_application_course->education_center->name  }}</span>
            </x-ui.card.card_table_tr>
        @else
            <x-ui.card.card_table_tr field="Учебный центр" required="1">
                <x-ui.select.single required="1" name="program[{{$uid}}][edu_center]" :items="$edu_centers" id="id" value="{{ $course->education_center->id ?? 0 }}"></x-ui.select.single>
            </x-ui.card.card_table_tr>
        @endif

        @if($course->education_application_course->date_preferred->timestamp > 0)
            <x-ui.card.card_table_tr field="Желаемая дата начала">
                <span class="text-success cursor-help" title="Взято из приложения">{{ $course->education_application_course->date_preferred->format('d.m.Y') }}</span>
            </x-ui.card.card_table_tr>
        @else
            <x-ui.card.card_table_tr field="Желаемая дата начала" required="1">
                <input class="form-control datepicker select text-center" name="program[{{$uid}}][date]" required="1" value="{{ !empty($course->date_preferred->timestamp > 0) ? $course->date_preferred->format('d.m.Y') : '' }}" style="width: 100px">
            </x-ui.card.card_table_tr>
        @endif

        @if($course->education_application_course->need_share)
            <x-ui.card.card_table_tr field="Нужно предоставить доступ">
                @if($course->education_application_course->need_share == 1)
                    <x-ui.icon.regular icon="fa-check text-success"></x-ui.icon.regular>
                @else($course->education_application_course->need_share == -1)
                    <x-ui.icon.regular icon="fa-xmark text-danger"></x-ui.icon.regular>
                @endif
            </x-ui.card.card_table_tr>
        @else
            <x-ui.card.card_table_tr field="Нужно предоставить доступ" required="1">
                    <x-ui.select.single required="1" name="program[{{$uid}}][need_share]" :items="[ 1 => 'Да', -1 => 'Нет']" blank-name="" value="{{ $course->need_share ?? null }}"></x-ui.select.single>
            </x-ui.card.card_table_tr>
        @endif
    </div>
</li>
