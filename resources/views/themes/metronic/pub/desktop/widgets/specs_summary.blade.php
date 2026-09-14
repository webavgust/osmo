{{-- Виджет «Конфигурации, сводная» (patch v30): App\Modules\Pub\Desktop\Widgets\Analytics\SpecsSummaryWidget --}}
@php
    // узкий блок — итоги столбиком (что не влезло, прячет подгон); шире — плитки в ряд (их число
    // растёт с шириной) и пометки; в высоком блоке — список партнёров с полоской, а если список
    // выключен — все итоги плитками
    $narrow = $dw === 'xs';
    $tall = in_array($dh, ['lg', 'xl'], true);
    $list = $settings['partners'] && $tall ? array_slice($data['rows'], 0, $rows_max) : [];
    $grid = $tall && !$settings['partners'];
    $top = max(1, (int) ($data['rows'][0]['specs'] ?? 1));
    $counters = [
        ['спецификаций', $data['specs'], null],
        ['конфигураций', $data['configurations'], 'desk-only-w-md'],
        ['строк состава', $data['scenarios'], 'desk-only-w-lg'],
        ['договоров', $data['contracts'], 'desk-only-w-xl'],
        ['клиентов', $data['companies'], null],
        ['партнёров', $data['partners'], null],
    ];
    $manual_title = 'Строки состава, вписанные руками мимо справочника сценариев';
@endphp
@if(empty($data['specs']))
    <div class="desk-empty">
        <i class="fa-light fa-table-cells-large"></i> Отчёт пуст ({{ $data['mode_label'] }})
    </div>
@elseif($narrow)
    <div class="desk-fit an-column h-100">
        @foreach($counters as [$label, $value])
            <div>
                <div class="desk-label desk-nowrap" title="{{ $label }}">{{ $label }}</div>
                <div class="desk-value desk-value-sm">{{ $value }}</div>
            </div>
        @endforeach
        @if($data['manual'] > 0)
            <div>
                <div class="desk-label desk-nowrap" title="{{ $manual_title }}">мимо справочника</div>
                <div class="desk-value desk-value-sm text-info" title="{{ $manual_title }}">{{ $data['manual'] }}</div>
            </div>
        @endif
    </div>
@else
    <div class="desk-stack">
        @if($grid)
            <div class="desk-tiles desk-stack-grow desk-fit ss-grid">
                @foreach($counters as [$label, $value])
                    <div>
                        <div class="desk-label desk-nowrap" title="{{ $label }}">{{ $label }}</div>
                        <div class="desk-value">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div @class(['desk-stack-grow d-flex flex-column justify-content-center' => !$list])>
                <div class="an-tiles">
                    @foreach(array_slice($counters, 0, 4) as [$label, $value, $hide])
                        <div @class(['an-tile', $hide])>
                            <div class="desk-label desk-nowrap" title="{{ $label }}">{{ $label }}</div>
                            <div class="desk-value desk-value-sm">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="ss-meta desk-only-h-md">
                    <span class="desk-muted">{{ $data['partners'] }} {{ \App\Facades\Tools::morph($data['partners'], 'партнёр', 'партнёра', 'партнёров') }}</span>
                    <span class="desk-muted desk-only-w-md">{{ $data['companies'] }} {{ \App\Facades\Tools::morph($data['companies'], 'клиент', 'клиента', 'клиентов') }}</span>
                    @if($data['manual'] > 0)
                        <span class="text-info desk-only-w-lg" title="{{ $manual_title }}">мимо справочника: {{ $data['manual'] }}</span>
                    @endif
                    <span class="desk-muted desk-only-w-lg">{{ $data['mode_label'] }}</span>
                </div>
            </div>
        @endif

        @if($grid)
            <div class="ss-meta desk-only-h-md">
                @if($data['manual'] > 0)
                    <span class="text-info" title="{{ $manual_title }}">мимо справочника: {{ $data['manual'] }}</span>
                @endif
                <span class="desk-muted desk-only-w-md">{{ $data['mode_label'] }}</span>
            </div>
        @elseif($list)
            <ul class="desk-list desk-stack-grow desk-fit">
                @foreach($list as $row)
                    <li>
                        <span class="desk-grow" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                        <span class="desk-bar desk-only-w-md ss-bar"><i style="width: {{ round($row['specs'] * 100 / $top, 1) }}%"></i></span>
                        <span class="badge badge-light-primary flex-shrink-0"
                              title="{{ $row['specs'] }} {{ \App\Facades\Tools::morph($row['specs'], 'спецификация', 'спецификации', 'спецификаций') }}">{{ $row['specs'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8" data-fit-more="и ещё {n}"></div>
        @elseif($tall && $settings['partners'])
            <div class="desk-muted fs-8">Партнёров в отчёте нет</div>
        @endif
    </div>
@endif
