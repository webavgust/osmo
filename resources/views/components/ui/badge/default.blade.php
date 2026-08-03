@php if(empty($text)) $text = null @endphp

<div {{ $attributes->class(['badge', 'bg-'.$type, 'text-'.$text => $text]) }} >
    {{ $slot }}
</div>
