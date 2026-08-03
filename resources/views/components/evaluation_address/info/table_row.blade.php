<x-ui.card.card_table_tr field="{{ $field }}">
    @if(!empty($value))
        {{ $value }}
    @else
        <x-ui.icon.regular icon="fa-xmark" class="text-danger"></x-ui.icon.regular>
    @endif
</x-ui.card.card_table_tr>
