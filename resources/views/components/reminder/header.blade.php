<div class="btn-group ms-2" role="group">
    @if(!empty($reminder['count']) && $reminder['count'] > 0)
        <x-ui.a.light href="{{ route('reminder.filter', ['module' =>$reminder['module'], 'id' => $reminder['id']]) }}" target="_blank" btn_type="secondary" class="text-secondary">
            {{ tools()->num_rus($reminder['count'], ['напоминания', 'напоминание', 'напоминаний'], true)  }}
        </x-ui.a.light>
    @endif
    <x-ui.button.light class="btn-light-primary justify-content-center"
                       onclick="javascript:sidebar({href: '{{ route('reminder.sidebar_add') }}', data: {module:'{{$reminder['module']}}', id:{{$reminder['id']}}},  method: 'POST'})">
        <x-ui.icon.regular icon="fa-bell-plus" class="fill-white text-primary"></x-ui.icon.regular>
    </x-ui.button.light>
</div>
