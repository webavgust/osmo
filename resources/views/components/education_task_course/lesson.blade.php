@if($is_prev)
    <x-ui.a.sidebar href="{{ route('study_lesson.sidebar_view', $lesson) }}" class="lesson short col-6 col-sm-3 col-md-2 col-lg-2 col-xl-1 p-0 muted d-flex justify-content-center">
        <div class="schedule_day">
            <div class="day">{{ $lesson->start_at->day }}</div>
            <div class="month">{{ \App\Services\Tools\Tools::MONTH_NAME_D[$lesson->start_at->month] }}</div>
            <div class="time">
                {{ $lesson->start_at->format('H:i') }} &ndash; {{ $lesson->end_at->format('H:i') }}
            </div>
            <div class="duration">
                {{ $lesson->duration }} ак.ч.
            </div>
        </div>
    </x-ui.a.sidebar>
@else
    <x-ui.a.sidebar href="{{ route('study_lesson.sidebar_view', $lesson) }}" class="lesson short col-6 col-sm-3 col-md-2 col-lg-2 col-xl-1 p-0 d-flex justify-content-center">
        <div class="schedule_day">
            <div class="day">{{ $lesson->start_at->day }}</div>
            <div class="month">{{ \App\Services\Tools\Tools::MONTH_NAME_D[$lesson->start_at->month] }}</div>
            <div class="time">
                {{ $lesson->start_at->format('H:i') }} &ndash; {{ $lesson->end_at->format('H:i') }}
            </div>
            <div class="duration">
                {{ $lesson->duration }} ак.ч.
            </div>
        </div>
    </x-ui.a.sidebar>
@endif
