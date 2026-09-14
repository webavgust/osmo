{{-- Виджет «Неудачные входы» (patch v30): App\Modules\Pub\Desktop\Widgets\Admin\AuthFailedWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - низкий блок (dh xs|sm) — подпись и счётчик; шире 300 px — рядом порог, «всплеск», логины и IP;
    - узкий блок (dw xs) — подпись короче («за сутки»), время попытки — «ЧЧ:ММ» или дата;
    - блок выше двух ячеек — сверху счётчик, ниже последние попытки ($rows_max в .desk-fit, «ещё N»):
      узкий — список «логин — когда», шире 420 px — + IP;
    - широкий (dw lg|xl) — таблица: логин, когда; шире 420 px — IP, шире 620 px — браузер.
--}}
@php
    $table = in_array($dw, ['lg', 'xl'], true);
    $narrow = $dw === 'xs';
    $with_list = !in_array($dh, ['xs', 'sm'], true);
    $list = $with_list ? array_slice($data['rows'], 0, min((int) $settings['limit'], $rows_max)) : [];
    $color = $data['alert'] ? 'text-danger' : ($data['count'] > 0 ? 'text-warning' : '');
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $hint = fn($row) => trim($row['login'] . ' · ' . ($row['ip'] ?: '—') . ' · ' . ($row['when'] ?: '—')
        . ($row['agent'] ? ' · ' . $row['agent'] : '')
        . ($row['user_id'] ? ' · есть такой пользователь' : ''));
    // коротко: сегодня — время, раньше — день и месяц
    $today = now()->format('d.m.Y');
    $short = fn($row) => empty($row['when']) ? '—' : (substr($row['when'], 0, 10) === $today ? substr($row['when'], 11) : substr($row['when'], 0, 5));
    $label = $narrow ? $data['label'] : 'не вошли ' . $data['label'];
@endphp
@if(empty($list))
    <div class="desk-center">
        <div class="desk-label desk-nowrap" title="Не вошли {{ $data['label'] }}">{{ $label }}</div>
        <div class="af-main">
            <div @class(['desk-value', $color])>{{ $data['count'] }}</div>
            <div class="af-side">
                <span class="desk-muted fs-8 text-nowrap">порог {{ $data['threshold'] }}</span>
                @if($data['alert'])
                    <span class="badge badge-light-danger text-nowrap desk-only-w-md">всплеск</span>
                @endif
                @if($data['count'] > 0)
                    <span class="desk-muted fs-8 text-nowrap desk-only-w-lg" title="Разных логинов и разных адресов за период">логинов: {{ $data['logins'] }}, IP: {{ $data['ips'] }}</span>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="desk-stack">
        <div class="af-head">
            <span @class(['desk-value desk-value-sm', $color])>{{ $data['count'] }}</span>
            <span class="desk-label desk-nowrap" title="Не вошли {{ $data['label'] }}">{{ $label }}</span>
            @if($data['count'] > 0)
                <span class="desk-muted fs-8 text-nowrap desk-only-w-lg" title="Разных логинов и разных адресов за период">логинов: {{ $data['logins'] }}, IP: {{ $data['ips'] }}</span>
            @endif
            @if($data['alert'])
                <span class="badge badge-light-danger text-nowrap desk-only-w-md" title="Порог всплеска: {{ $data['threshold'] }}">всплеск</span>
            @endif
        </div>

        @if($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Логин</th>
                            <th class="desk-only-w-lg">IP</th>
                            <th class="desk-only-w-xl">Браузер</th>
                            <th class="num">Когда</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    @if($row['user_id'])
                                        <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $hint($row) }}">{{ $row['login'] }}</a>
                                    @else
                                        <span class="d-block text-truncate fw-semibold" title="{{ $hint($row) }}">{{ $row['login'] }}</span>
                                    @endif
                                </td>
                                <td class="desk-muted text-nowrap desk-only-w-lg">{{ $row['ip'] }}</td>
                                {{-- браузер коротко («Chrome, Windows», не больше 40 знаков): не режется, сужается логин --}}
                                <td class="desk-muted text-nowrap desk-only-w-xl" title="{{ $row['agent'] }}">{{ $row['agent'] ?: '—' }}</td>
                                <td class="num desk-muted text-nowrap" title="{{ $row['ago'] }}">{{ $row['when'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <i class="fa-light fa-user-lock text-danger flex-shrink-0 desk-hide-narrow"></i>
                        @if($row['user_id'])
                            <a href="{{ $href($row) }}" class="desk-link desk-grow fw-semibold text-hover-primary" title="{{ $hint($row) }}">{{ $row['login'] }}</a>
                        @else
                            <span class="desk-grow fw-semibold" title="{{ $hint($row) }}">{{ $row['login'] }}</span>
                        @endif
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-lg">{{ $row['ip'] }}</span>
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0" title="{{ $row['when'] ?: '—' }} · {{ $row['ago'] ?: '—' }}">{{ $narrow ? $short($row) : ($row['ago'] ?: '—') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@endif
