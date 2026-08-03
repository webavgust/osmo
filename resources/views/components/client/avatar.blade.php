@switch($type)
    @case('upload')
        <div class="p-1" style="border: 1px dashed #DDD; border-radius: 50%">
            @if(empty($photo))
                <div {{ $attributes->class(['round', 'text-white', 'd-flex', 'align-items-center', 'justify-content-center', 'rounded-circle', 'bg-light-secondary', 'text-white'])->merge(['style' => $style]) }}>
                    <x-ui.icon.light icon="fa-square-dashed"></x-ui.icon.light>
                </div>
            @else
                <x-ui.a.box href="{{ route('client.box_avatar', [$course, $client]) }}" class="m-0 p-0">
                    @if(!empty($attributes))
                        <div {{ $attributes->merge(['style' => $style]) ?? null }} class="round text-white d-flex align-items-center justify-content-center rounded-circle bg-info text-white">
                            <img src="{{ $photo }}" class="profile-pic rounded-circle mw-100">
                        </div>
                    @else
                        <div style="{{ $style }}" class="round text-white d-flex align-items-center justify-content-center rounded-circle bg-info text-white">
                            <img src="{{ $photo }}" class="profile-pic rounded-circle mw-100">
                        </div>
                    @endif
                </x-ui.a.box>
            @endif
        </div>
    @break
    @case('flat')
        <div {{ $attributes->class(['round', 'text-white', 'd-flex', 'align-items-center', 'justify-content-center', 'rounded-circle', 'bg-info', 'text-white'])->merge(['style' => $style]) }}>
            {{ $client->abbr }}
        </div>
    @break
@endswitch
