{{-- Виджет «Обратный отсчёт» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\CountdownWidget --}}
@if($data['days'] === null)
    <div class="desk-empty">
        {{-- дата введена, но не разобрана — подсказать формат, а не «укажите» --}}
        <i class="fa-light fa-hourglass-half"></i> {{ trim((string) $settings['date']) !== '' ? 'Дата не распознана — нужно ДД.ММ.ГГГГ' : 'Укажите дату в настройках' }}
    </div>
@else
    @php
        $value = $data['today'] ? 'сегодня' : (string) $data['days'];

        // ширина крупного числа в em: от неё кегль (слово «сегодня» шире двух цифр)
        $em = $data['today'] ? 4.4 : round(max(1.3, mb_strlen($value) * .62 + .12), 2);

        $progress = $data['progress'] ?? null;
    @endphp
    {{-- раскладку (столбик или ряд) и кегль выбирает personal.css по пропорции блока --}}
    {{-- в подсказке — какой день считается концом и что сегодняшний в число не входит --}}
    <div class="cd-box" style="--cd-em: {{ $em }}" title="{{ $data['hint'] ?? $data['date'] }}">
        <div @class([
                'cd-value',
                'text-primary' => $data['today'],
                'text-danger' => !$data['today'] && $data['warn'],
                'desk-muted' => $data['passed'],
           ])>{{ $value }}</div>

        <div class="cd-side">
            <div class="cd-caption">{{ $data['caption'] }}</div>
            <div class="cd-date desk-muted">{{ $data['date'] }}</div>

            @if($progress !== null)
                <div class="cd-progress">
                    <div class="desk-bar"><i class="{{ $data['warn'] ? 'bg-danger' : 'bg-primary' }}" style="width: {{ $progress }}%"></i></div>
                    <div class="desk-muted">прошло {{ $progress }}% {{ $data['period'] }}</div>
                </div>
            @endif
        </div>
    </div>
@endif
