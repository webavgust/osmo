{{-- Виджет «Реестр лицензий» (patch v30): App\Modules\Pub\Desktop\Widgets\Keys\LicenseRegistryWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - узкий или низкий блок — список «компания — остаток дней»; шире 230 px — + дата окончания,
      шире 420 px — + сумма, шире 620 px — + партнёр;
    - высокий список (выше 230 px) — итог внизу: число лицензий и сумма;
    - широкий (dw lg|xl) и не низкий блок — таблица: компания, остаток; шире 230 px — окончание,
      шире 420 px — спецификация и сумма, шире 620 px — партнёр; итог — в подвале таблицы;
    - строки с запасом ($rows_max), не влезшие прячет .desk-fit, подпись «ещё N».
--}}
@php
    $symbol = $data['symbol'];
    $table = in_array($dw, ['lg', 'xl'], true) && !in_array($dh, ['xs', 'sm'], true);
    $list = array_slice($data['rows'], 0, max(1, $rows_max));
    $href = fn($row) => $preview || empty($row['company_url']) ? 'javascript:void(0)' : $row['company_url'];
    $left = fn($days) => $days === null ? '—' : ($days < 0 ? abs($days) . ' дн. назад' : $days . ' дн.');
    $licenses = \App\Facades\Tools::morph($data['count'], 'лицензия', 'лицензии', 'лицензий');
    $companies = \App\Facades\Tools::morph($data['companies'], 'компания', 'компании', 'компаний');
    $total_title = $widget::money($data['amount'], $symbol, false) . ($data['skipped'] ? ' · без курса: ' . $data['skipped'] : '');
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-rectangle-list"></i> Лицензий нет
    </div>
@elseif($table)
    <div class="desk-stack">
        <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
            <table class="desk-table">
                <thead>
                    <tr>
                        <th class="desk-only-w-xl">Партнёр</th>
                        <th>Компания</th>
                        @if($settings['show_spec'])
                            <th class="desk-only-w-lg">Спецификация</th>
                        @endif
                        <th class="num desk-only-w-md">Окончание</th>
                        <th class="num">Осталось</th>
                        @if($settings['show_amount'])
                            <th class="num desk-only-w-lg">Сумма</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $row)
                        <tr>
                            <td class="desk-cut desk-muted desk-only-w-xl lr-partner" title="{{ $row['partner'] }}">{{ $row['partner'] }}</td>
                            <td class="desk-cut">
                                <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold"
                                   title="{{ $row['company'] }}{{ $row['code'] ? ' · ключ ' . $row['code'] : '' }}">{{ $row['company'] }}</a>
                            </td>
                            @if($settings['show_spec'])
                                <td class="desk-cut desk-muted desk-only-w-lg" title="{{ $row['spec'] }}{{ $row['contract'] ? ' · договор ' . $row['contract'] : '' }}">{{ $row['spec'] }}</td>
                            @endif
                            <td class="num desk-muted desk-only-w-md">{{ $row['to'] ?? '—' }}</td>
                            <td class="num">
                                <span class="badge badge-light-{{ $row['state_color'] }}" title="{{ $row['state_label'] }}">{{ $left($row['days']) }}</span>
                            </td>
                            @if($settings['show_amount'])
                                <td class="num desk-only-w-lg" title="{{ $widget::money($row['amount'], $symbol, false) }}">{{ $widget::money($row['amount'], $symbol) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="desk-only-w-xl"></td>
                        <td class="text-nowrap">Итого</td>
                        @if($settings['show_spec'])
                            <td class="desk-muted fw-normal text-nowrap desk-only-w-lg">{{ $data['companies'] }} {{ $companies }}</td>
                        @endif
                        <td class="num desk-muted fw-normal text-nowrap desk-only-w-md">{{ $data['count'] }} {{ $licenses }}</td>
                        <td class="num"></td>
                        @if($settings['show_amount'])
                            <td class="num text-nowrap desk-only-w-lg" title="{{ $total_title }}">{{ $widget::money($data['amount'], $symbol) }}</td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="desk-muted fs-8" data-fit-more="ещё {n}"></div>
    </div>
@else
    <div class="desk-stack">
        <ul class="desk-list desk-stack-grow desk-fit">
            @foreach($list as $row)
                <li>
                    <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $row['company'] }} · до {{ $row['to'] ?? '—' }}">{{ $row['company'] }}</a>
                    <span class="desk-muted fs-8 desk-nowrap desk-only-w-xl lr-partner" title="{{ $row['partner'] }}">{{ $row['partner'] }}</span>
                    <span class="desk-muted fs-8 text-nowrap desk-only-w-md">{{ $row['to'] ?? '—' }}</span>
                    @if($settings['show_amount'])
                        <span class="fs-8 text-nowrap desk-only-w-lg" title="{{ $widget::money($row['amount'], $symbol, false) }}">{{ $widget::money($row['amount'], $symbol) }}</span>
                    @endif
                    {{-- в узком блоке остаток — только число дней со знаком («−167»), полностью — в подсказке --}}
                    <span class="badge badge-light-{{ $row['state_color'] }} flex-shrink-0" title="{{ $row['state_label'] }} · {{ $left($row['days']) }}">{{ $dw === 'xs' && $row['days'] !== null ? ($row['days'] < 0 ? '−' . abs($row['days']) : $row['days']) : $left($row['days']) }}</span>
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
        <div class="desk-only-h-lg flex-shrink-0">
            <div class="lr-total fs-8" title="{{ $data['companies'] }} {{ $companies }}">
                <span class="fw-bold text-nowrap">{{ $data['count'] }}</span>
                <span class="desk-muted text-nowrap">{{ $licenses }}</span>
                @if($settings['show_amount'])
                    <span class="fw-bold text-nowrap desk-hide-narrow" title="{{ $total_title }}">{{ $widget::money($data['amount'], $symbol) }}</span>
                @endif
            </div>
        </div>
    </div>
@endif
