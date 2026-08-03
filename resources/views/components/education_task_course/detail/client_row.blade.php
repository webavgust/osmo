<div class="d-flex align-items-center mb-3">
    <div class="me-2" id="avatar_{{ $client->id }}">
        {{-- Если есть ранее загруженный источник --}}
            <x-client.avatar :client="$client" :course="$course" size="40" upload="true"></x-client.avatar>
    </div>
    <div class="flex-grow-1">
        <div>
            <div>
                @can('client_view')
                    <a href="{{ route('client.detail', $client) }}">{{ $client->fullName }}</a>
                @else
                    {{ $client->fullName }}
                @endcan
            </div>
            <div>
                @if(!empty($client->role))
                    <x-ui.badge.default type="info" class="pb-1">{{ $client->role }}</x-ui.badge.default>
                @endif
                @if(!empty($client->org_name))
                    <x-ui.badge.light type="secondary" class="pb-1">{{ $client->org_name }}</x-ui.badge.light>
                @endif
                @if(!empty($client->rank))
                    <x-ui.badge.light type="warning" class="pb-1">{{ $client->rank }} разряд</x-ui.badge.light>
                @endif
            </div>

        </div>
    </div>
    <div>

        @if(!empty($number))
            <x-ui.badge.default type="danger" class="pb-1">{{ $number }}</x-ui.badge.default>
        @endif

        @if($course->canModify())
            @if(!empty($source))
                <x-ui.a.box href="{{ route('client.box_make_avatar', [$course, $client]) }}" type="secondary">
                    <x-ui.icon.solid icon="fa-crop-simple"></x-ui.icon.solid>
                </x-ui.a.box>
            @else
                <a href="{{ route('education-task.docs', [$course->education_task, 'client' => $client->id]) }}">
                    <x-ui.icon.solid icon="fa-paperclip"></x-ui.icon.solid>
                </a>
            @endif
        @endif
    </div>
</div>
