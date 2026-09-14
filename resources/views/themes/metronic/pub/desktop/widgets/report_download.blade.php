{{-- Виджет «Выгрузка отчёта» (patch v30): App\Modules\Pub\Desktop\Widgets\Analytics\ReportDownloadWidget --}}
@php
    // кнопки — плитки в сетке: она сама кладёт их в ряд или столбиком, не влезшие прячет подгон;
    // что видно в плитке (значок, подпись, дата) решают контейнерные запросы по размеру самой плитки
    $list = $data['rows'];
@endphp
@if(empty($list))
    <div class="desk-empty">
        <i class="fa-light fa-file-excel"></i> Выберите отчёт в настройках
    </div>
@else
    <div class="desk-fit rd-grid">
        @foreach($list as $row)
            <a @class(['btn rd-card', 'btn-' . $data['color']])
               href="{{ $preview ? 'javascript:void(0)' : $row['url'] }}"
               title="{{ $row['label'] }} — выгрузка в Excel{{ $row['date'] ? ', последняя ' . $row['date'] : '' }}">
                <span class="rd-inner">
                    <span class="rd-main">
                        <i class="fa-light {{ $row['icon'] }} rd-icon"></i>
                        <span class="rd-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    </span>
                    @if($row['date'])
                        <span class="rd-date" title="Последняя выгрузка: {{ $row['date'] }}">
                            <span class="rd-full">{{ $row['date'] }}</span>
                            <span class="rd-short">{{ substr($row['date'], 0, 5) }}</span>
                        </span>
                    @elseif($settings['date'])
                        <span class="rd-date" title="Этот отчёт ещё не выгружали">
                            <span class="rd-full">ещё не выгружали</span>
                            <span class="rd-short">—</span>
                        </span>
                    @endif
                </span>
            </a>
        @endforeach
    </div>
@endif
