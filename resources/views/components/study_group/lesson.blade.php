@if($lesson->study_group->canModify())
    <a class="lesson cursor-pointer @if($lesson->isPast()) muted @endif  @if(!empty($isLast)) last @endif" onclick="javascript:box({href:'{{ route('study_lesson.box_edit', $lesson) }}'})">
            <x-study_group.lesson_body :lesson="$lesson"></x-study_group.lesson_body>
    </a>
@else
    <div class="lesson @if($lesson->isPast()) muted @endif  @if(!empty($isLast)) last @endif" >
        <x-study_group.lesson_body :lesson="$lesson"></x-study_group.lesson_body>
    </div>
@endif

