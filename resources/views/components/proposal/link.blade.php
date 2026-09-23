@php
    /**
     * Связка КП «главное / второстепенное» (patch v33).
     *
     * <x-proposal.link :proposal="$proposal" />
     * <x-proposal.link :proposal="$proposal" as="btn" />
     * <x-proposal.link :proposal="$proposal" as="btn" editable="0" />
     *
     * Второстепенное — жёлтая кнопка «Второстепенное → № AA793», главное — голубая
     * «Главное · № AK760» (несколько второстепенных — «Главное · +N»), клик — попап связки.
     * Связки нет — ничего не выводим: связать можно из меню «⋮» карточки.
     * Связка — всегда текущее состояние, в том числе при просмотре состояния на дату.
     *
     * as="btn" — вид кнопки, чтобы совпадать по высоте с соседними кнопками.
     * editable="0" — не ссылка на попап, а просто плашка (режим «состояние на дату»).
     */
    $service = \App\Modules\Pub\Proposal\Services\ProposalLinkService::class;
    $ref = fn($item) => \App\Modules\Pub\Proposal\Models\ProposalLink::refOf($item);

    // «№ AA793 «название»»; без номера refOf() уже даёт название
    $caption = fn($item) => $ref($item) . (trim((string) $item->number) !== '' && filled($item->name) ? ' «' . $item->name . '»' : '');

    $as_btn = ($as ?? '') === 'btn';
    $editable = !isset($editable) || !empty($editable);

    $label = null;
    if (!empty($proposal->main_link)) {
        // второстепенное: главное — последняя редакция своей группы
        $main = $service::mainOf($proposal);
        $color = 'warning';
        $label = 'Второстепенное → ' . ($main ? $ref($main) : '?');
        $title = ($main ? 'Главное КП — ' . $caption($main) : 'Главное КП не найдено') . '. Это КП в расчётах не участвует';
    } else {
        $secondaries = $service::secondariesOf($proposal);
        if ($secondaries->isNotEmpty()) {
            $color = 'info';
            $label = 'Главное · ' . ($secondaries->count() === 1 ? $ref($secondaries->first()) : '+' . $secondaries->count());
            $title = 'Второстепенные КП: ' . $secondaries->map($caption)->implode(', ');
        }
    }

    if ($label !== null) {
        $class = $as_btn ? 'btn btn-sm btn-light-' . $color : 'badge badge-light-' . $color;
    }
@endphp

@if($label !== null)
    @if($editable)
        <a href="javascript:box({href: '{{ route('proposal.box_link', [$proposal, $proposal->iteration]) }}'})"
           class="{{ $class }} d-inline-flex align-items-center text-decoration-none text-nowrap"
           title="{{ $title }}">
            <i class="fas fa-link fs-7 me-2"></i>
            {{ $label }}
        </a>
    @else
        <span class="{{ $class }} d-inline-flex align-items-center text-nowrap" title="{{ $title }}">
            <i class="fas fa-link fs-7 me-2"></i>
            {{ $label }}
        </span>
    @endif
@endif
