{{-- Виджет «Заголовок ряда» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\HeadingWidget --}}
@php
    $icon = $settings['icon'] !== 'none' ? $settings['icon'] : null;
    $color = 'text-' . $settings['color'];
@endphp
<div class="desk-bleed desk-heading d-flex align-items-center gap-3 h-100 px-1">
    @if($icon)
        <i class="fa-light {{ $icon }} {{ $color }} flex-shrink-0"></i>
    @endif
    <span class="fw-bold text-truncate {{ $color }}" style="min-width: 0;">{{ $settings['text'] }}</span>
    @if($settings['line'])
        <span class="flex-grow-1 border-bottom border-gray-300 desk-hide-narrow" style="min-width: 1rem;"></span>
    @endif
</div>
