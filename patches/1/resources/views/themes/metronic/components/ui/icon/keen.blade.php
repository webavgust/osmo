{{--
    KeenIcons (иконки Metronic). Для новых элементов интерфейса.

    <x-ui.icon.keen icon="ki-calendar" paths="3" class="fs-2" />
    <x-ui.icon.keen icon="ki-home" style="solid" />

    style: duotone (по умолчанию) | outline | solid
    paths: количество слоёв для duotone (см. таблицу иконок Metronic)
--}}
@php
    $style = $style ?? 'duotone';
    $paths = (int) ($paths ?? 0);
@endphp
<i {{ $attributes->class(['ki-'.$style, $icon]) }}>@for($i = 1; $i <= $paths; $i++)<span class="path{{ $i }}"></span>@endfor</i>
