
<div class="tr">
    <span class="th">
        @if(!empty($field))
            {{ $field }}
        @else
            {{ $slot }}
        @endif
    </span>
    <span class="td">
        @if(!empty($link))<a href="{{$link}}" target="_blank">@endif
            @if(!empty($value))
                {{ $value }}
            @else
                <i class="fa-light fa-dash"></i>
            @endif
        @if(!empty($link))</a>@endif
    </span>
</div>
