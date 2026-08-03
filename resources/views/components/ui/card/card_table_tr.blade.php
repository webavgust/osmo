<div {{ $attributes->except(['field'])->merge(['class' => 'tr']) }} >
    <span class="th">
        {{ $field }}
        @if(!empty($required))
            <span class="text-danger ms-1">*</span>
        @endif
    </span>
    <span class="td">
        @if(!empty($value))
            @if(!empty($link))<a href="{{$link}}" target="_blank">@endif
                @if(!empty($value))
                    {{ $value }}
                @else
                    <i class="fa-light fa-dash"></i>
                @endif
            @if(!empty($link))</a>@endif
        @else
            @if(!empty($link))<a href="{{$link}}" target="_blank">@endif
            {{ $slot }}
            @if(!empty($link))</a>@endif
        @endif
    </span>
</div>

