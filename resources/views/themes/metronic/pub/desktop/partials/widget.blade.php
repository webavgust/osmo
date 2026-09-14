{{--
    Оболочка виджета рабочего стола (patch v30): заголовок, заливка, размер шрифта.

    Тело рисует сам виджет (Widget::body). Переменные: $widget (класс), $w, $h,
    $settings (нормализованные), $body (HTML тела), $url (страница-источник или null),
    $preview (превью в библиотеке — без ссылок).

    Блок может быть любого размера (замок размеров снимается), поэтому оболочка отдаёт
    в CSS ступени размера (desk-w-*, desk-h-*) и сама становится контейнером
    контейнерных запросов — шрифты и отступы внутри считаются от её реальной площади.
    Стили — public/metronic/css/osmo-desktop.css.
--}}
@php
    $fill = $settings['fill'] ?? 'none';
    $font = $settings['font'] ?? 'auto';
    $is_dark = in_array($fill, ['primary', 'info', 'success', 'dark'], true);
@endphp
<div @class([
        'desk-widget',
        'desk-fill-' . $fill,
        'desk-font-' . $font,
        'desk-w-' . $widget::wClass($w),
        'desk-h-' . $widget::hClass($h),
        'desk-widget-dark' => $is_dark,
        'desk-widget-bare' => !$widget::framed(),
     ])
     data-widget="{{ $widget::id() }}" data-size="{{ $w }}x{{ $h }}" data-w="{{ $w }}" data-h="{{ $h }}">
    @if(!empty($settings['show_title']))
        <div class="desk-widget-head">
            <i class="fa-light {{ $widget::icon() }} desk-widget-icon"></i>
            @php($widget_title = ($settings['title'] ?? '') !== '' ? $settings['title'] : $widget::name())
            <span class="desk-widget-title" title="{{ $widget_title }}">{{ $widget_title }}</span>
            @if($url && empty($preview))
                <a href="{{ $url }}" class="desk-widget-source" title="Открыть страницу">
                    <i class="fa-light fa-arrow-up-right-from-square"></i>
                </a>
            @endif
        </div>
    @endif
    <div class="desk-widget-body">{!! $body !!}</div>
</div>
