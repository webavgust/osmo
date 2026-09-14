{{-- Виджет «Константы портала» (patch v30): App\Modules\Pub\Desktop\Widgets\Admin\ConstsWatchWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - узкий блок (уже 170 px) — строка переносится: название сверху, значение под ним;
    - список «название — значение»: числа и короткие значения не режутся, длинные (JSON, адреса)
      режутся многоточием с полным значением в подсказке; шире 420 px — + примечание (если включено);
    - широкий блок (dw lg|xl) — таблица «константа — значение», шире 620 px — + примечание;
    - строки с запасом ($rows_max), не влезшие прячет .desk-fit, подпись «ещё N».
--}}
@php
    $table = in_array($dw, ['lg', 'xl'], true);
    $narrow = $dw === 'xs';
    $list = array_slice($data['rows'], 0, max(1, $rows_max));
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $hint = fn($row) => trim($row['name'] . ' · ' . $row['key'] . ' = ' . $row['full']
        . ($row['note'] ? ' · ' . $row['note'] : '')
        . ($row['system'] ? ' · системная' : ''));
    // значение целиком: число или короткая строка; в узком блоке — только короткое число
    $whole = fn($value) => $narrow
        ? is_numeric($value) && mb_strlen($value) <= 8
        : is_numeric($value) || mb_strlen($value) <= 16;
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-sliders"></i> Константы не выбраны
    </div>
@elseif($table)
    <div class="desk-stack">
        <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
            <table class="desk-table">
                <thead>
                    <tr>
                        <th>Константа</th>
                        <th>Значение</th>
                        @if($settings['note'])
                            <th class="desk-only-w-xl">Примечание</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $row)
                        <tr>
                            <td class="desk-cut">
                                <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $hint($row) }}">{{ $row['name'] }}</a>
                            </td>
                            <td @class(['fw-semibold', 'text-nowrap' => $whole($row['value']), 'desk-cut cw-value' => !$whole($row['value'])]) title="{{ $row['full'] }}">{{ $row['value'] !== '' ? $row['value'] : '—' }}</td>
                            @if($settings['note'])
                                <td class="desk-muted desk-cut desk-only-w-xl" title="{{ $row['note'] }}">{{ $row['note'] ?: '—' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@else
    <div class="desk-stack">
        <ul class="desk-list desk-stack-grow desk-fit cw-list">
            @foreach($list as $row)
                <li>
                    <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary cw-name" title="{{ $hint($row) }}">{{ $row['name'] }}</a>
                    @if($settings['note'] && $row['note'])
                        <span class="desk-muted fs-8 desk-nowrap desk-only-w-lg cw-note" title="{{ $row['note'] }}">{{ $row['note'] }}</span>
                    @endif
                    @if($whole($row['value']))
                        <span class="fw-semibold text-nowrap flex-shrink-0" title="{{ $row['full'] }}">{{ $row['value'] !== '' ? $row['value'] : '—' }}</span>
                    @else
                        <span class="fw-semibold desk-nowrap text-end cw-value" title="{{ $row['full'] }}">{{ $row['value'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@endif
