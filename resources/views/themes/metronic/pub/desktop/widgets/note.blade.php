{{-- Виджет «Заметка» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\NoteWidget --}}
@php
    $background = $widget::BACKGROUNDS[$settings['color']] ?? '';
    $text = trim((string) $settings['text']);

    // кегль по длине текста: короткая заметка в большом блоке крупнее (personal.css)
    $length = mb_strlen($text);
    $scale = $length === 0 ? '' : ($length <= 120 ? 'note-short' : ($length <= 400 ? 'note-mid' : ''));
@endphp
<div class="desk-bleed desk-scroll {{ $background }} {{ $scale }} p-4 text-gray-800 text-break lh-base" data-desk-note>
    @if($text === '')
        <div class="desk-empty">
            <i class="fa-light fa-note-sticky"></i> Пустая заметка — откройте настройки
        </div>
    @else
        {!! nl2br(e($settings['text'])) !!}
    @endif
</div>
