<div class="lesson plan noHover border-0 @if($lesson->deleted_at || $lesson->deleted) deleted muted @endif">
    <div class="schedule_day p-1 p-md-1 border-top justify-content-center d-flex" style="width: 100px;" title="{{ $lesson->id }}">
        <div class="month text-center">{{ $lesson->start_at->format('d.m') }}</div>
        <div class="font-12">
            <span class="d-none d-md-inline">
                {{ $lesson->start_at->format('H:i') }} - {{ $lesson->end_at->format('H:i') }}
            </span>
            <span class="d-md-none font-10">
                {{ $lesson->start_at->format('H:i') }} - {{ $lesson->end_at->format('H:i') }}
            </span>
        </div>
    </div>
    <div class="flex-grow-1 d-flex p-1 p-md-1 justify-content-between align-items-center">
        <div class="d-flex justify-content-between flex-column">
            @if(!empty($lesson->teacher))
                <span class="ps-1">
                        {{ $lesson->teacher->user->fullName }}
                </span>
                <div>
                    @if($lesson->teacher->isState())
                        <x-ui.badge.light  type="secondary" class="font-12" >
                            Штатный<span class="d-none d-lg-inline"> преподаватель</span>
                        </x-ui.badge.light>

                        @if(!method_exists($lesson, 'calculation_lesson') || empty($lesson->calculation_lesson()))
                            <div>
                                <x-ui.badge.light  type="warning" class="font-12" >
                                    {{ $lesson->duration }} а.ч.
                                </x-ui.badge.light>
                            </div>
                        @else
                            <div>
                                <x-ui.badge.light  type="success" class="font-12" >
                                    {{ $lesson->duration }} а.ч.
                                </x-ui.badge.light>
                                <x-ui.badge.light  type="success" class="font-12" >
                                        = {{ tools()->cost_normalize($lesson->calculation_lesson()->amount) }} ₽
                                </x-ui.badge.light>
                            </div>
                        @endif
                    @else
                        <x-ui.badge.light  type="primary" class="font-12" >
                            {{ $lesson->duration }} а.ч.
                        </x-ui.badge.light>
                        <x-ui.badge.light  type="primary" class="font-12" >
                            = {{ tools()->cost_normalize($lesson->cost_total) }} ₽
                        </x-ui.badge.light>
                    @endif
                </div>
            @else
                <span class="ps-1">
                    Без преподавателя
                </span>
            @endif
        </div>
        <div>{{ $slot }}</div>
    </div>

</div>

