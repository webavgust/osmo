{{-- Виджет «Расхождения с Битрикс24» (patch v30): App\Modules\Pub\Desktop\Widgets\Funnel\CrmMismatchWidget

    Поведение по размерам:
    - всегда: число КП с расхождениями;
    - ширина 2 (dw xs): подпись прячется, сумма расхождения («на» и сумма переносятся) — выше 96 px;
    - блок не выше 5 ячеек и шире 2: сводка одной строкой — число, подпись, сумма, виды значками;
    - высота ≥3 (dh md+): + список КП (.desk-fit, не больше настройки «Сколько КП»);
      на ширине 2 в строке номер КП, шире — название и сумма расхождения, от 420 px — вид;
    - высота ≥6 (dh lg|xl): число крупно, виды расхождений строками со счётчиками перед КП;
    - ширина ≥12 (dw lg|xl): сводка и виды колонкой слева, справа таблица КП
      (статус — от 620 px, компания — от 860 px; стили в osmo-desktop-widgets/funnel-1.css).
--}}
@php
    $show_list = !in_array($dh, ['xs', 'sm'], true);
    $types_list = in_array($dh, ['lg', 'xl'], true);
    $tight = $dw !== 'xs' && !$types_list;
    $limit = max(1, (int) $settings['limit']);
    $list = $show_list ? array_slice($data['rows'], 0, min($limit, $rows_max)) : [];
    $side = in_array($dw, ['lg', 'xl'], true) && !empty($list);

    // счётчики по видам: пустые виды не занимают место
    $badges = array_filter($data['issues'], fn($issue) => $issue['count'] > 0);
    $money = $data['money'];
    // сумма расхождения — про вид «Сумма не сходится»: при отборе другого вида она не к месту (как на странице)
    $show_money = $settings['show_money'] && $money['count'] > 0 && in_array($data['issue'], [null, 'amount'], true);
    $label = $data['issue'] ? mb_strtolower($data['issue_label']) : 'расхождений';

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
    $diff = fn($value) => ($value > 0 ? '+' : ($value < 0 ? '−' : '')) . $widget::compact(abs($value));

    // подсказка строки: КП, компания и все причины расхождения — как в списке на странице
    $hint = fn($row) => $row['name']
        . ($row['number'] !== '' ? ' · № ' . $row['number'] : '')
        . ($row['company'] !== '' ? ' · ' . $row['company'] : '')
        . "\n" . implode("\n", $row['reasons']);

    // низкий блок: сумма — только если строка шире 230 px, значки — шире 420 px
    $low = in_array($dh, ['xs', 'sm'], true);
@endphp
@if($data['total'] === 0)
    <div class="desk-empty">
        <i class="fa-light fa-circle-check"></i> Портал и Битрикс24 сходятся
    </div>
