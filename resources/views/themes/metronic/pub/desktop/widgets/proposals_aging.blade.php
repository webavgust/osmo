{{-- Виджет «Возраст КП» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ProposalsAgingWidget --}}
@php
    $total = (int) $data['total'];
    // крупное число — все КП в работе, в том числе без даты отправки (в корзины они не попадают)
    $in_work = (int) ($data['in_work'] ?? $total);
    $href = $preview || empty($data['url']) ? 'javascript:void(0)' : $data['url'];
    $buckets = $data['buckets'];

    // самая старая корзина — последняя, она же красная
    $oldest_bucket = empty($buckets) ? null : $buckets[count($buckets) - 1];
    $max_count = empty($buckets) ? 0 : max(array_column($buckets, 'count'));

    $percent = fn($value) => rtrim(rtrim(number_format((float) $value, 1, ',', ' '), '0'), ',') . ' %';
    $days_word = fn($value) => \App\Facades\Tools::morph($value, 'день', 'дня', 'дней');
    // короткая подпись для узких мест: «30–60» вместо «30–60 дн.»
    $short = fn($bucket) => str_replace(' дн.', '', $bucket['label']);

    // раскладка по ступеням: низкий — строка с чипами, узкий — столбик чисел; средней высоты —
    // широкий: плитки сбоку, остальные: чипы под числом; высокий узкий — список корзин,
    // высокий от 6 колонок — столбики корзин
    $layout = match (true) {
        in_array($dh, ['xs', 'sm'], true) => 'line',
        $dw === 'xs' => 'column',
        $dh === 'md' && in_array($dw, ['lg', 'xl'], true) => 'tiles',
        $dh === 'md' => 'chips',
        $dw === 'sm' => 'list',
        default => 'bars',
    };
    $side = in_array($layout, ['line', 'tiles'], true);
@endphp
@if($in_work === 0)
    <div class="desk-empty">
        <i class="fa-light fa-hourglass-half"></i> КП в работе нет
    </div>
