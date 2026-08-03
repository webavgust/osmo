@if(empty($value) || !empty($value['blank']))
    <x-ui.badge.light type="secondary"><Пусто></x-ui.badge.light>
@else
    @if(!empty($value['link']))
        <a href="{{ $value['link'] }}">
    @endif
    @if(!empty($value['icon']))
        @switch($value['icon']['type'])
            @case('light')
                <x-ui.icon.light icon="{{ $value['icon']['icon'] }}" {{ $attributes->class([$value['icon']['color'] ?? '' => 1])->only(['class']) }}></x-ui.icon.light>
                @break
            @case('duotone')
                <x-ui.icon.duotone icon="{{ $value['icon']['icon'] }}" {{ $attributes->class([$value['icon']['color'] ?? '' => 1])->only(['class']) }}></x-ui.icon.duotone>
                @break
            @case('regular')
            @default
                <x-ui.icon.regular icon="{{ $value['icon']['icon'] }}" {{ $attributes->class([$value['icon']['color'] ?? '' => 1])->only(['class']) }}></x-ui.icon.regular>
        @endswitch
    @endif

    @if(!empty($value['text']))
        @if(is_array($value['text']))
            <div class="card bg-light-secondary w-100">
                <div class="card-body">
                    <div class="card-table mt-2 fs-2">
                        @foreach($value['text'] as $field => $value)
                            <x-ui.card.card_table_tr field="{{ $field }}">
                                @if(is_array($value))
                                    < массив >
                                @else
                                    {{ $value }}
                                @endif
                            </x-ui.card.card_table_tr>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            @if(!empty($value['badge']))
                <x-ui.badge.default type="{{ $value['badge'] }}" text="{{ $value['color'] }}">
                    {{ $value['text'] }}
                </x-ui.badge.default>
            @else
                <span class="fs-3">
                    {{ $value['text'] }}
                </span>
            @endif
        @endif
    @endif
    @if(!empty($value['link']))
        </a>
    @endif
@endif
