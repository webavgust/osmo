{{-- Виджет «Карточка КП» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ProposalCardWidget --}}
@php
    $href = $preview || empty($data['url']) ? 'javascript:void(0)' : $data['url'];
    $number = $data['number'] !== '' ? $data['number'] : 'б/н';
    $client = $data['company'] !== '' ? $data['company'] : $data['partner'];
    $cost = $data['amount'] === null ? '—' : $widget::money($data['amount'], $data['symbol']);
    $cost_full = $data['amount'] === null ? 'Расчёта нет' : $widget::money($data['amount'], $data['symbol'], false);
    $status_title = trim($data['status_label'] . ($data['reason'] !== '' ? ' · ' . $data['reason'] : '') . ($data['comment'] !== '' ? ' · ' . $data['comment'] : ''));
    $title = trim('КП № ' . $number . ' · ' . $data['name'] . ($client !== '' ? ' · ' . $client : '')
        . ($data['iterations'] > 1 ? ' · редакция ' . $data['iteration'] . ' из ' . $data['iterations'] : ''));
    $days_text = $data['days'] === null ? '' : $data['days'] . ' ' . \App\Facades\Tools::morph($data['days'], 'день', 'дня', 'дней') . ' назад';

    // высота 1–2 ячейки — номер и сумма; ширина 2 или высота ≥6 — список свойств, сделок и спецификаций
    // (режется подгоном); высота 3–5 — заголовок и плитки
    $compact = in_array($dh, ['xs', 'sm'], true);
    $narrow = $dw === 'xs';
    $props = !$compact && ($narrow || in_array($dh, ['lg', 'xl'], true));

    // узкий блок: «4,8» крупно и «млн ₽» под ним — сумма не уходит в многоточие
    $short = $data['amount'] === null ? '—' : $widget::compact($data['amount']);
    [$cost_number, $cost_unit] = preg_match('/^(.+) (млрд|млн|тыс\.)$/u', $short, $m)
        ? [$m[1], $m[2] . ' ' . $data['symbol']]
        : [$short, $data['amount'] === null ? '' : $data['symbol']];

    // свойства: [подпись, значение, подсказка, число (без многоточия)]
    $facts = array_values(array_filter([
        $settings['show_amount'] ? ['сумма', $cost, 'Основной вариант последней редакции: ' . $cost_full, true] : null,
        $settings['show_amount'] && $data['variants'] > 1 ? ['вариантов', (string) $data['variants'], 'Вариантов расчёта в редакции', true] : null,
        ['заказчик', $data['company'] !== '' ? $data['company'] : '—', 'Заказчик', false],
        $data['partner'] !== '' ? ['партнёр', $data['partner'], 'Партнёр', false] : null,
        $settings['show_manager'] ? ['менеджер', $data['manager'] !== '' ? $data['manager'] : '—', 'Менеджер', false] : null,
        // на ширине 2 длинные подписи не помещаются даже отдельной строкой — там короткие
        $settings['show_date'] ? [$narrow ? 'дата' : 'отправлено', $data['date'] ?? '—', 'Дата отправки последней редакции' . ($days_text !== '' ? ' · ' . $days_text : ''), true] : null,
        $data['iterations'] > 1 ? [$narrow ? 'ред.' : 'редакция', $data['iteration'] . ' из ' . $data['iterations'], 'Показана последняя редакция', true] : null,
        $data['reason'] !== '' ? ['причина', $data['reason'], $status_title, false] : null,
        $settings['show_deal'] ? ['сделки', $data['deals_count'] > 0 ? (string) $data['deals_count'] : 'нет', 'Привязанные сделки Битрикс24', true] : null,
        $settings['show_specs'] && $data['specs_count'] > 0
            ? [$narrow ? 'спец.' : 'спецификации', $data['specs_count'] . ($narrow ? '' : ' · ' . $widget::money($data['specs_sum'], $data['symbol'])),
                'Сумма спецификаций в валюте КП: ' . $widget::money($data['specs_sum'], $data['symbol'], false), true]
            : null,
        $data['external'] !== '' ? [$narrow ? 'CP' : 'OSMOVIEW CP', $data['external'], 'Перенесено из OSMOVIEW CP', true] : null,
    ]));
    $deals = $settings['show_deal'] ? $data['deals'] : [];
    $specs = $settings['show_specs'] ? $data['specs'] : [];
