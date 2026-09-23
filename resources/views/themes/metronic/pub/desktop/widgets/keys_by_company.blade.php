{{-- Виджет «Ключи по компаниям» (patch v30): App\Modules\Pub\Desktop\Widgets\Keys\KeysByCompanyWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - узкий блок — список «название — число ключей»; шире 230 px — + ближайшее окончание,
      шире 420 px — + доля в процентах;
    - широкий блок (dw lg|xl) — таблица: число ключей, доля (шире 420 px), ближайшее окончание (шире 620 px);
    - высокий блок (выше 230 px) — сверху итог: всего ключей крупно, число групп, полоска долей;
    - строки с запасом ($rows_max), не влезшие целиком прячет .desk-fit, подпись «ещё N».
--}}
@php
    $table = in_array($dw, ['lg', 'xl'], true);
    $list = array_slice($data['rows'], 0, max(1, min((int) $settings['limit'], $rows_max)));
    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $left = fn($days) => $days === null ? '' : ($days < 0 ? abs($days) . ' дн. назад' : $days . ' дн.');
    $share = fn($value) => number_format($value, $value < 10 ? 1 : 0, ',', ' ') . ' %';
    $groups_label = ['company' => 'Компаний', 'partner' => 'Партнёров', 'country' => 'Стран'][$settings['by']] ?? 'Групп';
    // полоска долей: первые пять групп, остальное — серым
    $split = array_slice($data['rows'], 0, 5);
    $split_colors = ['primary', 'info', 'success', 'warning', 'danger'];
    $split_rest = max(0, 100 - array_sum(array_column($split, 'share')));
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-building"></i> Ключей нет
    </div>
@else
    <div class="desk-stack">
        <div class="desk-only-h-lg kbc-head">
            <div class="d-flex align-items-baseline flex-wrap column-gap-2">
                <span class="desk-value desk-value-sm">{{ $data['total'] }}</span>
                <span class="desk-muted text-nowrap">{{ \App\Facades\Tools::morph($data['total'], 'ключ', 'ключа', 'ключей') }}</span>
                <span class="desk-muted text-nowrap desk-hide-narrow ms-auto">{{ $groups_label }}: {{ $data['groups'] }}</span>
            </div>
            <div class="desk-split mt-2 desk-hide-narrow">
                @foreach($split as $i => $row)
                    <span class="bg-{{ $split_colors[$i] }}" style="width: {{ $row['share'] }}%;" title="{{ $row['name'] }} · {{ $row['count'] }} · {{ $share($row['share']) }}"></span>
                @endforeach
                @if($split_rest > 0)
                    <span class="bg-gray-400" style="width: {{ $split_rest }}%;" title="Остальные · {{ $share($split_rest) }}"></span>
                @endif
            </div>
        </div>

        @if($table)
            <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>{{ $data['label'] }}</th>
                            <th class="num">Ключей</th>
                            <th class="desk-only-w-lg" style="width: 30%;">Доля</th>
                            <th class="num desk-only-w-xl">Ближайшее окончание</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $row)
                            <tr>
                                <td class="desk-cut">
                                    @if($row['url'] && !$preview)
                                        <a href="{{ $href($row) }}" class="desk-link text-hover-primary d-block text-truncate fw-semibold" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                                    @else
                                        <span class="d-block text-truncate fw-semibold" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                                    @endif
                                </td>
                                <td class="num fw-bold">{{ $row['count'] }}</td>
                                <td class="desk-only-w-lg">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="desk-bar flex-grow-1">
                                            <i style="width: {{ max(0, min(100, $row['share'])) }}%;"></i>
                                        </div>
                                        <span class="desk-muted text-end text-nowrap kbc-share">{{ $share($row['share']) }}</span>
                                    </div>
                                </td>
                                <td class="num desk-muted desk-only-w-xl" title="{{ $left($row['days']) }}">{{ $row['nearest'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    <li>
                        @if($row['url'] && !$preview)
                            <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                        @else
                            <span class="desk-grow" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                        @endif
                        <span class="desk-muted fs-8 text-nowrap text-end kbc-share desk-only-w-lg" title="Доля от всех ключей">{{ $share($row['share']) }}</span>
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-md" title="Ближайшее окончание{{ $row['days'] === null ? '' : ' · ' . $left($row['days']) }}">{{ $row['nearest'] ?? '—' }}</span>
                        <span class="badge badge-light-primary flex-shrink-0" title="{{ $row['count'] }} {{ \App\Facades\Tools::morph($row['count'], 'ключ', 'ключа', 'ключей') }}">{{ $row['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="desk-muted fs-8 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@endif
