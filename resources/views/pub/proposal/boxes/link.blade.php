@extends('components.box.box-static-large')

{{-- Связка КП «главное / второстепенное» (patch v33); заголовок — $title из контроллера --}}

@section('body')
    <style>
        .modal-body { overflow-x: hidden }
    </style>
    @php
        // второстепенному показываем его главное, главному — его второстепенные
        $items = $main ? collect([$main]) : $secondaries;
        if ($items instanceof \Illuminate\Database\Eloquent\Collection) {
            $items->loadMissing(['variants', 'currency']);
        } else {
            $items->each(fn($item) => $item->loadMissing(['variants', 'currency']));
        }

        $link_of = fn($item) => $main
            ? $proposal->main_link
            : $proposal->secondary_links->firstWhere('secondary_group', $item->group);
    @endphp

    <div class="text-muted fs-7 mb-5">
        Связанные КП не склеиваются: главное участвует в расчётах, скоринге и аналитике, второстепенное — только
        просмотр и история. Второстепенным может стать только КП без сделок, спецификаций и договоров.
    </div>

    @if($items->isNotEmpty())
        <div class="mb-6">
            <div class="fw-bold mb-2">
                @if($main)
                    Главное КП
                @else
                    Второстепенные КП
                    <span class="badge badge-light ms-2">{{ $items->count() }}</span>
                @endif
            </div>

            @foreach($items as $item)
                @php
                    $ref = \App\Modules\Pub\Proposal\Models\ProposalLink::refOf($item);
                    $link = $link_of($item);
                    // сумма главного варианта — как в колонке «Сумма» списка КП
                    $cost = $item->variants->first()?->cost_total;
                    $amount = $cost
                        ? tools()->cost_normalize($cost) . ' ' . $item->currency?->symbol
                        : '';
                    $sub = collect([
                        $item->sended_at ? 'КП от ' . $item->sended_at->format('d.m.Y') : null,
                        $link?->linked_at ? 'связаны ' . $link->linked_at->format('d.m.Y') : null,
                        $link?->author?->name,
                        $link?->comment,
                    ])->filter()->implode(' · ');

                    if ($main) {
                        // это КП второстепенное: оно станет главным, главное — второстепенным
                        $target = $proposal->group;
                        $main_label = 'Сделать это КП главным';
                        $others = $item->secondary_links->count() - 1;
                        $main_block = empty($main_blockers) ? null : $ref . ' не может стать второстепенным: ' . implode('; ', $main_blockers);
                        $main_title = $main_block ?? ('Это КП станет главным, ' . $ref . ' — второстепенным к нему'
                            . ($others > 0 ? '. Остальные второстепенные ' . $ref . ' (' . $others . ') перейдут к этому КП' : ''));
                        $main_confirm = 'Это КП станет главным, ' . $ref . ' — второстепенным и перестанет участвовать в расчётах. Продолжить?';
                        $detach_title = 'Это КП снова будет участвовать в расчётах';
                        $detach_confirm = 'Разъединить это КП с главным ' . $ref . '? Оно снова будет участвовать в расчётах.';
                    } else {
                        // это КП главное: строка станет главным, это КП — второстепенным
                        $target = $item->group;
                        $main_label = 'Сделать главным';
                        $others = $items->count() - 1;
                        $main_block = empty($blockers) ? null : 'Это КП не может стать второстепенным: ' . implode('; ', $blockers);
                        $main_title = $main_block ?? ($ref . ' станет главным, это КП — второстепенным к нему'
                            . ($others > 0 ? '. Остальные второстепенные (' . $others . ') перейдут к ' . $ref : ''));
                        $main_confirm = $ref . ' станет главным, это КП — второстепенным и перестанет участвовать в расчётах. Продолжить?';
                        $detach_title = $ref . ' снова будет участвовать в расчётах';
                        $detach_confirm = 'Разъединить ' . $ref . ' с этим КП? Оно снова будет участвовать в расчётах.';
                    }
                @endphp

                <div class="bg-light-success rounded p-3 mb-2">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center pe-3 overflow-hidden">
                            <i class="fa-light fa-link fs-2 text-success me-3"></i>
                            <div class="overflow-hidden">
                                <div class="fw-bold text-truncate">
                                    <a href="{{ route('proposal.detail', [$item, $item->iteration]) }}"
                                       class="text-muted text-hover-primary me-2">{{ $item->number ? '№ ' . $item->number : '—' }}</a>
                                    {{ $item->name }}
                                </div>
                                <div class="fs-8 text-muted text-truncate">{{ $sub }}</div>
                            </div>
                        </div>

                        @if($amount)
                            <span class="fw-bold text-nowrap flex-shrink-0">{{ $amount }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-2">
                        {{-- title на обёртке: у неактивной кнопки подсказка не всплывает --}}
                        <span class="d-inline-block" title="{{ $main_title }}">
                            <button type="button" class="btn btn-sm btn-light" @disabled($main_block)
                                    onclick="link_main('{{ $target }}', @js($main_confirm))">
                                <i class="fa-light fa-crown fs-6 me-2"></i>{{ $main_label }}
                            </button>
                        </span>
                        <span class="d-inline-block" title="{{ $detach_title }}">
                            <button type="button" class="btn btn-sm btn-light-danger"
                                    onclick="link_detach('{{ $target }}', @js($detach_confirm))">
                                <i class="fa-light fa-link-slash fs-6 me-2"></i>Разъединить
                            </button>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if(!$main)
        {{-- Поиск пары: второстепенному не показываем — оно ни с кем больше не связывается --}}
        <div class="fw-bold mb-2">Связать с другим КП</div>

        <div class="row g-2 mb-3">
            <div class="col-12 col-lg-7">
                <div class="position-relative">
                    <i class="fa-light fa-magnifying-glass fs-4 position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                    <input type="text" id="link_q" class="form-control form-control-solid ps-12"
                           value="{{ $q }}"
                           autocomplete="off"
                           placeholder="Номер, название, компания" />
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <input type="text" id="link_comment" class="form-control form-control-solid"
                       maxlength="500"
                       autocomplete="off"
                       placeholder="Комментарий к связке" />
            </div>
        </div>

        <div class="text-muted fs-8 mb-3" id="link_hint">
            Пустой поиск — сначала КП той же компании. «Сделать второстепенным» — найденное КП станет
            второстепенным к этому, «Сделать главным» — это КП станет второстепенным к найденному.
        </div>

        <div id="link_results" class="border-1 rounded-1 border-gray-300" style="border-style: solid; max-height: 380px; overflow-y: auto;">
            @include('pub.proposal.boxes.link_rows', ['proposal' => $proposal, 'rows' => $candidates, 'blockers' => $blockers])
        </div>
    @endif
@endsection

@section('footer')
    <button type="button" class="btn btn-light" onclick="javascript:box_close();">Закрыть</button>
@endsection

@section('modal')
<script>
    var link_search_timer = null;

    function link_search() {
        $("#link_results").css("opacity", .4);

        $.ajax({
            url: "{{ route('api.proposal.link_search', $proposal) }}",
            type: "GET",
            data: { q: $("#link_q").val(), _token: csrf_token() },
            dataType: "json",
            success: function (response) {
                $("#link_results").css("opacity", 1).html(response.html);
            },
            error: function () {
                $("#link_results").css("opacity", 1);
                toastr.error("Не получилось выполнить поиск", "Это провал!", {
                    progressBar: true, timeOut: 3000
                });
            }
        });
    }

    /**
     * Действие со связкой. Успех — перезагрузка: связка меняет карточку целиком
     * (плашка второстепенного, запрет правки, расчёты)
     */
    function link_request(url, type, data) {
        body_block();

        $.ajax({
            url: url,
            type: type,
            data: data,
            dataType: "json",
            success: function (response) {
                if (response.result !== 'success') {
                    body_unblock();
                    toastr.error(response.message ?? "Не получилось", "Это провал!", {
                        progressBar: true, timeOut: 6000
                    });
                    return;
                }

                toastr.success(response.message ?? "Готово", "Это успех!", { progressBar: true, timeOut: 1500 });
                box_close();
                setTimeout(function () { location.reload(); }, 600);
            },
            error: function (xhr) {
                body_unblock();
                toastr.error(xhr.responseJSON?.message ?? "Не получилось", "Это провал!", {
                    progressBar: true, timeOut: 4000
                });
            }
        });
    }

    /** Связать с найденным КП: role = main — оно станет второстепенным к этому, secondary — наоборот */
    function link_attach(other, role) {
        var ref = $("#link_row_" + other).data("ref") || "КП";
        var text = role === 'main'
            ? ref + " станет второстепенным к этому КП и перестанет участвовать в расчётах, скоринге и аналитике. Продолжить?"
            : "Это КП станет второстепенным к " + ref + " и перестанет участвовать в расчётах, скоринге и аналитике. Продолжить?";

        if (!confirm(text)) return;

        link_request("{{ route('api.proposal.link_attach', $proposal) }}", "POST", {
            other: other,
            role: role,
            comment: $("#link_comment").val(),
            _token: csrf_token()
        });
    }

    /** Поменять роли: secondary станет главным */
    function link_main(secondary, text) {
        if (!confirm(text)) return;

        link_request("{{ route('api.proposal.link_main', $proposal) }}", "POST", {
            secondary: secondary,
            _token: csrf_token()
        });
    }

    /** Разъединить: secondary снова участвует в расчётах */
    function link_detach(secondary, text) {
        if (!confirm(text)) return;

        link_request("{{ route('api.proposal.link_detach', $proposal) }}?" + $.param({
            secondary: secondary,
            _token: csrf_token()
        }), "DELETE", {});
    }

    $("#link_q").on("keyup", function (e) {
        clearTimeout(link_search_timer);
        link_search_timer = setTimeout(link_search, e.key === "Enter" ? 0 : 350);
    });
</script>
@endsection
