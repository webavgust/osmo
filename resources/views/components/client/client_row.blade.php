<div class="d-flex align-items-center mb-3">
    <div style="width: 45px" class="d-none d-lg-inline">
        <x-client.avatar :client="$client" size="40" class="me-2"></x-client.avatar>
    </div>
    <div class="flex-grow-1">
        <div>
            <div>{{ $client->fullName }}</div>
            @if(!empty($client->role))
                <x-ui.badge.default type="info" class="pb-1">{{ $client->role }}</x-ui.badge.default>
            @endif

        </div>
    </div>
</div>