@endphp
@if(empty($data['found']))
    <div class="desk-empty">
        <i class="fa-light fa-file-invoice"></i> КП не выбрано
    </div>
@elseif($compact)
    <div class="desk-center">
        <div class="desk-label desk-nowrap" title="{{ $title }}">
            <a href="{{ $href }}" class="desk-link text-hover-primary">№ {{ $number }}</a>
        </div>
        @if($narrow && $settings['show_amount'])
            <div class="desk-value" title="Основной вариант: {{ $cost_full }}">{{ $cost_number }}</div>
            <div class="desk-label text-nowrap">{{ $cost_unit }}</div>
        @else
            <div class="d-flex align-items-baseline column-gap-3">
                @if($settings['show_amount'])
                    <div class="desk-value flex-shrink-0" title="Основной вариант: {{ $cost_full }}">{{ $cost }}</div>
                @else
                    <div class="desk-value desk-value-sm" title="{{ $title }}">{{ $client !== '' ? $client : $data['name'] }}</div>
                @endif
                @if($settings['show_status'])
                    <span class="badge badge-light-{{ $data['status_color'] }} text-nowrap flex-shrink-0 desk-only-w-lg" title="{{ $status_title }}">{{ $data['status_label'] }}</span>
                @endif
                <span class="desk-muted desk-nowrap desk-only-w-xl" title="{{ $title }}">{{ $data['name'] }}</span>
            </div>
        @endif
    </div>
@elseif($props)
    <div class="desk-stack">
        <div class="d-flex align-items-center column-gap-2 flex-wrap">
            <a href="{{ $href }}" class="desk-link fw-semibold text-hover-primary text-nowrap" title="{{ $title }}">{{ $narrow ? '' : '№ ' }}{{ $number }}</a>
            @if($settings['show_status'])
                <span class="text-{{ $data['status_color'] }} fs-8 fw-semibold" title="{{ $status_title }}"><i class="fa-light {{ $data['status_icon'] }} me-1"></i>{{ $data['status_label'] }}</span>
            @endif
        </div>
        @unless($narrow)
            <div class="fw-semibold desk-card-clamp" title="{{ $title }}">{{ $data['name'] }}</div>
        @endunless

        <ul class="desk-list desk-stack-grow desk-fit desk-card-props" data-fit-min="0">
            @foreach($facts as [$label, $value, $hint, $numeric])
                <li title="{{ $hint }}">
                    <span class="desk-label flex-shrink-0">{{ $label }}</span>
                    @if($numeric)
                        <span @class(['ms-auto fw-bold text-nowrap', 'fs-8' => $narrow])>{{ $value }}</span>
                    @else
                        <span @class(['desk-grow text-end fw-semibold', 'fs-8' => $narrow]) title="{{ $value }}">{{ $value }}</span>
                    @endif
                </li>
            @endforeach
            @foreach($deals as $deal)
                <li>
                    <i class="fa-light fa-handshake text-info flex-shrink-0 desk-hide-narrow"></i>
                    @if($preview || empty($deal['url']))
                        <span class="desk-grow" title="{{ $deal['title'] }}">{{ $deal['title'] }}</span>
                    @else
                        <a href="{{ $deal['url'] }}" target="_blank" class="desk-link desk-grow text-hover-primary" title="Сделка в Битрикс24: {{ $deal['title'] }}">{{ $deal['title'] }}</a>
                    @endif
                    @if($deal['main'])
                        <span class="badge badge-light-primary fs-8 flex-shrink-0 desk-only-w-md" title="Главная сделка КП">главная</span>
                    @endif
                    @if($deal['stage'] !== '')
                        <span class="desk-muted fs-8 desk-nowrap desk-only-w-lg desk-card-stage" title="{{ $deal['stage'] }}">{{ $deal['stage'] }}</span>
                    @endif
                </li>
            @endforeach
            @foreach($specs as $spec)
                <li>
                    <i class="fa-light fa-file-signature text-success flex-shrink-0 desk-hide-narrow"></i>
                    <span class="desk-grow" title="{{ $spec['name'] }}">{{ $spec['name'] }}</span>
                    @if(!$spec['signed'])
                        <span class="badge badge-light-warning fs-8 flex-shrink-0 desk-only-w-md">не подписана</span>
                    @endif
                    <span @class(['fw-semibold text-nowrap flex-shrink-0', 'fs-8' => $narrow]) title="{{ $widget::money($spec['amount'], $spec['symbol'], false) }}">{{ $widget::money($spec['amount'], $spec['symbol']) }}</span>
                </li>
            @endforeach
        </ul>

        @if($data['comment'] !== '' && !$narrow)
            <div class="desk-muted fs-8 desk-card-clamp desk-only-h-lg" title="{{ $data['comment'] }}">{{ $data['comment'] }}</div>
        @endif
    </div>
