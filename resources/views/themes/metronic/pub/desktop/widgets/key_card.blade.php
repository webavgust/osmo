{{-- Виджет «Карточка ключа» (patch v30): App\Modules\Pub\Desktop\Widgets\Keys\KeyCardWidget --}}
{{--
    Поведение по размерам (ступени, не числа):
    - узко и низко — только остаток дней;
    - низко, но широко — рядом с числом «дн. до …» и состояние;
    - высота ≥ md — спецификация; высота ≥ lg — ключ, договор, срок с полоской прошедшей доли;
    - высокий и не узкий блок — число крупнее (desk-value-lg);
    - высота от 300 px — спецификация, номер ключа и договор переносятся строками, а не режутся (.kc-wrap).
    Компания сверху, число посередине, подробности прижаты книзу — крупный блок не пустует.
--}}
@php
    $days = $data['days'];
    $expired = $days !== null && $days < 0;
    // цвет числа — по состоянию реестра (горизонты из license_horizons): красный и жёлтый, остальное без цвета
    $color = in_array($data['state_color'], ['danger', 'warning'], true) ? 'text-' . $data['state_color'] : '';
    $href = $preview || empty($data['company_url']) ? 'javascript:void(0)' : $data['company_url'];
    $narrow = $dw === 'xs';
    $big = in_array($dh, ['lg', 'xl'], true) && !$narrow;

    // в узком блоке дата короче: 05.10.26
    $date = fn(?string $value) => $value === null ? null : ($narrow ? substr($value, 0, 6) . substr($value, 8) : $value);

    // доля прошедшего срока ключа, %
    $progress = null;
    if (!empty($data['from']) && !empty($data['to'])) {
        try {
            $from = \Carbon\Carbon::createFromFormat('d.m.Y', $data['from'])->startOfDay();
            $to = \Carbon\Carbon::createFromFormat('d.m.Y', $data['to'])->startOfDay();
            $total = max(1, $from->diffInDays($to, false));
            $progress = (int) round(min(100, max(0, $from->diffInDays(now()->startOfDay(), false) / $total * 100)));
        } catch (\Throwable $e) {
            $progress = null;
        }
    }
    $details = $data['contract'] === '' ? '' : (str_starts_with($data['contract'], '№') ? $data['contract'] : '№ ' . $data['contract']);
    $details = trim($details . ($data['partner'] !== '' ? ($details !== '' ? ' · ' : '') . $data['partner'] : ''));
@endphp
@if(empty($data['found']))
    {{-- не выбран — пусто в настройках; не найден — компания без ключей, неверный номер или ключ не активен --}}
    @php
        $why = trim((string) $settings['code']) !== '' ? 'Ключа с таким номером нет' : 'У компании нет ключей';
        $why .= $settings['only_active'] ? ' среди активных' : '';
    @endphp
    <div class="desk-empty" @if(!empty($data['chosen'])) title="{{ $why }}" @endif>
        <i class="fa-light fa-key"></i> {{ empty($data['chosen']) ? 'Ключ не выбран' : 'Ключ не найден' }}
    </div>
@else
    <div class="desk-stack kc">
        <a href="{{ $href }}" class="desk-label desk-link desk-nowrap d-block text-hover-primary kc-company"
           title="{{ $data['company'] }}{{ $data['partner'] ? ' · ' . $data['partner'] : '' }}">{{ $data['company'] }}</a>

        <div class="desk-stack-grow kc-main">
            <div @class([$big ? 'desk-value-lg desk-value' : 'desk-value', $color])
                 title="Ключ {{ $data['code'] }} · до {{ $data['to'] ?? '—' }}">{{ $days === null ? '—' : abs($days) }}</div>

            <div @class(['kc-side', 'fs-8' => $narrow])>
                @if($days === null)
                    <span class="desk-muted text-nowrap">срок не указан</span>
                @elseif($expired)
                    <span class="desk-muted text-nowrap">дн.</span>
                    <span class="desk-muted text-nowrap">как истёк</span>
                @else
                    <span class="desk-muted text-nowrap">дн. до</span>
                    <span class="text-nowrap">{{ $date($data['to']) }}</span>
                @endif
                <span class="badge badge-light-{{ $data['state_color'] }} desk-only-w-md">{{ $data['state_label'] }}</span>
            </div>
        </div>

        <div class="kc-foot">
            @if($data['spec'] !== '')
                <div class="desk-muted fs-8 desk-nowrap desk-only-h-md kc-wrap"
                     title="{{ $data['spec'] }}{{ $data['contract'] ? ' · договор ' . $data['contract'] : '' }}">{{ $data['spec'] }}</div>
            @endif

            @if(!$data['active'])
                <div class="desk-muted fs-8 desk-only-h-md">ключ не активен</div>
            @endif

            <div class="desk-only-h-lg">
                <div class="d-flex gap-2 fs-8 desk-hide-narrow">
                    <span class="desk-muted">Ключ</span>
                    <span class="desk-grow fw-semibold kc-wrap" title="{{ $data['code'] }}">{{ $data['code'] }}</span>
                </div>

                @if($details !== '')
                    <div class="d-flex gap-2 fs-8 desk-hide-narrow">
                        {{-- ключ без спецификации: договора нет, в строке только партнёр компании --}}
                        <span class="desk-muted">{{ $data['contract'] !== '' ? 'Договор' : 'Партнёр' }}</span>
                        <span class="desk-grow kc-wrap" title="{{ $details }}">{{ $details }}</span>
                    </div>
                @endif

                @if($progress !== null)
                    <div class="kc-term fs-8 mt-1">
                        <span class="desk-muted text-nowrap">{{ $date($data['from']) }}</span>
                        <span class="desk-muted text-nowrap">{{ $date($data['to']) }}</span>
                    </div>
                    <div class="desk-bar mt-1" title="Прошло {{ $progress }}% срока">
                        <i class="bg-{{ $data['state_color'] }}" style="width: {{ $progress }}%"></i>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
