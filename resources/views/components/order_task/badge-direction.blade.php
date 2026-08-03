@foreach($directions as $direction)
    <x-ui.badge.default :type="$data[$direction]['color']['badge']" {{ $attributes->class([]) }}>
        {{ $data[$direction]['letter'] }}
    </x-ui.badge.default>
@endforeach
