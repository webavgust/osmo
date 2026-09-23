{{-- Виджет «Часы» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\ClockWidget --}}
@php
    $list = array_slice($data['rows'], 0, 4);

    // ширина строки времени в em: от неё кегль (12-часовой формат «02:35 PM» длиннее)
    $em = $settings['format'] === '12' ? 4.8 : 2.8;

    // строк под временем: город всегда, дата — по настройке
    $lines = $settings['date'] ? 2 : 1;

    // в низком блоке часы стоят в ряд — лишние прячутся по ширине, в остальных — по высоте
    $axis = in_array($dh, ['xs', 'sm'], true) ? 'x' : 'y';
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-clock"></i> Выберите часовой пояс
    </div>
@else
    {{-- кегль считает personal.css от площади блока и числа часов (--clock-n) --}}
    <div @class(['clock-grid', 'desk-fit', 'clock-n-' . count($list), 'clock-single' => count($list) === 1, 'clock-h12' => $settings['format'] === '12'])
         data-fit-axis="{{ $axis }}"
         style="--clock-n: {{ count($list) }}; --clock-em: {{ $em }}; --clock-lines: {{ $lines }}">
        @foreach($list as $row)
            <div class="clock-tile" title="{{ $row['city'] }}{{ $row['shift'] !== '' ? ' · ' . $row['shift'] : '' }} · {{ $row['date'] }}">
                {{-- в узле только время: его текст раз в секунду переписывает tickClocks --}}
                <div class="clock-time" data-desk-clock="{{ $row['zone'] }}" data-desk-clock-format="{{ $settings['format'] }}">{{ $row['time'] }}</div>
                {{-- город режется многоточием, сдвиг пояса — никогда --}}
                <div class="clock-city">
                    <span class="desk-nowrap">{{ $row['city'] }}</span>
                    @if($row['shift'] !== '')
                        <span class="clock-shift desk-muted">{{ $row['shift'] }}</span>
                    @endif
                </div>
                @if($settings['date'])
                    <div class="clock-date desk-muted desk-nowrap">{{ $row['date'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif
