{{-- Виджет «Реестр сделок» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\DealsRegistryWidget

    Поведение по размерам:
    - низкий блок (dh xs|sm): число сделок по отбору, вкладка (шире 170 px), «с КП» (шире 230 px),
      значок «отбор» (шире 420 px);
    - высота ≥3: строка сводки, ниже сделки в .desk-fit (не больше настройки «Сколько сделок»):
      ширина 2–11 — список: точка стадии (шире 170 px), название, КП (от 420 px), сумма;
      на ширине 2 название и сумма в две строки;
    - ширина ≥12 (dw lg|xl): таблица — сделка, стадия, дата, сумма, КП; от 620 px ещё ID и менеджер,
      от 800 px — партнёр (osmo-desktop-widgets/funnel-1.css).
--}}
@php
    $low = in_array($dh, ['xs', 'sm'], true);
    $table = in_array($dw, ['lg', 'xl'], true);
    $list = $low ? [] : array_slice($data['rows'], 0, min(max(1, (int) $settings['limit']), $rows_max));

    // сделка открывается в Битрикс24, КП — на портале; в превью ссылки не работают
    $href = fn($url) => $preview || empty($url) ? 'javascript:void(0)' : $url;
    $blank = fn($url) => $preview || empty($url) ? '' : '_blank';

    $money = fn($row) => $row['amount'] > 0 ? $widget::money($row['amount'], $row['symbol']) : '—';
    $full = fn($row) => $row['amount'] > 0 ? $widget::money($row['amount'], $row['symbol'], false) : 'сумма не указана';

    // подсказка строки: партнёр, заказчик, стадия и менеджер — как в столбцах реестра
    $hint = fn($row) => '#' . $row['id'] . ' · ' . $row['title']
        . ($row['company'] !== '' ? "\nПартнёр: " . $row['company'] : '')
        . ($row['customer'] !== '' ? "\nЗаказчик: " . $row['customer'] : '')
        . "\nСтадия: " . $row['stage'] . ' · ' . $row['date']
        . ($row['manager'] !== '' ? "\nМенеджер: " . $row['manager'] : '')
        . "\nСумма: " . $full($row);

    $mode = mb_strtolower($data['mode_label']);
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-table-list"></i> Сделок по отбору нет
    </div>
