{{-- Виджет «График показателя» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\ChartWidget --}}
@php
    $format = function ($value) use ($widget, $data) {
        if ($value === null) return '—';
        if ($data['unit'] === 'money') return $widget::money($value, $data['symbol']);
        if ($data['unit'] === 'percent') return number_format((float) $value, 1, ',', ' ') . ' %';
        return $widget::compact($value);
    };

    $config = [
        'type' => $settings['kind'],
        'colors' => ['primary'],
        'money' => $data['unit'] === 'money',
        'symbol' => $data['symbol'],
        // узкий или низкий блок — без осей и сетки: подписи осей съели бы весь график
        'sparkline' => $dw === 'xs' || in_array($dh, ['xs', 'sm'], true),
        'categories' => array_column($data['rows'], 'label'),
        'series' => [['name' => $data['label'], 'data' => array_map(fn($row) => round($row['value'], 2), $data['rows'])]],
    ];

    // огромный блок (высота ≥10, ширина ≥6) — под графиком разбивка по отрезкам, свежие сверху
    $breakdown = [];
    if ($dh === 'xl' && !in_array($dw, ['xs', 'sm'], true)) {
        $prev = null;
        foreach ($data['rows'] as $row) {
            $row['delta'] = $prev ? round(($row['value'] - $prev) / abs($prev) * 100, 1) : null;
            $prev = $row['value'];
            $breakdown[] = $row;
        }
        $breakdown = array_slice(array_reverse($breakdown), 0, $rows_max);
    }

    // последнее значение: крупнее в высоком блоке, крупно — в огромном и не узком
    $last_class = match (true) {
        $dh === 'xl' && !in_array($dw, ['xs', 'sm'], true) => 'desk-value',
        in_array($dh, ['lg', 'xl'], true) => 'desk-value-sm',
        default => '',
    };
@endphp
@if(empty($data['rows']))
    <div class="desk-empty">
        <i class="fa-light fa-chart-line"></i> Выберите показатель в настройках
    </div>
@else
    <div class="desk-stack">
        {{-- высокий блок — последнее значение крупнее; широкий — ещё итог за все отрезки;
             раскладка шапки в низком и узком блоке — common-1.css --}}
        <div class="chart-head">
            <span class="chart-label desk-label desk-grow" title="{{ $data['label'] }}">{{ $data['label'] }}</span>
            @if($data['total'] !== null)
                <span class="chart-total desk-muted text-nowrap desk-only-w-lg" title="Итог за все отрезки">всего {{ $format($data['total']) }}</span>
            @endif
            <span class="chart-last fw-bold text-nowrap {{ $last_class }}" title="Последний отрезок">{{ $format($data['last']) }}</span>
        </div>

        <div class="desk-stack-grow">
            {!! $widget::chart($config) !!}
        </div>

        @if($breakdown)
            <div class="chart-table desk-fit" data-fit-items="tbody > tr">
                <table class="desk-table">
                    <thead>
                        <tr>
                            <th>Отрезок</th>
                            <th class="num">Значение</th>
                            <th class="num desk-only-w-lg">к пред.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakdown as $row)
                            <tr>
                                <td class="desk-cut" title="{{ $row['title'] ?? $row['label'] }}">{{ $row['title'] ?? $row['label'] }}</td>
                                <td class="num fw-semibold">{{ $format($row['value']) }}</td>
                                <td class="num desk-only-w-lg">
                                    @if($row['delta'] === null)
                                        <span class="desk-muted">—</span>
                                    @else
                                        <span class="desk-delta {{ abs($row['delta']) < 0.5 ? 'flat' : ($row['delta'] > 0 ? 'up' : 'down') }}">{{ $row['delta'] > 0 ? '+' : ($row['delta'] < 0 ? '−' : '') }}{{ number_format(abs($row['delta']), abs($row['delta']) < 10 ? 1 : 0, ',', ' ') }} %</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
