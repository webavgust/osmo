@php if(empty($text)) $text = null @endphp

<div {{ $attributes->class(['badge', 'border border-'.$type, 'bg-'.$type, 'text-'.$text => $text]) }} >
    {{ $slot }}
</div>