@else
    <div @class(['desk-stack', 'desk-center' => $low])>
        @if($low)
            {{-- низкий блок: ячейки в ряд — число сделок, с КП, без КП, последняя сделка;
                 не влезшие по ширине прячет подгон .desk-fit (data-fit-axis="x") с конца --}}
            @php $last = $data['rows'][0]; @endphp
            <div @class(['d-flex align-items-end gap-4 min-w-0', 'desk-fit' => $dw !== 'xs']) data-fit-axis="x">
                <div class="flex-shrink-0" title="{{ $data['mode_label'] }}: {{ $data['total'] }}{{ $data['filtered'] ? ' (с отбором)' : '' }}">
                    <div class="desk-label desk-nowrap">{{ $dw === 'xs' ? 'сделок' : $mode }}</div>
                    <div class="desk-value desk-value-sm">{{ $data['total'] }}</div>
                </div>
                @if($dw !== 'xs')
                    <div class="dr-cell" title="Сделок с привязанным КП портала">
                        <div class="desk-label desk-nowrap">с КП</div>
                        <div class="fw-bold">{{ $data['with_proposal'] }}</div>
                    </div>
                    <div class="dr-cell" title="Сделок без КП портала">
                        <div class="desk-label desk-nowrap">без КП</div>
                        <div class="fw-bold">{{ $data['total'] - $data['with_proposal'] }}</div>
                    </div>
                    <div class="dr-cell" title="{{ $hint($last) }}">
                        <div class="desk-label desk-nowrap">последняя · {{ $last['date'] }}</div>
                        <div class="fw-semibold text-nowrap">{{ $last['title'] }}</div>
                    </div>
                @endif
            </div>
        @else
            <div class="d-flex gap-2 align-items-baseline min-w-0">
                <span class="desk-label desk-nowrap desk-hide-narrow" title="Вкладка реестра: {{ $data['mode_label'] }}">{{ $mode }}</span>
                <span class="fw-bold text-nowrap" title="Столько сделок в реестре по этому отбору">{{ $data['total'] }}</span>
                <span class="desk-muted fs-8 text-nowrap desk-only-w-md" title="Сделок с привязанным КП портала">с КП: {{ $data['with_proposal'] }}</span>
                @if($data['filtered'])
                    <span class="badge badge-light-info fs-8 text-nowrap desk-only-w-lg" title="Виджет показывает реестр с отбором">отбор</span>
                @endif
            </div>
        @endif

        @if(!empty($list) && $table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                <table class="desk-table">
                    <thead>
                    <tr>
                        <th class="num desk-only-w-xl">ID</th>
                        <th class="dr-name">Сделка</th>
                        <th class="dr-stage">Стадия</th>
                        <th class="dr-wide">Партнёр</th>
                        <th class="desk-only-w-xl dr-man">Менеджер</th>
                        <th>Дата</th>
                        <th class="num">Сумма</th>
                        <th>КП</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($list as $row)
                        <tr>
                            <td class="num desk-muted desk-only-w-xl">{{ $row['id'] }}</td>
                            <td class="desk-cut dr-name">
                                <a href="{{ $href($row['url']) }}" target="{{ $blank($row['url']) }}"
                                   class="desk-link d-block text-truncate fw-semibold text-hover-primary"
                                   title="{{ $hint($row) }}">{{ $row['title'] }}</a>
                            </td>
                            <td class="desk-cut dr-stage" title="{{ $row['stage'] }}">
                                <span class="bullet bullet-dot bg-{{ $row['color'] }} w-8px h-8px me-1"></span>{{ $row['stage'] }}
                            </td>
                            <td class="desk-cut desk-muted dr-wide" title="{{ $row['company'] ?: 'партнёр не указан' }}">{{ $row['company'] ?: '—' }}</td>
                            <td class="desk-cut desk-muted desk-only-w-xl dr-man" title="{{ $row['manager'] }}">{{ $row['manager'] ?: '—' }}</td>
                            <td class="desk-muted text-nowrap">{{ $row['date'] }}</td>
                            <td class="num fw-semibold text-nowrap" title="{{ $full($row) }}">{{ $money($row) }}</td>
                            <td>
                                @if($row['proposal'])
                                    <a href="{{ $href($row['proposal_url']) }}" class="desk-link badge badge-light-success fs-8 text-nowrap"
                                       title="{{ $row['proposal_name'] }}">{{ $row['proposal'] }}</a>
                                @else
                                    <span class="desk-muted fs-8" title="КП не привязано">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @elseif(!empty($list))
            <ul class="desk-list desk-stack-grow desk-fit dr-list" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <span class="bullet bullet-dot bg-{{ $row['color'] }} w-8px h-8px flex-shrink-0 desk-hide-narrow"
                              title="{{ $row['stage'] }}"></span>
                        <a href="{{ $href($row['url']) }}" target="{{ $blank($row['url']) }}"
                           class="desk-link desk-grow text-hover-primary" title="{{ $hint($row) }}">{{ $row['title'] }}</a>
                        @if($row['proposal'])
                            <span class="badge badge-light-success fs-8 flex-shrink-0 desk-only-w-lg"
                                  title="{{ $row['proposal_name'] }}">{{ $row['proposal'] }}</span>
                        @endif
                        {{-- на ширине 2 сумма без символа валюты: «184 тыс. ₽» шире самого блока --}}
                        <span class="fw-semibold text-nowrap flex-shrink-0" title="{{ $full($row) }}">{{ $dw === 'xs' && $row['amount'] > 0 ? $widget::compact($row['amount']) : $money($row) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        @if(!empty($list))
            <div class="desk-label desk-nowrap desk-hide-short" data-fit-more="ещё {n}"></div>
        @endif
    </div>
@endif
