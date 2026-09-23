{{-- Кандидаты для связки КП (patch v33): первичная отрисовка и ответ живого поиска --}}
@php
    // сумма и валюта в каждой строке — подгружаем разом, без запроса на строку
    if ($rows instanceof \Illuminate\Database\Eloquent\Collection) {
        $rows->loadMissing(['variants', 'currency']);
    }

    $self_secondary = $proposal->is_secondary;
    $self_count = $proposal->secondary_links->count();
    $self_blockers = implode('; ', $blockers ?? []);
@endphp

@if($rows->isEmpty())
    <div class="text-center text-muted py-10">КП не найдены</div>
@else
    @foreach($rows as $row)
        @php
            $ref = \App\Modules\Pub\Proposal\Models\ProposalLink::refOf($row);
            $row_main = $row->is_secondary ? \App\Modules\Pub\Proposal\Models\ProposalLink::refOf($row->main_link?->main) : null;
            $row_count = $row->secondary_links->count();
            $sub = collect([
                collect([$row->company?->name, $row->partner?->name])->filter()->implode(' / '),
                $row->sended_at?->format('d.m.Y'),
            ])->filter()->implode(' · ');
            // сумма главного варианта — как в колонке «Сумма» списка КП
            $cost = $row->variants->first()?->cost_total;
            $amount = $cost
                ? tools()->cost_normalize($cost) . ' ' . $row->currency?->symbol
                : '';

            // «Сделать второстепенным»: строка станет второстепенным к текущему КП
            $to_secondary = match (true) {
                $self_secondary => 'Это КП само второстепенное — связывать можно только главное',
                $row->is_secondary => 'Второстепенное к ' . $row_main . ' — свяжите с ним',
                !empty($row->blockers) => 'Нельзя: ' . implode('; ', $row->blockers),
                default => null,
            };
            $to_secondary_title = $to_secondary
                ?? ($ref . ' станет второстепенным к этому КП'
                    . ($row_count ? '. Его второстепенные (' . $row_count . ') перейдут к этому КП' : ''));

            // «Сделать главным»: текущее КП станет второстепенным к строке
            $to_main = match (true) {
                $self_secondary => 'Это КП само второстепенное — связывать можно только главное',
                $row->is_secondary => 'Второстепенное к ' . $row_main . ' — свяжите с ним',
                $self_blockers !== '' => 'Это КП не может стать второстепенным: ' . $self_blockers,
                default => null,
            };
            $to_main_title = $to_main
                ?? ($ref . ' станет главным, это КП — второстепенным к нему'
                    . ($self_count ? '. Второстепенные этого КП (' . $self_count . ') перейдут к ' . $ref : ''));
        @endphp

        <div class="border-bottom py-3 link-row ps-2 pe-3"
             id="link_row_{{ $row->group }}"
             data-group="{{ $row->group }}"
             data-ref="{{ $ref }}">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div class="overflow-hidden">
                    <div class="fw-semibold text-truncate">
                        <a href="{{ route('proposal.detail', [$row, $row->iteration]) }}" target="_blank"
                           class="text-muted text-hover-primary me-2">{{ $row->number ? '№ ' . $row->number : '—' }}</a>
                        {{ $row->name }}
                        @if($row->is_secondary)
                            <span class="badge badge-light-warning fs-9 ms-2">второстепенное к {{ $row_main }}</span>
                        @elseif($row_count)
                            <span class="badge badge-light-success fs-9 ms-2">главное</span>
                        @endif
                    </div>
                    <div class="fs-8 text-muted text-truncate">{{ $sub }}</div>
                </div>

                <div class="d-flex align-items-center flex-shrink-0 gap-3">
                    <x-proposal.status :proposal="$row"/>
                    @if($amount)
                        <span class="fw-bold text-nowrap">{{ $amount }}</span>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-end gap-2 mt-2">
                {{-- title на обёртке: у неактивной кнопки подсказка не всплывает --}}
                <span class="d-inline-block" title="{{ $to_secondary_title }}">
                    <button type="button" class="btn btn-sm btn-light-primary" @disabled($to_secondary)
                            onclick="link_attach('{{ $row->group }}', 'main')">
                        <i class="fa-light fa-arrow-down-to-line fs-6 me-2"></i>Сделать второстепенным
                    </button>
                </span>
                <span class="d-inline-block" title="{{ $to_main_title }}">
                    <button type="button" class="btn btn-sm btn-light" @disabled($to_main)
                            onclick="link_attach('{{ $row->group }}', 'secondary')">
                        <i class="fa-light fa-crown fs-6 me-2"></i>Сделать главным
                    </button>
                </span>
            </div>
        </div>
    @endforeach
@endif
