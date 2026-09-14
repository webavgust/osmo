{{-- Виджет «Новые партнёры» (patch v30): App\Modules\Pub\Desktop\Widgets\Partner\PartnersNewWidget --}}
@php
    $type = (string) $settings['type'];
    $word = $type === 'company'
        ? \App\Facades\Tools::morph($data['count'], 'компания', 'компании', 'компаний')
        : \App\Facades\Tools::morph($data['count'], 'партнёр', 'партнёра', 'партнёров');

    $delta = $data['previous'] === null ? null : $data['count'] - $data['previous'];
    $direction = match (true) {
        $delta === null, $delta === 0 => 'flat',
        $delta > 0 => 'up',
        default => 'down',
    };
    $delta_sign = ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')) . abs((int) $delta);

    // список — от высоты 3 ячеек; ниже только число. Строк не больше настройки (до 100),
    // лишние спрячет .desk-fit, подпись «ещё N» считает спрятанные честно
    $with_list = in_array($dh, ['md', 'lg', 'xl'], true);
    $list = $data['rows'];

    // число: без списка — крупно по центру; со списком — помельче, а в высоком не узком блоке снова крупно
    $value_class = !$with_list || ($dh === 'xl' && $dw !== 'xs') ? 'desk-value' : 'desk-value-sm fw-bold';

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
@endphp
<div @class(['desk-stack', 'desk-center' => !$with_list])>
    <div>
        {{-- подпись периода со стола — «Текущий квартал» и т.п. --}}
        <div class="desk-label desk-nowrap" title="{{ $data['label'] }}: {{ $data['dates'] }}">новые: {{ mb_strtolower($data['label']) }}</div>
        <div class="d-flex flex-wrap align-items-baseline column-gap-2">
            <span class="{{ $value_class }} text-nowrap flex-shrink-0" title="{{ $data['dates'] }}">{{ $data['count'] }}</span>

            @if($type === 'both')
                {{-- разбивка значками: в узкой колонке слова не влезают --}}
                <span class="desk-muted text-nowrap desk-hide-short" title="Партнёров за {{ $data['dates'] }}: {{ $data['partners'] }}">
                    <i class="fa-light fa-handshake-simple fs-8"></i> {{ $data['partners'] }}
                </span>
                <span class="desk-muted text-nowrap desk-hide-short" title="Компаний за {{ $data['dates'] }}: {{ $data['companies'] }}">
                    <i class="fa-light fa-building fs-8"></i> {{ $data['companies'] }}
                </span>
            @else
                <span class="desk-muted text-nowrap desk-hide-short desk-hide-narrow">{{ $word }}</span>
            @endif

            @if($settings['compare'] && $delta !== null)
                <span class="desk-delta {{ $direction }} desk-hide-short" title="Прошлый отрезок: {{ $data['previous'] }}">
                    {{ $delta_sign }}<span class="desk-only-w-md"> к прошлому отрезку</span>
                </span>
            @endif
        </div>
    </div>

    @if($with_list)
        @if(empty($list))
            <div class="desk-stack-grow desk-muted fs-8" title="{{ $data['dates'] }}">За период никто не появился</div>
        @else
            <ul class="desk-list desk-stack-grow desk-fit" data-fit-min="0">
                @foreach($list as $row)
                    <li>
                        <i @class(['fa-light', 'flex-shrink-0', 'desk-hide-narrow', 'fa-building' => $row['type'] === 'company', 'fa-handshake-simple' => $row['type'] !== 'company'])
                           title="{{ $row['type'] === 'company' ? 'Компания' : 'Партнёр' }}"></i>

                        <a href="{{ $href($row) }}" class="desk-link desk-grow text-hover-primary"
                           title="{{ $row['name'] }}{{ $row['note'] !== '' ? ' · ' . $row['note'] : '' }}">{{ $row['name'] }}</a>

                        @if($row['note'] !== '')
                            <span class="desk-muted fs-8 text-nowrap text-truncate desk-only-w-xl" style="max-width: 9rem;"
                                  title="{{ $row['type'] === 'company' ? 'Партнёр компании: ' : 'Грейд: ' }}{{ $row['note'] }}">{{ $row['note'] }}</span>
                        @endif

                        @if($settings['author'])
                            <span class="desk-muted fs-8 text-nowrap text-truncate desk-only-w-lg" style="max-width: 9rem;"
                                  title="{{ $row['author'] !== '' ? 'Создал ' . $row['author'] : 'Автор неизвестен: запись старше журнала изменений' }}">
                                {{ $row['author'] !== '' ? $row['author'] : '—' }}
                            </span>
                        @endif

                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md">{{ $row['date'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="desk-muted fs-8 desk-hide-short desk-fit-out" data-fit-more="ещё {n}"></div>
        @endif
    @endif
</div>