@else
    <div @class(['h-100', 'cmm-side' => $side, 'desk-stack' => !$side, 'desk-center' => empty($list)])>
        <div class="cmm-summary">
            @if(!$tight)
                <div class="desk-label desk-nowrap desk-hide-narrow">{{ $label }}</div>
            @endif
            <div @class(['d-flex align-items-baseline flex-wrap gap-2' => $tight])>
                <div @class(['desk-value text-danger', 'desk-value-sm' => $tight && $dh === 'md'])
                     title="Всего КП с расхождениями: {{ $data['total'] }}. Допуск суммы: {{ tools()->cost_normalize($data['tolerance']) }}">{{ $data['shown'] }}</div>
                @if($tight)
                    <span class="desk-label desk-nowrap">{{ $label }}</span>
                @endif
            </div>

            <div @class(['d-flex gap-2 align-items-baseline flex-wrap', 'desk-hide-short' => !$tight, 'mt-1' => $tight && !$low, 'desk-only-w-md' => $tight && $low])>
                @if($show_money)
                    {{-- «на» переносится отдельно: сумма не режется даже в самом узком блоке --}}
                    <span class="desk-muted"
                          title="По {{ $money['count'] }} КП: в Битрикс24 {{ tools()->cost_normalize(round($money['deals_total'])) }}, в КП {{ tools()->cost_normalize(round($money['proposal_total'])) }}. Валюты не пересчитываются">
                        на <span class="text-nowrap">{{ $diff($money['diff']) }}</span>
                    </span>
                @endif
                @if(!$types_list)
                    @foreach($badges as $code => $issue)
                        <span @class(['badge badge-light-' . $issue['color'] . ' fs-8 text-nowrap', 'desk-only-w-md' => !$low, 'desk-only-w-lg' => $low])
                              title="{{ $issue['label'] }}: {{ $issue['hint'] }}">
                            <i class="fa-light {{ $issue['icon'] }} me-1"></i>{{ $issue['count'] }}
                        </span>
                    @endforeach
                @endif
            </div>
        </div>

        @if($side && $types_list && !empty($badges))
            <ul class="desk-list desk-fit cmm-types" data-fit-min="0">
                @foreach($badges as $code => $issue)
                    <li title="{{ $issue['label'] }}: {{ $issue['hint'] }}">
                        <i class="fa-light {{ $issue['icon'] }} text-{{ $issue['color'] }}"></i>
                        <span class="desk-grow">{{ $issue['label'] }}</span>
                        <span class="fw-bold ms-auto">{{ $issue['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if(!empty($list))
            <div class="desk-stack desk-stack-grow cmm-list">
                @if($side)
                    <div class="desk-stack-grow desk-fit" data-fit-items="tbody > tr" data-fit-min="0">
                        <table class="desk-table">
                            <thead>
                            <tr>
                                <th>КП</th>
                                <th class="cmm-company">Компания</th>
                                <th class="desk-only-w-xl">Статус</th>
                                <th class="cmm-issue">Что не так</th>
                                <th class="num">Расхождение</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($list as $row)
                                <tr>
                                    <td class="desk-cut">
                                        <a href="{{ $href($row) }}" class="desk-link d-block text-truncate fw-semibold text-hover-primary"
                                           title="{{ $hint($row) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td class="desk-cut desk-muted cmm-company" title="{{ $row['company'] }}">{{ $row['company'] !== '' ? $row['company'] : '—' }}</td>
                                    <td class="desk-only-w-xl">
                                        <span class="badge badge-light-{{ $row['status_color'] }} fs-8">{{ $row['status_label'] }}</span>
                                    </td>
                                    <td class="desk-cut cmm-issue text-{{ $row['color'] }}" title="{{ implode(', ', $row['labels']) }}">{{ implode(', ', $row['labels']) }}</td>
                                    <td class="num fw-bold text-{{ $row['color'] }}" title="{{ $row['diff'] != 0 ? tools()->cost_normalize(round($row['diff'])) : 'Суммы сходятся' }}">
                                        {{ $row['diff'] == 0 ? '—' : $diff($row['diff']) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <ul class="desk-list desk-stack-grow desk-fit" data-fit-min="0">
                        {{-- высокий блок: сначала виды расхождений со счётчиками, затем КП (подгон прячет с конца) --}}
                        @if($types_list)
                            @foreach($badges as $code => $issue)
                                <li title="{{ $issue['label'] }}: {{ $issue['hint'] }}">
                                    <i class="fa-light {{ $issue['icon'] }} text-{{ $issue['color'] }}"></i>
                                    <span class="desk-grow desk-hide-narrow">{{ $issue['label'] }}</span>
                                    <span class="fw-bold ms-auto">{{ $issue['count'] }}</span>
                                </li>
                            @endforeach
                        @endif
                        @foreach($list as $i => $row)
                            <li @class(['pt-3' => $types_list && $i === 0 && !empty($badges)])>
                                <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary"
                                   title="{{ $hint($row) }}">{{ $dw === 'xs' && $row['number'] !== '' ? $row['number'] : $row['name'] }}</a>
                                <span class="badge badge-light-{{ $row['color'] }} flex-shrink-0 desk-only-w-lg"
                                      title="{{ implode(', ', $row['labels']) }}">{{ $row['labels'][0] ?? '—' }}</span>
                                @if($row['diff'] != 0)
                                    <span class="fw-bold text-nowrap flex-shrink-0 desk-hide-narrow text-{{ $row['color'] }}"
                                          title="{{ tools()->cost_normalize(round($row['diff'])) }}">{{ $diff($row['diff']) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="desk-label desk-nowrap desk-hide-short" data-fit-more="ещё {n}"></div>
            </div>
        @endif
    </div>
@endif
