{{-- Виджет «Внешняя страница» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\EmbedWidget --}}
@if($preview || !empty($data['preview']))
    {{-- в библиотеке чужую страницу не грузим: схема окна браузера с доменом и страницей-отчётом --}}
    <div class="desk-bleed emb-mock" title="Внешний отчёт с разрешённого домена (константа {{ $widget::DOMAINS_KEY }})">
        <div class="emb-bar">
            <span class="emb-dots desk-hide-narrow"><i></i><i></i><i></i></span>
            <span class="emb-address">
                <i class="fa-light fa-lock"></i>
                <span class="desk-nowrap desk-hide-narrow">{{ $data['host'] }}</span>
            </span>
        </div>
        <svg class="emb-page" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <rect class="emb-s3" x="4" y="6" width="34" height="5"/>
            <rect class="emb-s2" x="4" y="16" width="28" height="14"/>
            <rect class="emb-s2" x="36" y="16" width="28" height="14"/>
            <rect class="emb-s2" x="68" y="16" width="28" height="14"/>
            <rect class="emb-s1" x="4" y="35" width="92" height="60"/>
            @foreach([38, 52, 44, 66, 58, 74, 62, 86] as $index => $bar)
                <rect class="emb-bar-fill" x="{{ 8 + $index * 11 }}" y="{{ 95 - $bar * .6 }}" width="7" height="{{ $bar * .6 }}"/>
            @endforeach
        </svg>
    </div>
@elseif(empty($data['url']))
    <div class="desk-empty emb-note" title="Укажите адрес страницы в настройках">
        <i class="fa-light fa-browser"></i>
        <span class="desk-hide-narrow">Укажите адрес страницы в настройках</span>
    </div>
@elseif(!$data['allowed'])
    @php($denied = 'Домен ' . ($data['host'] ?: 'страницы') . ' не разрешён')
    <div class="desk-empty emb-note" title="{{ $denied }}">
        <i class="fa-light fa-shield text-warning"></i>
        <div class="desk-hide-narrow emb-note-text">
            <div>{{ $denied }}</div>
            <div class="fs-8 desk-only-h-md">
                @if(empty($data['domains']))
                    Список разрешённых доменов пуст — заполните константу {{ $widget::DOMAINS_KEY }}
                @else
                    Разрешены: {{ implode(', ', array_slice($data['domains'], 0, 5)) }}
                @endif
            </div>
        </div>
    </div>
@else
    <div class="desk-bleed h-100" @if($data['reload']) data-desk-reload="{{ $data['reload'] }}" @endif>
        <iframe src="{{ $data['url'] }}" title="{{ $data['host'] }}" loading="lazy"
                referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
                style="width: 100%; height: 100%; border: 0; display: block;"></iframe>
    </div>
@endif
