{{-- Виджет «Карточка сделки» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\DealCardWidget

    Поведение по размерам:
    - низкий блок (dh xs|sm): название и сумма (на ширине 2 — без символа), от 420 px — стадия;
    - высота ≥3: шапка (название, стадия значком на ширине ≥6, «↗» шире 170 px), плитки «сумма» и
      (ширина ≥6) «квартал плана», поля строками в .desk-fit — стадия и квартал (ширина 2–5),
      партнёр, заказчик, ответственный, КП, проект; в узком блоке подпись над значением;
    - высота ≥6 (dh lg|xl): + лестница стадий воронки с текущей стадией (подгон прячет её первой),
      на ширине ≥12 — отдельной колонкой справа. Стили — osmo-desktop-widgets/funnel-1.css.
--}}
@php
    $low = in_array($dh, ['xs', 'sm'], true);
    $narrow = in_array($dw, ['xs', 'sm'], true);
    $tall = in_array($dh, ['lg', 'xl'], true);

    $href = fn($url) => $preview || empty($url) ? 'javascript:void(0)' : $url;
    $box = fn($url) => $preview || empty($url) ? 'javascript:void(0)' : "javascript:box({href: '" . e($url) . "'})";

    $money_title = $data['amount'] === null
        ? 'Сумма сделки не указана'
        : 'Сумма сделки: ' . $widget::money($data['amount'], $data['symbol'], false)
            . ($data['converted'] ? '' : ' (пересчитать в валюту стола не удалось)');
    $amount = $data['amount'] === null ? '—' : $widget::compact($data['amount']);

    $title = $data['title'] . ($data['stage'] !== '' ? ' · ' . $data['stage'] : '');
    $quarter = $data['quarter'] !== '' ? str_replace(' квартал ', ' кв. ', $data['quarter']) : 'не выбран';
    $has_stage = $settings['stage'] && $data['stage'] !== '';

    // лестница стадий воронки — если стадия сделки из воронки
    $stages = \App\Modules\Pub\Desktop\Widgets\Funnel\FunnelTableWidget::STAGES;
    $current = $has_stage ? array_search($data['stage'], $stages, true) : false;
    $ladder = $tall && $current !== false;
    $ladder_side = $ladder && in_array($dw, ['lg', 'xl'], true);

    // поля карточки строками
    $fields = [];
    if ($has_stage && $narrow) {
        $fields[] = ['label' => 'стадия', 'text' => $data['stage'], 'title' => $data['stage']];
    }
    if ($settings['quarter'] && $narrow) {
        $fields[] = ['label' => 'квартал плана', 'text' => $quarter, 'title' => 'Плановый квартал исполнения сделки'];
    }
    if ($settings['parties']) {
        $fields[] = ['label' => 'партнёр', 'text' => $data['partner'] ?: '—', 'title' => $data['partner'] ?: 'не указан'];
        $fields[] = ['label' => 'заказчик', 'text' => $data['customer'] ?: '—', 'title' => $data['customer'] ?: 'не указан'];
    }
    $fields[] = ['label' => 'ответственный', 'text' => $data['manager'] ?: '—', 'title' => $data['manager'] ?: 'не указан'];
    if ($settings['links']) {
        $fields[] = $data['proposal'] !== ''
            ? ['label' => 'КП', 'text' => $data['proposal'], 'title' => $data['proposal'], 'href' => $href($data['proposal_url'])]
            : ['label' => 'КП', 'text' => 'не привязано', 'title' => 'К сделке не привязано КП', 'muted' => true];
        $fields[] = $data['project'] !== ''
            ? ['label' => 'проект', 'text' => $data['project'], 'title' => $data['project'], 'href' => $box($data['project_url'])]
            : ['label' => 'проект', 'text' => 'нет', 'title' => 'По сделке не заведён проект', 'muted' => true];
    }
@endphp
@if(empty($data['found']))
    <div class="desk-empty">
        <i class="fa-light fa-handshake"></i> Сделка не выбрана
    </div>
