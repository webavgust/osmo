{{-- Виджет «Пользователи: активность» (patch v30): App\Modules\Pub\Desktop\Widgets\Admin\UsersOnlineWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - низкий блок (dh xs|sm) — «в сети» и число; шире 300 px — рядом «из N» и неудачные входы за сутки;
    - блок выше двух ячеек — сверху счётчик, ниже пользователи ($rows_max в .desk-fit, «ещё N»):
      узкий — список «имя — когда был», шире 420 px — + должность;
    - узкий блок (dw xs) — время коротко: «сейчас», «ЧЧ:ММ» (сегодня) или «ДД.ММ»;
    - широкий (dw lg|xl) — таблица: пользователь, был; шире 420 px — последний вход, шире 620 px — должность.
--}}
@php
    $table = in_array($dw, ['lg', 'xl'], true);
    $narrow = $dw === 'xs';
    $with_list = !in_array($dh, ['xs', 'sm'], true);
    $list = $with_list ? array_slice($data['rows'], 0, min((int) $settings['limit'], $rows_max)) : [];
    $failed = $settings['failed'] && $data['failed'] > 0;
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $hint = fn($row) => trim($row['name'] . ($row['position'] ? ' · ' . $row['position'] : '')
        . ' · был на портале: ' . ($row['hit'] ?: 'ни разу')
        . ' · последний вход: ' . ($row['login'] ?: 'не записан'));
    // когда был: в сети — «сейчас»; в узком блоке сегодня — время, раньше — день и месяц
    $today = now()->format('d.m.Y');
    $seen = fn($row) => $row['online'] ? 'сейчас' : (empty($row['hit']) ? '—'
        : ($narrow ? (substr($row['hit'], 0, 10) === $today ? substr($row['hit'], 11) : substr($row['hit'], 0, 5)) : ($row['ago'] ?: '—')));
@endphp
@if(empty($list))
    <div class="desk-center">
        <div class="desk-label desk-nowrap" title="Пользователей в сети">в сети</div>
        <div class="uo-main">
            <div @class(['desk-value', 'text-success' => $data['online'] > 0])>{{ $data['online'] }}</div>
            <div class="uo-side">
                <span class="desk-muted text-nowrap" title="Активных пользователей портала">из {{ $data['total'] }}</span>
                @if($failed)
                    <span class="badge badge-light-danger text-nowrap desk-only-w-md" title="Неудачных попыток входа за последние сутки">входы: {{ $data['failed'] }}</span>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="desk-stack">
        <div class="uo-head">
            <span @class(['desk-value desk-value-sm', 'text-success' => $data['online'] > 0])>{{ $data['online'] }}</span>
            <span class="desk-muted text-nowrap" title="Активных пользователей портала: {{ $data['total'] }}">{{ $narrow ? 'из ' . $data['total'] : 'в сети из ' . $data['total'] }}</span>
            @if($failed)
                <span class="badge badge-light-danger text-nowrap desk-only-w-md" title="Неудачных попыток входа за последние сутки">входы: {{ $data['failed'] }}</span>
            @endif
        </div>

        @if($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th class="desk-only-w-xl">Должность</th>
                            <th class="num desk-only-w-lg">Вход</th>
                            <th class="num">Был</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $hint($row) }}"><span class="bullet bullet-dot bg-{{ $row['online'] ? 'success' : 'gray-400' }} me-2"></span>{{ $row['name'] }}</a>
                                </td>
                                <td class="desk-muted desk-cut desk-only-w-xl" title="{{ $row['position'] }}">{{ $row['position'] ?: '—' }}</td>
                                <td class="num desk-muted text-nowrap desk-only-w-lg">{{ $row['login'] ?: '—' }}</td>
                                <td class="num desk-muted text-nowrap" title="{{ $row['hit'] ?: 'ни разу' }}">{{ $seen($row) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <span class="bullet bullet-dot bg-{{ $row['online'] ? 'success' : 'gray-400' }} flex-shrink-0"></span>
                        <a href="{{ $href($row) }}" class="desk-link desk-grow fw-semibold text-hover-primary" title="{{ $hint($row) }}">{{ $row['name'] }}</a>
                        @if($row['position'])
                            <span class="desk-muted fs-8 desk-nowrap desk-only-w-lg uo-position" title="{{ $row['position'] }}">{{ $row['position'] }}</span>
                        @endif
                        <span @class(['fs-8 text-nowrap flex-shrink-0', 'text-success fw-semibold' => $row['online'], 'desk-muted' => !$row['online']]) title="{{ $row['hit'] ?: 'ни разу' }}">{{ $seen($row) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@endif
