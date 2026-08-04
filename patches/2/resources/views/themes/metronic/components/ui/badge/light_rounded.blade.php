@php if(empty($text)) $text = null @endphp
<div {{ $attributes->class(['mb-1', 'badge', 'badge-light-'.$type, 'rounded-pill', 'text-'.$text => $text]) }}>
    {{ $slot }}
</div>
