@php if(empty($text)) $text = null @endphp

<div {{ $attributes->class(['mb-1', 'badge', 'bg-'.$type, 'rounded-pill', 'text-'.$type, 'text-'.$text => $text]) }} >
    {{ $slot }}
</div>
