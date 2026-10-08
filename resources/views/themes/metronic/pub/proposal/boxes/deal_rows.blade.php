{{--
    Первичная отрисовка результатов поиска сделок (дальше рисует deal_render в JS).

    patch v43: сделка может принадлежать нескольким КП. Другие КП сделки
    ($deal->proposals: [{group, number, name, url}]) — справочная плашка
    «есть КП: …», кнопка «Привязать» есть всегда. Поля is_taken / taken_by
    больше не используются.
--}}
@if($deals->isEmpty())
    <div class="text-center text-muted fs-7 py-10">Сделки не найдены</div>
@else
    @foreach($deals as $deal)
        @php
            $sub = collect([$deal->company_name, $deal->customer_name, $deal->manager, $deal->stage_name])
                ->filter()->implode(' · ');
            $amount = $deal->opportunity
                ? tools()->cost_normalize(round($deal->opportunity)) . ' ' . $deal->currency_id
                : '';
            $others = collect($deal->proposals ?? []);
        @endphp

        <div class="d-flex align-items-center justify-content-between border-bottom py-3 deal-row ps-2 pe-3"
             id="deal_row_{{ $deal->id }}"
             data-id="{{ $deal->id }}"
             data-title="{{ $deal->title }}"
             data-sub="{{ $sub }}"
             data-amount="{{ $amount }}">
            <div class="pe-3 overflow-hidden">
                <div class="fw-semibold fs-7 text-truncate">
                    <span class="text-muted me-2">#{{ $deal->id }}</span>
                    {{ $deal->title }}
                </div>
                <div class="fs-8 text-muted text-truncate">{{ $sub }}</div>
            </div>

            <div class="d-flex align-items-center flex-shrink-0 gap-3">
                @if($others->isNotEmpty())
                    <span class="badge badge-light fs-8 fw-normal text-gray-700 text-nowrap"
                          title="К сделке привязаны и другие КП — это не мешает привязке">
                        есть КП:&nbsp;
                        @foreach($others->take(3) as $other)
                            <a href="{{ $other['url'] }}" target="_blank" class="ms-1"
                               title="{{ $other['name'] }}">{{ $other['number'] ?: 'без номера' }}</a>@if(!$loop->last),@endif
                        @endforeach
                        @if($others->count() > 3)
                            <span class="ms-1">+{{ $others->count() - 3 }}</span>
                        @endif
                    </span>
                @endif

                @if($amount)
                    <span class="fw-bold fs-7 text-nowrap">{{ $amount }}</span>
                @endif

                <button type="button" class="btn btn-sm btn-light-primary text-nowrap" onclick="deal_attach({{ $deal->id }})">
                    <i class="fa-light fa-link fs-6 me-2"></i>Привязать
                </button>
            </div>
        </div>
    @endforeach
@endif