@else
    <div @class(['desk-stack' => !$side, 'd-flex align-items-center gap-4 h-100 min-w-0' => $side])>
        <div @class(['min-w-0', 'pa-side flex-shrink-0' => $side])>
            <div class="desk-label desk-nowrap" title="КП в работе{{ $data['manager_label'] !== 'Все' ? ' · ' . $data['manager_label'] : '' }}">
                в работе{{ $data['manager_label'] !== 'Все' ? ' · ' . $data['manager_label'] : '' }}
            </div>
            <div @class(['desk-value', 'pa-value-chips' => $layout === 'chips', 'text-danger' => $oldest_bucket && $oldest_bucket['count'] > 0])>{{ $in_work }}</div>

            @if(!in_array($layout, ['line', 'column'], true))
                <div class="d-flex column-gap-2 align-items-baseline flex-wrap desk-hide-short">
                    @if($oldest_bucket && $oldest_bucket['count'] > 0)
                        <span class="text-danger text-nowrap" title="{{ $oldest_bucket['hint'] }}">{{ $oldest_bucket['label'] }}: {{ $oldest_bucket['count'] }}</span>
                    @endif
                    @if($settings['show_oldest'] && $data['oldest'] !== null)
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-md" title="Возраст самого старого КП в работе">самое старое: {{ $data['oldest'] }} {{ $days_word($data['oldest']) }}</span>
                    @endif
                    @if($data['average'] !== null)
                        <span class="desk-muted fs-8 text-nowrap desk-only-w-lg" title="Средний возраст КП в работе">в среднем {{ $data['average'] }} {{ $days_word($data['average']) }}</span>
                    @endif
                    @if($data['no_date'] > 0)
                        <span class="badge badge-light-warning fs-8 text-nowrap desk-only-w-lg" title="У этих КП не указана дата отправки — в корзины они не попали">без даты: {{ $data['no_date'] }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if($layout === 'line')
            {{-- низкий блок: чипы корзин справа, от старых к свежим; не влезшие прячет подгон --}}
            <div class="pa-chips desk-fit d-flex align-items-center gap-2 flex-grow-1" data-fit-axis="x" data-fit-min="0">
                @foreach(array_reverse($buckets) as $bucket)
                    <span class="badge badge-light-{{ $bucket['color'] }} text-nowrap flex-shrink-0" title="{{ $bucket['hint'] }}: {{ $bucket['count'] }}">{{ $bucket['label'] }} · {{ $bucket['count'] }}</span>
                @endforeach
            </div>
        @elseif($layout === 'column')
            {{-- две колонки: числа корзин столбиком, самая старая сверху --}}
            <ul class="desk-list desk-stack-grow desk-fit pa-spread">
                @foreach(array_reverse($buckets) as $bucket)
                    <li title="{{ $bucket['hint'] }}: {{ $bucket['count'] }}">
                        <span class="bullet bullet-dot bg-{{ $bucket['color'] }} w-8px h-8px flex-shrink-0"></span>
                        <span class="fw-bold text-nowrap">{{ $bucket['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        @elseif($layout === 'chips')
            {{-- средняя высота: шкала долей и чипы корзин от старых к свежим, не влезшие прячет подгон --}}
            <div class="d-flex h-6px rounded overflow-hidden bg-light flex-shrink-0">
                @foreach($buckets as $bucket)
                    @if($bucket['count'] > 0)
                        <div class="bg-{{ $bucket['color'] }}" style="width: {{ $bucket['share'] }}%" title="{{ $bucket['label'] }}: {{ $bucket['count'] }}"></div>
                    @endif
                @endforeach
            </div>
            <div class="pa-chips desk-fit d-flex align-items-center gap-2 flex-shrink-0" data-fit-axis="x">
                @foreach(array_reverse($buckets) as $bucket)
                    <span class="badge badge-light-{{ $bucket['color'] }} text-nowrap flex-shrink-0" title="{{ $bucket['hint'] }}: {{ $bucket['count'] }} · {{ $percent($bucket['share']) }}">{{ $bucket['label'] }} · {{ $bucket['count'] }}</span>
                @endforeach
            </div>
        @elseif($layout === 'tiles')
            <div class="pa-tiles d-flex gap-3 flex-grow-1 min-w-0 h-100">
                @foreach($buckets as $bucket)
                    <a href="{{ $href }}" class="desk-link" title="{{ $bucket['hint'] }}: {{ $bucket['count'] }} · {{ $percent($bucket['share']) }}">
                        <span class="desk-label desk-nowrap">{{ $bucket['label'] }}</span>
                        <span class="fw-bold fs-3 text-{{ $bucket['color'] }} text-nowrap">{{ $bucket['count'] }}</span>
                        <span class="desk-bar"><i class="bg-{{ $bucket['color'] }}" style="width: {{ max(0, min(100, $bucket['share'])) }}%;"></i></span>
                        <span class="desk-muted fs-8 text-nowrap">{{ $percent($bucket['share']) }}</span>
                    </a>
                @endforeach
            </div>
        @elseif($layout === 'bars')
            {{-- высокий блок: столбики корзин, высота — от самой полной корзины --}}
            <div class="pa-bars desk-stack-grow d-flex gap-2">
                @foreach($buckets as $bucket)
                    <a href="{{ $href }}" class="pa-col desk-link" title="{{ $bucket['hint'] }}: {{ $bucket['count'] }} · {{ $percent($bucket['share']) }}">
                        <span class="fw-bold text-{{ $bucket['color'] }} text-nowrap">{{ $bucket['count'] }}</span>
                        <span class="pa-track"><i class="bg-{{ $bucket['color'] }}" style="height: {{ $max_count > 0 ? round($bucket['count'] / $max_count * 100, 1) : 0 }}%;"></i></span>
                        <span class="desk-label text-nowrap">{{ $short($bucket) }}</span>
                        <span class="pa-col-share desk-muted fs-8 text-nowrap">{{ $percent($bucket['share']) }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="d-flex h-6px rounded overflow-hidden bg-light desk-hide-short flex-shrink-0">
                @foreach($buckets as $bucket)
                    @if($bucket['count'] > 0)
                        <div class="bg-{{ $bucket['color'] }}" style="width: {{ $bucket['share'] }}%" title="{{ $bucket['label'] }}: {{ $bucket['count'] }}"></div>
                    @endif
                @endforeach
            </div>

            <ul class="desk-list desk-stack-grow desk-fit pa-spread">
                @foreach($buckets as $bucket)
                    <li>
                        <span class="bullet bullet-dot bg-{{ $bucket['color'] }} w-8px h-8px flex-shrink-0" title="{{ $bucket['hint'] }}"></span>
                        <a href="{{ $href }}" class="desk-link desk-grow text-hover-primary" title="{{ $bucket['hint'] }}">{{ $bucket['label'] }}</a>
                        <div class="desk-bar flex-shrink-0 desk-only-w-md" style="width: 4rem;" title="{{ $percent($bucket['share']) }}">
                            <i style="width: {{ max(0, min(100, $bucket['share'])) }}%;"></i>
                        </div>
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-lg">{{ $percent($bucket['share']) }}</span>
                        <span class="badge badge-light-{{ $bucket['color'] }} flex-shrink-0" title="{{ $bucket['hint'] }}">{{ $bucket['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
