{{-- Виджет «Истекающие ключи» (patch v30): App\Modules\Pub\Desktop\Widgets\Keys\KeysExpiringWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - низкий блок — подпись и число; если он шире 300 px — рядом сумма продления и истёкшие;
    - узкий блок (dw xs) — подпись короче («за 30 дн.»), сумма без слова «продление»;
    - высота от трёх ячеек — список ближайших ключей ($rows_max в .desk-fit, подпись «ещё N»),
      шире 230 px в строке — дата окончания;
    - высокий блок (dh xl) не узкий — число крупнее (desk-value-lg).
--}}
@php
    $days_word = \App\Facades\Tools::morph($data['days'], 'день', 'дня', 'дней');
    $show_expired = $settings['expired'] && $data['expired'] > 0;
    $narrow = $dw === 'xs';
    $big = $dh === 'xl' && !in_array($dw, ['xs', 'sm'], true);

    // список ближайших ключей — когда блок выше двух ячеек; не влезшие строки спрячет .desk-fit
    $with_list = !in_array($dh, ['xs', 'sm'], true);
    $soonest = $with_list ? array_slice($data['soonest'] ?? [], 0, $rows_max) : [];

    // из чего сложена сумма: при «Учитывать уже истёкшие» в ней и ключи, истёкшие за хвост дней
    $amount_title = 'Оценка продления: ' . $widget::money($data['amount'], $data['symbol'], false)
        . ' · ключей: ' . ($data['count'] + ($show_expired ? $data['expired'] : 0))
        . ($show_expired ? ' (истекают: ' . $data['count'] . ', истекли за ' . $data['tail'] . ' дн.: ' . $data['expired'] . ')' : '')
        . (!empty($data['skipped']) ? ' · без курса: ' . $data['skipped'] : '');
@endphp
<div class="desk-stack ke">
    <div class="desk-label desk-nowrap flex-shrink-0" title="Истекает за {{ $data['days'] }} {{ $days_word }}">
        {{ $narrow ? 'за ' . $data['days'] . ' дн.' : 'истекает за ' . $data['days'] . ' ' . $days_word }}
    </div>

    <div @class(['ke-main', 'desk-stack-grow' => !$with_list])>
        <div @class(['desk-value', 'desk-value-lg' => $big, 'text-warning' => $data['count'] > 0])>{{ $data['count'] }}</div>

        <div @class(['ke-side', 'fs-8' => $narrow])>
            @if($settings['show_amount'])
                <span class="desk-muted text-nowrap" title="{{ $amount_title }}">{{ $narrow ? '' : 'продление ' }}{{ $widget::money($data['amount'], $data['symbol']) }}</span>
            @endif
            @if($show_expired)
                <span class="text-danger fs-8 text-nowrap" title="Истекли за последние {{ $data['tail'] }} дн.">истекли: {{ $data['expired'] }}</span>
            @endif
            @if(!empty($data['skipped']) && $settings['show_amount'])
                <span class="desk-muted fs-8 text-nowrap desk-only-w-md">без курса: {{ $data['skipped'] }}</span>
            @endif
        </div>
    </div>

    @if($with_list)
        @if(empty($soonest))
            <div class="desk-stack-grow desk-muted fs-8 ke-list">В ближайшие {{ $data['days'] }} {{ $days_word }} ничего не истекает</div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit ke-list" data-fit-min="0">
                @foreach($soonest as $key)
                    <li>
                        <span class="desk-grow" title="{{ $key['company'] }} · до {{ $key['date'] }}">{{ $key['company'] }}</span>
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-md">{{ $key['date'] }}</span>
                        <span @class(['fw-semibold text-nowrap fs-8', 'text-danger' => $key['days_left'] <= 7, 'text-warning' => $key['days_left'] > 7])>{{ $key['days_left'] }} дн.</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8" data-fit-more="ещё {n}"></div>
        @endif
    @endif
</div>
