<div
    class="calendar-events mb-3 cursor-pointer  "
    data-color="bg-{{ $event->color }}"
    data-title="{{ $event->title }}"
    data-duration="{{ $event->duration_str }}"
    data-id="{{ $event->id }}"
    data-class="{{ $event->color }}"
    data-reminders_count="{{ $event->reminders()->count() }}"
    id="{{ $event->id }}"
>
    <i class="fa fa-circle text-{{ $event->color }} me-2"></i
    >{{ $event->title }}
    @if(!empty($event->duration))
        <x-ui.badge.light type='secondary' class="fs-1">{{ $event->duration }} мин</x-ui.badge.light>
    @endif

    <x-ui.a.sidebar href="{{ route('calendar.sidebar_edit', [$event, '_token' => _token()]) }}">
        <x-ui.icon.regular icon="fa-edit" class="ms-1"></x-ui.icon.regular>
    </x-ui.a.sidebar>

</div>
