<div class="d-flex align-items-center mb-3">

    <div class="form-check">
        <input name="listeners[]" class="form-check-input" type="checkbox" value="{{ $client->id }}" id="flexCheckChecked" checked="">
    </div>

    <div class="me-2" id="avatar_{{ $client->id }}">
        {{-- Если есть ранее загруженный источник --}}
            <x-client.avatar :client="$client" :course="$course" size="40" upload="true"></x-client.avatar>
    </div>
    <div class="flex-grow-1">
        <div>
            <div>{{ $client->fullName }}</div>
            <div>
                @if(!empty($client->role))
                    <x-ui.badge.default type="info" class="pb-1">{{ $client->role }}</x-ui.badge.default>
                @endif
            </div>

        </div>
    </div>
    <div>
    </div>
</div>