@else
    <div class="desk-stack">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-semibold text-nowrap flex-shrink-0 desk-hide-narrow">№ {{ $number }}</span>
            <a href="{{ $href }}" class="desk-link desk-grow text-hover-primary" title="{{ $title }}">{{ $data['name'] }}</a>
            @if($data['iterations'] > 1)
                <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-lg" title="Показана последняя редакция">ред. {{ $data['iteration'] }}</span>
            @endif
            @if($settings['show_status'])
                <span class="badge badge-light-{{ $data['status_color'] }} flex-shrink-0 text-nowrap" title="{{ $status_title }}">{{ $data['status_label'] }}</span>
            @endif
        </div>

        {{-- плитки: сумма по своей ширине, остальные делят место; не влезшие прячет подгон --}}
        <div class="desk-tiles desk-stack-grow desk-fit desk-card-tiles" data-fit-min="0">
            @if($settings['show_amount'])
                <div class="desk-card-amount" title="Основной вариант последней редакции: {{ $cost_full }}">
                    <div class="desk-label">сумма{{ $data['variants'] > 1 ? ' · ' . $data['variants'] . ' вар.' : '' }}</div>
                    <div class="desk-value desk-value-sm">{{ $cost }}</div>
                </div>
            @endif
            <div>
                <div class="desk-label">{{ $data['company'] !== '' || $client === '' ? 'заказчик' : 'партнёр' }}</div>
                <div class="fw-bold desk-nowrap" title="{{ $client !== '' ? $client : 'Компания не указана' }}">{{ $client !== '' ? $client : '—' }}</div>
            </div>
            @if($settings['show_date'])
                <div title="Дата отправки последней редакции{{ $days_text !== '' ? ' · ' . $days_text : '' }}">
                    <div class="desk-label">отправлено</div>
                    <div class="fw-bold text-nowrap">{{ $data['date'] ?? '—' }}</div>
                </div>
            @endif
            @if($settings['show_manager'])
                <div>
                    <div class="desk-label">менеджер</div>
                    <div class="fw-bold desk-nowrap" title="{{ $data['manager'] !== '' ? $data['manager'] : 'Менеджер не назначен' }}">{{ $data['manager'] !== '' ? $data['manager'] : '—' }}</div>
                </div>
            @endif
            @if($settings['show_deal'])
                <div title="Привязанные сделки Битрикс24">
                    <div class="desk-label">сделки</div>
                    <div @class(['fw-bold', 'text-warning' => $data['deals_count'] === 0])>{{ $data['deals_count'] > 0 ? $data['deals_count'] : 'нет' }}</div>
                </div>
            @endif
        </div>
    </div>
@endif
