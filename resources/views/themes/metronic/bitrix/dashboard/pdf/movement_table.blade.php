{{-- PDF вкладки «Таблица» отчёта «Движение сделок» (patch v51), mPDF: простые таблицы и CSS без flex/grid.
     Данные — DealMovementController::tableData() + mode_label, mode_note, created_at --}}
@php
    $cohort_groups = \App\Modules\Bitrix\Dashboard\Services\DealMovementExportService::COHORT;
    $move_groups = \App\Modules\Bitrix\Dashboard\Services\DealMovementExportService::MOVES;
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Движение сделок {{ $year }}</title>
    <style>
        body { font-family: dejavusans; font-size: 9pt; color: #181c32; }
        h1 { font-size: 15pt; margin: 0 0 2mm 0; }
        h2 { font-size: 11pt; margin: 6mm 0 2mm 0; }
        .meta { color: #5e6278; margin: 0 0 1mm 0; }
        .note { color: #b7791f; font-style: italic; margin: 0 0 1mm 0; }
        .hint { color: #5e6278; font-style: italic; margin: 0 0 2mm 0; }
        table { border-collapse: collapse; }
        th, td { border: 0.2mm solid #d6d9e0; padding: 1.5mm 2.5mm; }
        th { background: #e9ecef; font-weight: bold; text-align: right; }
        th.l, td.l { text-align: left; }
        th.c { text-align: center; }
        td { text-align: right; }
        .muted { color: #7e8299; font-size: 7.5pt; }
        tr.total td { font-weight: bold; background: #f5f8fa; }
    </style>
</head>
<body>

<h1>Движение сделок {{ $year }}</h1>
<div class="meta">Срез: {{ $mode_label }}</div>
@if($mode_note)
    <div class="note">{{ $mode_note }}</div>
@endif
<div class="meta">Сформировано: {{ $created_at }}</div>

{{-- Когорта года --}}
<h2>Новые запросы {{ $year }}</h2>
<table>
    <thead>
        <tr>
            @foreach($cohort_groups as $label)
                <th class="c" style="width: 32mm">{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            @foreach($cohort_groups as $key => $label)
                <td style="text-align: center; font-size: 13pt; font-weight: bold">
                    {{ $cohort[$key] }}
                    @if($key !== 'total')
                        <span class="muted">{{ $cohort[$key . '_pct'] }}%</span>
                    @endif
                </td>
            @endforeach
        </tr>
    </tbody>
</table>

{{-- Движения за год --}}
<h2>Движения за {{ $year }}</h2>
<div class="hint">Сделки, которые в {{ $year }} году перешли в группу (по первому переходу), — включая запросы прошлых лет.</div>
<table>
    <thead>
        <tr>
            <th class="l" style="width: 40mm">Группа</th>
            <th style="width: 25mm">Всего</th>
            <th style="width: 45mm">Из запросов {{ $year }}</th>
            <th style="width: 35mm">Прошлых лет</th>
        </tr>
    </thead>
    <tbody>
        @foreach($move_groups as $key => $label)
            <tr>
                <td class="l">{{ $label }}</td>
                <td><b>{{ $movements[$key]['all'] }}</b></td>
                <td>{{ $movements[$key]['this'] }}</td>
                <td>{{ $movements[$key]['past'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- По менеджерам — только администратору (контроллер не отдаёт строки иначе) --}}
@if($by_manager)
    <h2>По менеджерам</h2>
    <table style="width: 100%">
        <thead>
            <tr>
                <th rowspan="2" class="l">Менеджер</th>
                <th colspan="4" class="c">Новые запросы {{ $year }}</th>
                <th colspan="2" class="c">Движения за {{ $year }}</th>
            </tr>
            <tr>
                <th>Всего</th>
                <th>В работе</th>
                <th>Отвалились</th>
                <th>Закончились</th>
                <th>Отвалились <span class="muted">(прошлых лет)</span></th>
                <th>Закончились <span class="muted">(прошлых лет)</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_merge($managers, ['Итого' => ['cohort' => $cohort, 'movements' => $movements]]) as $name => $row)
                @php($c = $row['cohort'])
                @php($m = $row['movements'])
                <tr @class(['total' => $loop->last])>
                    <td class="l">{{ $name }}</td>
                    <td>{{ $c['total'] ?? 0 }}</td>
                    @foreach(['work', 'lost', 'done'] as $key)
                        <td>
                            {{ $c[$key] ?? 0 }}
                            @if(!empty($c['total']))
                                <span class="muted">{{ $c[$key . '_pct'] }}%</span>
                            @endif
                        </td>
                    @endforeach
                    @foreach(['lost', 'done'] as $key)
                        <td>{{ $m[$key]['all'] ?? 0 }} <span class="muted">({{ $m[$key]['past'] ?? 0 }})</span></td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

</body>
</html>
