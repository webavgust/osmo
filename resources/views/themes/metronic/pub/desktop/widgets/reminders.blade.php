{{-- Виджет «Напоминания» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\RemindersWidget --}}
@php
    // строки с запасом: не влезшие целиком спрячет .desk-fit
    $visible = array_slice($data['rows'], 0, $rows_max);

    // места вдвое больше, чем напоминаний, — объект второй строкой под текстом и сводка сверху
    $roomy = $rows >= 2 * max(1, count($visible));

    $overdue = count(array_filter($data['rows'], fn($row) => $row['overdue']));
    $ahead = count($data['rows']) - $overdue;
@endphp
@if(empty($data['rows']))
    {{-- в узком блоке слово шире тела и выталкивает значок — там только значок, текст в title --}}
    <div class="desk-empty" title="Напоминаний нет">
        <i class="fa-light fa-alarm-clock"></i><span class="desk-hide-narrow">Напоминаний нет</span>
    </div>
@else
    <div class="desk-stack">
        @if($roomy)
            {{-- сводка: сколько просрочено и сколько впереди; обёртка — d-flex перебил бы desk-only-h-lg --}}
            <div class="flex-shrink-0 desk-only-h-lg desk-hide-narrow">
                <div class="desk-tiles">
                    <div>
                        <div @class(['desk-value desk-value-sm', 'text-danger' => $overdue > 0])>{{ $overdue }}</div>
                        <div class="desk-label">просрочено</div>
                    </div>
                    <div>
                        <div class="desk-value desk-value-sm">{{ $ahead }}</div>
                        <div class="desk-label">впереди</div>
                    </div>
                </div>
            </div>
        @elseif($overdue > 0)
            <div class="flex-shrink-0 desk-only-h-lg desk-hide-narrow">
                <span class="badge badge-light-danger">просрочено: {{ $overdue }}</span>
            </div>
        @endif

        <ul class="desk-list rem-list desk-stack-grow desk-fit">
            @foreach($visible as $row)
                @php
                    $href = $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
                    $hint = trim($row['text'] . (!empty($row['object']) ? ' — ' . $row['object'] : ''));
                    // дата и час врозь: в узком блоке час переносится под дату, а не режется
                    [$day, $hour] = array_pad(explode(' ', (string) $row['time'], 2), 2, '');
                @endphp
                <li>
                    <span class="bullet bullet-dot flex-shrink-0 desk-hide-narrow {{ $row['overdue'] ? 'bg-danger' : 'bg-primary' }}"></span>
                    <span class="rem-time desk-muted text-nowrap flex-shrink-0 {{ $row['overdue'] ? 'text-danger' : '' }}" title="{{ $row['time'] }} — {{ $hint }}"><span>{{ $day }}</span> <span>{{ $hour }}</span></span>
                    @if($roomy)
                        <div class="desk-grow">
                            <a href="{{ $href }}" class="d-block desk-nowrap text-reset" title="{{ $hint }}">{{ $row['text'] }}</a>
                            @if(!empty($row['object']))
                                <div class="desk-muted desk-nowrap fs-7" title="{{ $hint }}">{{ $row['object'] }}</div>
                            @endif
                        </div>
                    @else
                        <a href="{{ $href }}" class="desk-grow text-reset" title="{{ $hint }}">{{ $row['text'] }}</a>
                        @if(!empty($row['object']))
                            <span class="desk-muted text-truncate desk-only-w-md" style="max-width: 40%;" title="{{ $hint }}">{{ $row['object'] }}</span>
                        @endif
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 flex-shrink-0 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@endif
