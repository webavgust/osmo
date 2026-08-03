<tr uid="{{ $uid }}">
    <td class="client_pad">
        <x-client.client_search_selector :uid="$uid" :clientId="$clientId"></x-client.client_search_selector>
    </td>
    <td>
        <x-ui.select.multiple class="select2" name="add[]" :items="$courses" :selected="['asd', 'asd']" blank-ignore="1"></x-ui.select.multiple>
    </td>
    <td>
        <input class="form-control inputmask-cost text-right" group="contact" name="delivery[location]" value="">
    </td>
</tr>
