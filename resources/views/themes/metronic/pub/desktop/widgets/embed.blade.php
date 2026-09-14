{{-- Виджет «Внешняя страница» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\EmbedWidget --}}
@if($preview || !empty($data['preview']))
    {{-- в библиотеке чужую страницу не грузим: показываем, что это за блок --}}
    <div class="desk-empty flex-column">
        <i class="fa-light fa-browser fs-2"></i>
        <span>Внешний отчёт на столе</span>
        <span class="fs-8">домены из константы {{ $widget::DOMAINS_KEY }}</span>
    </div>
@elseif(empty($data['url']))
    <div class="desk-empty">
        <i class="fa-light fa-browser"></i> Укажите адрес страницы в настройках
    </div>
@elseif(!$data['allowed'])
    <div class="desk-empty flex-column text-center">
        <i class="fa-light fa-shield-halved fs-2 text-warning"></i>
        <span>Домен {{ $data['host'] ?: 'страницы' }} не разрешён</span>
        <span class="fs-8 desk-only-h-md">
            @if(empty($data['domains']))
                Список разрешённых доменов пуст — заполните константу {{ $widget::DOMAINS_KEY }}
            @else
                Разрешены: {{ implode(', ', array_slice($data['domains'], 0, 5)) }}
            @endif
        </span>
    </div>
@else
    <div class="desk-bleed h-100" @if($data['reload']) data-desk-reload="{{ $data['reload'] }}" @endif>
        <iframe src="{{ $data['url'] }}" title="{{ $data['host'] }}" loading="lazy"
                referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
                style="width: 100%; height: 100%; border: 0; display: block;"></iframe>
    </div>
@endif
