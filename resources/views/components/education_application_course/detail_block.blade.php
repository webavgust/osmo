<div class="card mb-1 course" id="{{ $course->id }}" >
    <div class="card-body p-2 px-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fs-4 fw-bold text-dark">{{ $course->course->name_duration ?? '?'}}</span>

            <span class="text-dark d-flex align-items-center text-nowrap ms-1">
                <x-ui.icon.solid icon="fa-person"></x-ui.icon.solid>
                    <span class="fs-4 ms-1 fw-bold">{{ $course->clients->count() }} / {{ $course->count }}</span>
            </span>
        </div>

        <div class="mt-2 d-flex justify-content-between align-items-center">
            <div>
                <x-ui.badge.default type="danger">
                    {{ $course_type }}
                </x-ui.badge.default>
            </div>
            @if($course->need_share == 1   )
                <span class="text-info d-flex align-items-center">
                    <x-ui.icon.regular icon="fa-universal-access"></x-ui.icon.regular>
                    <span class="fs-3 ms-1">предоставить доступ</span>
                </span>
            @endif
        </div>

{{--        <div class="mt-2 text-dark">Тут будет информация о закреплённой группе и ссылка на детальную страницу курса</div>--}}


    </div>

</div>
