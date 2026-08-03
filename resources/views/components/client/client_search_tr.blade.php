<tr>
    <td class="py-2 ps-0">
        @if(!empty($attributes['row_id']))
            <x-ui.button.light btn_type="secondary" onclick="javascript:set_client('{{ $attributes['row_id'] }}', {{ $client->id }});">Выбрать</x-ui.button.light>
        @endif
    </td>
    <td class="align-middle py-2">
        <div class="d-flex align-items-center">
            <x-client.avatar :client="$client" size="32" class="me-2"></x-client.avatar> {{ $client->full_name }}
        </div>
    </td>
    <td class="align-middle py-2">{{ $client->phone }}</td>
    <td class="align-middle py-2">{{ $client->email }}</td>
</tr>
