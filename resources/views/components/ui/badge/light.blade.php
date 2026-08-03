@php if(empty($text)) $text = null @endphp
<div {{ $attributes->class(['badge', 'bg-light-'.$type, 'text-'.$type, 'd-none' => strlen($slot) == 0]) }} >
    {{ $slot }}
</div>
