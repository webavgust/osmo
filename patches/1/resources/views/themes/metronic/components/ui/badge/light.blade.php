@php if(empty($text)) $text = null @endphp
<div {{ $attributes->class(['badge', 'badge-light-'.$type, 'd-none' => strlen($slot) == 0]) }}>
    {{ $slot }}
</div>
