{{-- Виджет «КП по статусам» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ProposalStatusWidget --}}
@php
    $total = (int) $data['total'];
    $share = fn($count) => $total > 0 ? round($count / $total * 100, 1) : 0;
    // в узком блоке конверсия без десятых: «73 %» влезает в две колонки
    $percent = fn($value, int $digits = 1) => rtrim(rtrim(number_format((float) $value, $digits, ',', ' '), '0'), ',') . ' %';
    $conversion = $percent($data['conversion'], $dw === 'xs' ? 0 : 1);

    $in_work = collect($data['statuses'])->firstWhere('key', 'in_work') ?? $data['statuses'][0];
    // отбор (период, «только мои») в низком блоке не подписан — называем его в подсказках
    $scope = ($data['period_label'] ? ' · отправленные: ' . $data['period_label'] : '')
        . (!empty($settings['mine']) ? ' · только мои' : '');
    $summary = collect($data['statuses'])->map(fn($status) => $status['label'] . ': ' . $status['count'])->implode(' · ')
        . ' · конверсия: ' . $percent($data['conversion']) . $scope;

    // низкий блок (1–2 ячейки) — одна строка; выше — плитки, их раскладку выбирает CSS по пропорции блока
    $line = in_array($dh, ['xs', 'sm'], true);
@endphp
@if($line && $dw === 'xs')
    {{-- две колонки: только главное число — КП в работе --}}
    <div class="desk-center align-items-center text-center" title="{{ $summary }}">
        <div class="d-flex align-items-center gap-1 mw-100">
            <span class="bullet bullet-dot bg-{{ $in_work['color'] }} w-6px h-6px flex-shrink-0"></span>
            <span class="desk-value desk-value-sm">{{ $in_work['count'] }}</span>
        </div>
    </div>
@elseif($line)
    <div class="desk-center">
        <div class="ps-line d-flex align-items-center gap-2 flex-nowrap min-w-0">
            @foreach($data['statuses'] as $status)
                <span class="badge badge-light-{{ $status['color'] }} fs-4 fw-bold text-nowrap flex-shrink-0" title="{{ $status['label'] }}: {{ $status['count'] }}{{ $scope }}">
                    <i class="ps-line-icon fa-light {{ $status['icon'] }} fs-6 me-2"></i><span class="ps-line-label fw-semibold me-2">{{ $status['label'] }}</span>{{ $status['count'] }}
                </span>
            @endforeach
            <span class="ps-line-conv ms-auto text-nowrap flex-shrink-0" title="Выиграно среди решённых (выиграно и проиграно){{ $scope }}">
                <span class="desk-label">конверсия</span>
                <span class="fw-bold fs-4">{{ $conversion }}</span>
            </span>
        </div>
    </div>
@else
    <div class="desk-stack">
        @if($data['period_label'])
            <div class="desk-label desk-nowrap desk-only-h-md" title="Отправленные: {{ $data['period_label'] }}">Отправленные: {{ $data['period_label'] }}</div>
        @endif

        <div class="ps-tiles desk-stack-grow">
            @foreach($data['statuses'] as $status)
                <div class="ps-tile" title="{{ $status['label'] }}: {{ $status['count'] }} · {{ $percent($share($status['count'])) }}">
                    <div class="ps-head desk-label">
                        <span class="bullet bullet-dot bg-{{ $status['color'] }} w-8px h-8px flex-shrink-0"></span>
                        <span class="ps-name desk-nowrap">{{ $status['label'] }}</span>
                    </div>
                    <div class="ps-num desk-value text-{{ $status['color'] }}">{{ $status['count'] }}</div>
                    <div class="ps-sub desk-muted">{{ $percent($share($status['count'])) }}</div>
                </div>
            @endforeach

            <div class="ps-tile" title="Конверсия: выиграно среди решённых (выиграно и проиграно) — {{ $percent($data['conversion']) }}">
                <div class="ps-head desk-label">
                    <i class="fa-light fa-bullseye-arrow text-primary flex-shrink-0"></i>
                    <span class="ps-name desk-nowrap">конверсия</span>
                </div>
                <div class="ps-num desk-value">{{ $conversion }}</div>
                <div class="ps-sub desk-muted">из решённых</div>
            </div>
        </div>

        {{-- шкала долей: видна, когда под плитками есть место --}}
        <div class="desk-split desk-only-h-lg flex-shrink-0">
            @foreach($data['statuses'] as $status)
                @if($status['count'] > 0)
                    <div class="bg-{{ $status['color'] }}" style="width: {{ $share($status['count']) }}%" title="{{ $status['label'] }}: {{ $percent($share($status['count'])) }}"></div>
                @endif
            @endforeach
        </div>
    </div>
@endif
