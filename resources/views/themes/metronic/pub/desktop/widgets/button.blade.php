{{-- Виджет «Действие» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\ButtonWidget --}}
@php
    // попап и сайдбар открывает проектный box(); обычный переход — ссылкой
    $onclick = match ($data['mode']) {
        'box' => "box({href: '" . e($data['url']) . "'})",
        'sidebar' => "sidebar({href: '" . e($data['url']) . "'})",
        default => null,
    };
    // подсказка, что случится по нажатию: видна в высоком или широком блоке (common-1.css)
    $hint = match ($data['mode']) {
        'box' => 'Откроется окно',
        'sidebar' => 'Откроется панель',
        default => 'Переход на страницу',
    } . ($data['confirm'] ? ' · с подтверждением' : '');
@endphp
@if(empty($data['url']))
    <div class="desk-empty">
        <i class="fa-light fa-bolt"></i> Выберите действие в настройках
    </div>
@else
    {{-- кнопка-плитка на всё тело: низкий блок — значок и подпись в строку, высокий — значок над подписью --}}
    <a @class(['btn desk-action', 'btn-' . $data['color']])
       href="{{ $onclick || $preview ? 'javascript:void(0)' : $data['url'] }}"
       @if(!$preview && $onclick) onclick="{{ $onclick }}" @endif
       @if(!$preview && $data['confirm']) data-desk-confirm="{{ $data['label'] }}?" @endif
       title="{{ $data['label'] }}">
        <span class="desk-action-main">
            <i class="fa-light {{ $data['icon'] }} desk-action-icon"></i>
            <span class="desk-action-label">{{ $data['label'] }}</span>
        </span>
        <span class="desk-action-hint" title="{{ $hint }}">{{ $hint }}</span>
    </a>
@endif
