<select select2="Y" {{ $attributes->except(['items'])->class('form-select') }}>
    @if(!empty($list))
        @foreach($list as $row)
            <option @if(!empty($row['selected']) && $row['selected']) selected @endif value="{{ $row['item_id'] }}">{{ $row['name'] }}</option>
        @endforeach
    @endif
</select>
