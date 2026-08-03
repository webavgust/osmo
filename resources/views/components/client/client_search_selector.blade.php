@if(empty($client))
    <x-ui.a.box href="{{ route('client.box_select', ['row_id' => $uid, 'app' => $app]) }}" btn_type="secondary" class="client_choice">Выбрать слушателя</x-ui.a.box>
@else
    <div class="client">
        <input type="hidden" name="data[{{ $uid }}][client_id]" value="{{ $client->id }}" class="client_id">
        <div class="d-flex align-items-center">
            <div style="width: 45px" class="avatar">
                <a href="{{ route('client.detail', $client) }}" target="_blank">
                    <x-client.avatar :client="$client" size="40" class="me-2"></x-client.avatar>
                </a>
            </div>
            <div class="flex-grow-1">
                    <a href="{{ route('client.detail', $client) }}" target="_blank">{{ $client->fullName }}</a>
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
            <x-ui.button.light class="ms-1 p-2 invisible delete" onclick="javascript:set_client('{{ $uid }}');">
                <x-ui.icon.solid icon="fa-xmark" class="text-danger"></x-ui.icon.solid>
            </x-ui.button.light>
        </div>
    </div>
@endif
