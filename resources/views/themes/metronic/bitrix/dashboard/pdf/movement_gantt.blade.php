{{-- PDF вкладки «Диаграмма» отчёта «Движение сделок» (patch v51), mPDF, A3 альбомная.
     Шкала — таблица: колонка = неделя (или месяц, если период длинный — $step), ячейка залита цветом стадии,
     где сделка была большую часть колонки; подряд идущие одинаковые ячейки — одним td с colspan (быстрее).
     Данные — DealMovementExportService::ganttData() + head, step, buckets, bands, lines, colors --}}
@php
    $n = count($buckets);
    $left = 72;                                        // ширина колонки сделки, мм
    $cell = $n ? round((404 - $left) / $n, 3) : 0;    // ширина колонки шкалы, мм (A3-L 420 мм минус поля)
    $bg = function (?string $stage) use ($colors) {
        if ($stage === null) return '';
        $c = $colors[$stage] ?? ['hex' => ltrim(\App\Modules\Bitrix\Dashboard\Services\DealMovementService::GANTT_COLOR_UNKNOWN, '#'), 'light' => 'D9DBE2', 'pause' => false];
        // пауза (Suspended) — светлее, как бледная штриховка на экране
        return 'background-color:#' . ($c['pause'] ? $c['light'] : $c['hex']) . ';';
    };
    // ячейки строки шкалы → прогоны [stage, span]
    $runs = function (array $cells) {
        $out = [];
        foreach ($cells as $stage) {
            if ($out && $out[count($out) - 1][0] === $stage) $out[count($out) - 1][1]++;
            else $out[] = [$stage, 1];
        }
        return $out;
    };
    $short = fn(string $s, int $len) => mb_strlen($s) > $len ? mb_substr($s, 0, $len - 1) . '…' : $s;
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Движение сделок {{ $year }} — диаграмма</title>
    <style>
        body { font-family: dejavusans; font-size: 7pt; color: #181c32; }
        h1 { font-size: 14pt; margin: 0 0 1.5mm 0; }
        .meta { color: #5e6278; font-size: 8pt; margin: 0 0 0.5mm 0; }
        table.g { border-collapse: collapse; margin-top: 3mm; }
        table.g td { padding: 0; height: 3.6mm; border-bottom: 0.1mm solid #eef0f4; }
        table.g th { background: #e9ecef; font-weight: bold; font-size: 6.5pt; padding: 0.6mm 0; text-align: center;
                     border: 0.1mm solid #d6d9e0; overflow: hidden; }
        table.g th.l { text-align: left; padding-left: 1mm; }
        td.name { padding: 0.3mm 1mm; font-size: 6.5pt; border-right: 0.2mm solid #d6d9e0; }
        td.name .m { color: #7e8299; font-size: 5.5pt; }
        td.stage { padding: 0 1mm 0 4mm; font-size: 6pt; color: #5e6278; border-right: 0.2mm solid #d6d9e0; }
        tr.head td { background: #f5f8fa; font-weight: bold; padding: 0.5mm 1mm; }
        .legend td { padding: 0.6mm 1.5mm; font-size: 7pt; }
        .legend .sw { width: 6mm; height: 3mm; }
    </style>
</head>
<body>

<h1>Движение сделок {{ $year }} — диаграмма</h1>
@foreach($head as $text)
    <div class="meta">{{ $text }}</div>
@endforeach
@if($step === 'month')
    <div class="meta"><b>Шкала по месяцам:</b> период длиннее {{ \App\Modules\Bitrix\Dashboard\Services\DealMovementExportService::PDF_MAX_WEEKS }} недель — колонка = месяц (цвет — стадия большей части месяца).</div>
@else
    <div class="meta">Шкала по неделям: колонка = неделя (номер ISO), цвет — стадия большей части недели.</div>
@endif

<table class="g">
    <thead>
        <tr>
            <th class="l" rowspan="{{ $step === 'week' ? 3 : 2 }}" style="width: {{ $left }}mm">Сделка · менеджер</th>
            @foreach($bands['years'] as $band)
                <th colspan="{{ $band['span'] }}">{{ $band['span'] * $cell >= 8 ? $band['label'] : '' }}</th>
            @endforeach
        </tr>
        @if($step === 'week')
            <tr>
                @foreach($bands['months'] as $band)
                    @php($w = $band['span'] * $cell)
                    <th colspan="{{ $band['span'] }}">{{ $w >= 14 ? $band['label'] : ($w >= 6 ? $band['short'] : '') }}</th>
                @endforeach
            </tr>
        @endif
        <tr>
            {{-- строка с шириной каждой колонки шкалы --}}
            @foreach($buckets as $b)
                <th style="width: {{ $cell }}mm; font-size: {{ $cell >= 4 ? '5pt' : '4pt' }}; font-weight: normal">{{ $cell >= 2.5 ? $b['label'] : '' }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($lines as $line)
            @if($line['type'] === 'head')
                <tr class="head"><td colspan="{{ $n + 1 }}">{{ $line['label'] }} — {{ $line['count'] }}</td></tr>
            @else
                <tr>
                    @if($line['type'] === 'deal')
                        <td class="name">{{ $short($line['deal']['title'], 60) }} <span class="m">· {{ $line['deal']['manager'] }}</span></td>
                    @else
                        <td class="stage">{{ $short($line['stage'], 40) }} — {{ $line['days'] === null ? 'итог' : ($line['days'] < 1 ? '<1 дн.' : $line['days'] . ' дн.') }}</td>
                    @endif
                    @foreach($runs($line['cells']) as [$stage, $span])
                        <td @if($span > 1) colspan="{{ $span }}" @endif style="{{ $bg($stage) }}"></td>
                    @endforeach
                </tr>
            @endif
        @empty
            <tr><td colspan="{{ $n + 1 }}" class="name">Нет сделок под фильтр</td></tr>
        @endforelse
    </tbody>
</table>

{{-- легенда цветов стадий --}}
@if($legend)
    <table class="legend" style="margin-top: 4mm">
        <tr>
            <td colspan="{{ count($legend) * 2 }}" style="font-weight: bold">Стадии</td>
        </tr>
        @foreach(array_chunk($legend, 6) as $chunk)
            <tr>
                @foreach($chunk as $stage)
                    <td class="sw" style="{{ $bg($stage) }}"></td>
                    <td>{{ $stage }}{{ !empty($colors[$stage]['pause']) ? ' (пауза)' : '' }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>
@endif

</body>
</html>
