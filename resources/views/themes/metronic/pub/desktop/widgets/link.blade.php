{{-- Виджет «Быстрая ссылка» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\LinkWidget --}}
@php
    $href = $preview || empty($data['url']) ? 'javascript:void(0)' : $data['url'];
    $hint = trim($data['title'] . ($data['second'] !== '' ? ' · ' . $data['second'] : ''));
    $types = ['proposal' => 'Коммерческое предложение', 'partner' => 'Партнёр', 'company' => 'Компания', 'deal' => 'Сделка Битрикс24'];
    $type_label = $types[$data['type'] ?? ''] ?? 'Ссылка';
    // с высоты 3 ячейки — карточка: значок и тип сверху, название и вторая строка снизу
    $card = !in_array($dh, ['xs', 'sm'], true);
    // низкий блок: название переносится на столько строк, сколько влезает по высоте
    // (заголовок виджета съедает строку-две); значок в узком блоке — в строку с названием
    $head = !empty($settings['show_title']);
    $lines_xs = $dh === 'xs' ? ($head ? 1 : 2) : ($head ? 3 : 5);
    $lines_row = $dh === 'sm' && !$head ? 2 : 1;
@endphp
@if(empty($data['found']))
    <div class="desk-empty">
        <i class="fa-light fa-link"></i> <span class="desk-hide-narrow">Выберите объект в настройках</span>
    </div>
@elseif($card)
    <a href="{{ $href }}" class="desk-link text-reset lk-card" title="{{ $hint }}">
        <div class="d-flex align-items-start gap-2 lk-top">
            <span class="symbol flex-shrink-0 lk-symbol">
                <span class="symbol-label bg-light-primary">
                    <i class="fa-light {{ $data['icon'] }} text-primary lk-icon"></i>
                </span>
            </span>
            <span class="desk-label desk-nowrap flex-grow-1 pt-1 desk-only-w-md" title="{{ $type_label }}">{{ $type_label }}</span>
            <i class="fa-light fa-arrow-up-right text-gray-500 ms-auto lk-go"></i>
        </div>
        <div class="lk-bottom">
            <div class="fw-bold lk-title">{{ $data['title'] }}</div>
            @if($data['second'] !== '')
                <div class="desk-muted lk-second">{{ $data['second'] }}</div>
            @endif
        </div>
    </a>
@elseif($dw === 'xs')
    <a href="{{ $href }}" class="desk-link desk-center align-items-center text-center text-reset" title="{{ $hint }}">
        <div class="fw-bold fs-8 mw-100 lk-lines lk-lines-{{ $lines_xs }}">
            <i class="fa-light {{ $data['icon'] }} text-primary me-1"></i>{{ $data['title'] }}
        </div>
    </a>
@else
    <a href="{{ $href }}" class="desk-link d-flex align-items-center gap-3 h-100 text-reset" title="{{ $hint }}">
        <div class="symbol symbol-35px flex-shrink-0 desk-hide-narrow">
            <span class="symbol-label bg-light-primary text-primary">
                <i class="fa-light {{ $data['icon'] }} fs-3 text-primary"></i>
            </span>
        </div>
        <div class="desk-grow flex-grow-1">
            <div class="fw-bold lk-lines lk-lines-{{ $lines_row }}">{{ $data['title'] }}</div>
            @if($data['second'] !== '' && $dh !== 'xs')
                <div class="desk-muted fs-8 text-truncate">{{ $data['second'] }}</div>
            @endif
        </div>
        <i class="fa-light fa-arrow-up-right text-gray-500 flex-shrink-0 desk-only-w-md"></i>
    </a>
@endif
