@php if(empty($text)) $text = null @endphp
<div {{ $attributes->class(['badge', 'badge-outline', 'badge-'.$type, 'text-'.$text => $text]) }}>
    {{ $slot }}
</div>
