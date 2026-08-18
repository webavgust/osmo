{{-- Первичная отрисовка результатов поиска сделок (дальше рисует deal_render в JS) --}}
@if($deals->isEmpty())
    <div class="text-center text-muted py-10">Сделки не найдены</div>
@else
    @foreach($deals as $deal)
        @php
            $sub = collect([$deal->company_name, $deal->customer_name, $deal->manager, $deal->stage_name])
                ->filter()->implode(' · ');
            $amount = $deal->opportunity
                ? tools()->cost_normalize(round($deal->opportunity)) . ' ' . $deal->currency_id
                : '';
        @endphp

        <div class="d-flex align-items-center justify-content-between border-bottom py-3 deal-row ps-2 pe-3"
             id="deal_row_{{ $deal->id }}"
             data-id="{{ $deal->id }}"
             data-title="{{ $deal->title }}"
             data-sub="{{ $sub }}"
             data-amount="{{ $amount }}">
            <div class="pe-3 overflow-hidden">
                <div class="fw-semibold text-truncate">
                    <span class="text-muted me-2">#{{ $deal->id }}</span>
                    {{ $deal->title }}
                </div>
                <div class="fs-8 text-muted text-truncate">{{ $sub }}</div>
            </div>

            <div class="d-flex align-items-center flex-shrink-0 gap-3">
                @if($amount)
                    <span class="fw-bold text-nowrap">{{ $amount }}</span>
                @endif

                @if($deal->is_taken)
                    <span class="badge badge-light-warning" title="Уже привязана к другому КП">занята</span>
                @else
                    <button type="button" class="btn btn-sm btn-light-primary" onclick="deal_attach({{ $deal->id }})">
                        <i class="fa-light fa-link fs-6 me-2"></i>Привязать
                    </button>
                @endif
            </div>
        </div>
    @endforeach
@endif
