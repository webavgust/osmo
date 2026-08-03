<div class="date-select-inputs mt-3  pb-2 border-1 border p-2 shadow position-relative" style="border-radius: 5px; opacity: .6">
    <div class="input-group mb-3">
        <span class="w-50 fw-bold ps-2">{{ $time->notify_at->format('d.m.Y') }}</span>
        <span class="w-50 fw-bold ps-2">{{ $time->notify_at->format('H:i') }}</span>
    </div>
    <div>
        @foreach($notificators as $notificator)
            <x-reminder.show_time_notificators :notificator="$notificator" checked="{{ in_array($notificator['class'], $time->notificators) ? true : false }}"></x-reminder.show_time_notificators>
        @endforeach
    </div>
</div>
