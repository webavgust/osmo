{{-- Виджет «Баннер» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\BannerWidget --}}
@php
    $style = $settings['style'];
    $is_light = in_array($style, ['light', 'warning'], true);
    $background = $style === 'gradient' ? 'desk-banner-gradient' : 'bg-' . $style;
@endphp
<div @class([
        'desk-banner desk-bleed',
        $background,
        'text-gray-900' => $is_light,
        'text-white' => !$is_light,
        'justify-content-center text-center' => $settings['align'] === 'center',
        'justify-content-between' => $settings['align'] !== 'center',
    ])>
    <div class="desk-banner-text">
        <div class="desk-banner-headline" title="{{ $settings['headline'] }}">{{ $settings['headline'] }}</div>
        @if($settings['text'] !== '')
            {{-- текст появляется, когда баннер выше двух ячеек; раскладка по размеру — common-1.css --}}
            <div class="desk-banner-sub desk-only-h-md" title="{{ $settings['text'] }}">{{ $settings['text'] }}</div>
        @endif
    </div>

    @if($settings['button_label'] !== '' && $data['url'])
        <a href="{{ $preview ? 'javascript:void(0)' : $data['url'] }}" class="btn btn-sm desk-banner-btn" title="{{ $settings['button_label'] }}">
            {{ $settings['button_label'] }}
        </a>
    @endif
</div>
