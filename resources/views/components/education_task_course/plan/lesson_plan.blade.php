<div class="lesson">
    <div class="schedule_day">
        <div class="day">{{ $lesson->start_at->day }}</div>
        <div class="month">{{ \App\Services\Tools\Tools::MONTH_NAME_D[$lesson->start_at->month] }}</div>
    </div>
    <div class="flex-grow-1 d-flex flex-column p-2 justify-content-between">
        <div class="d-flex justify-content-between align-items-center">
            <div class="time">
                {{ $lesson->start_at->format('H:i') }} &ndash; {{ $lesson->end_at->format('H:i') }}
                @if($lesson->force_time)
                    <i class="fa-solid fa-diamond-exclamation text-warning ms-2"></i>
                @endif
            </div>
            <div>
                @if(!empty($lesson->education_class->floor))
                    <x-ui.badge.light  type="info" class="font-12" >
                        {{ $lesson->education_class->floor }} этаж
                    </x-ui.badge.light>
                @endif
                <x-ui.badge.default  type="info" class="font-12" >
                    {{ $lesson->education_class->number }}
                </x-ui.badge.default>
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <x-ui.badge.light_rounded type="secondary">
                {{ $lesson->teacher->user->fullName }}
            </x-ui.badge.light_rounded>
            <div>
                @if($lesson->teacher->isState())
                    <x-ui.badge.light  type="secondary" class="font-12" >
                        Штатный преподаватель
                    </x-ui.badge.light>
                @else
                    <x-ui.badge.light  type="primary" class="font-12" >
                        {{ tools()->num_rus($lesson->duration, ['ак.часа', 'ак.час', 'ак.часов'], true) }}
                    </x-ui.badge.light>
                    <x-ui.badge.light  type="primary" class="font-12" >
                        = {{ tools()->cost_normalize($lesson->cost_total) }} ₽
                    </x-ui.badge.light>
                @endif

            </div>
        </div>
    </div>
</div>