@elseif($low)
    <div class="desk-center">
        <a href="{{ $href($data['url'] ?: $data['bitrix_url']) }}" class="desk-link desk-label desk-nowrap d-block text-hover-primary"
           title="{{ $title }}">{{ $data['title'] }}</a>
        <div class="d-flex align-items-baseline gap-2 min-w-0">
            @if($settings['money'])
                <span class="desk-value desk-value-sm" title="{{ $money_title }}">{{ $narrow ? $amount : $widget::money($data['amount'], $data['symbol']) }}</span>
            @endif
            @if($has_stage)
                <span class="badge badge-light-primary text-nowrap desk-only-w-lg">{{ $data['stage'] }}</span>
            @endif
        </div>
    </div>
@else
    <div class="desk-stack">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <a href="{{ $href($data['url'] ?: $data['bitrix_url']) }}" class="desk-link desk-grow fw-semibold text-hover-primary" title="{{ $title }}">{{ $data['title'] }}</a>
            @if($has_stage && !$narrow)
                <span class="badge badge-light-primary flex-shrink-0 text-nowrap">{{ $data['stage'] }}</span>
            @endif
            <a href="{{ $href($data['bitrix_url']) }}" target="_blank" class="desk-link flex-shrink-0 text-hover-primary desk-hide-narrow"
               title="Открыть сделку в Битрикс24">
                <i class="fa-light fa-arrow-up-right-from-square"></i>
            </a>
        </div>

        @if($settings['money'] || ($settings['quarter'] && !$narrow))
            <div class="desk-tiles dc-tiles">
                @if($settings['money'])
                    <div>
                        <div class="desk-label desk-nowrap">сумма, {{ $data['symbol'] }}</div>
                        <div class="desk-value" title="{{ $money_title }}">{{ $amount }}</div>
                    </div>
                @endif
                @if($settings['quarter'] && !$narrow)
                    <div>
                        <div class="desk-label desk-nowrap">квартал плана</div>
                        <div class="desk-value" title="Плановый квартал исполнения сделки">{{ $quarter }}</div>
                    </div>
                @endif
            </div>
        @endif

        <div @class(['desk-stack-grow', 'd-flex gap-5' => $ladder_side])>
            <ul class="desk-list desk-fit dc-fields h-100 flex-grow-1" data-fit-min="0">
                @foreach($fields as $field)
                    <li title="{{ $field['title'] }}">
                        <span class="desk-label desk-nowrap flex-shrink-0" title="{{ $field['label'] }}">{{ $field['label'] }}</span>
                        @if(!empty($field['href']))
                            <a href="{{ $field['href'] }}" class="desk-link desk-grow text-end text-hover-primary">{{ $field['text'] }}</a>
                        @else
                            <span @class(['desk-grow text-end', 'desk-muted' => !empty($field['muted'])])>{{ $field['text'] }}</span>
                        @endif
                    </li>
                @endforeach
                @if($ladder && !$ladder_side)
                    <li class="desk-label pt-3">стадии воронки</li>
                    @foreach($stages as $i => $stage)
                        <li @class(['fw-bold' => $i === $current, 'desk-muted' => $i > $current]) title="{{ $stage }}">
                            <i @class(['fa-light', 'fa-circle-check text-success' => $i < $current, 'fa-circle-dot text-primary' => $i === $current, 'fa-circle' => $i > $current])></i>
                            <span class="desk-grow">{{ $stage }}</span>
                        </li>
                    @endforeach
                @endif
                <li class="desk-label" data-fit-more="ещё {n}"></li>
            </ul>
            @if($ladder_side)
                <ul class="desk-list desk-fit h-100 flex-grow-1" data-fit-min="0">
                    @foreach($stages as $i => $stage)
                        <li @class(['fw-bold' => $i === $current, 'desk-muted' => $i > $current]) title="{{ $stage }}">
                            <i @class(['fa-light', 'fa-circle-check text-success' => $i < $current, 'fa-circle-dot text-primary' => $i === $current, 'fa-circle' => $i > $current])></i>
                            <span class="desk-grow">{{ $stage }}</span>
                        </li>
                    @endforeach
                    <li class="desk-label" data-fit-more="ещё {n}"></li>
                </ul>
            @endif
        </div>
    </div>
@endif
