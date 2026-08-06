@extends('components.box.box-static-large')

@section('title')
    Сделки Битрикс24
    <span class="text-muted fs-7 ms-2">КП «{{ $proposal->name }}»</span>
@endsection

@section('body')
    {{-- Привязанные сделки --}}
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold">
                Привязанные сделки
                <span class="badge badge-light ms-2" id="deal_links_count">{{ $links->count() }}</span>
            </span>
            <span class="text-muted fs-8">
                Сделок может быть несколько. Главная попадает в колонку списка КП и в выгрузки.
            </span>
        </div>

        <div id="deal_links">
            @foreach($links as $link)
                <div class="deal-link d-flex align-items-center justify-content-between bg-light-success rounded p-3 mb-2"
                     data-id="{{ $link->crm_deal_id }}">
                    <div class="d-flex align-items-center pe-3 overflow-hidden">
                        <i class="fa-light fa-link fs-2 text-success me-3"></i>
                        <div class="overflow-hidden">
                            <div class="fw-bold text-truncate">
                                <span class="text-muted me-2">#{{ $link->crm_deal_id }}</span>
                                {{ $link->deal?->title ?: 'Сделка не найдена в выгрузке Битрикса' }}
                                <span class="badge badge-success fs-9 ms-2 deal-main-badge @if(!$link->is_main) d-none @endif">главная</span>
                            </div>
                            <div class="fs-8 text-muted text-truncate">
                                {{ collect([
                                    $link->deal?->company_name,
                                    $link->deal?->stage_name,
                                    $link->linked_at ? 'привязана ' . $link->linked_at->format('d.m.Y') : null,
                                ])->filter()->implode(' · ') }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-shrink-0 gap-2">
                        @if($link->deal?->opportunity)
                            <span class="fw-bold text-nowrap me-2">
                                {{ tools()->cost_normalize(round($link->deal->opportunity)) }} {{ $link->deal->currency_id }}
                            </span>
                        @endif

                        <button type="button"
                                class="btn btn-sm btn-light deal-main-btn @if($link->is_main) d-none @endif"
                                onclick="deal_main({{ $link->crm_deal_id }})"
                                title="Сделать главной">
                            <i class="fa-light fa-star fs-6"></i>
                        </button>

                        <button type="button" class="btn btn-sm btn-light-danger"
                                onclick="deal_detach({{ $link->crm_deal_id }})"
                                title="Отвязать">
                            <i class="fa-light fa-link-slash fs-6"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="deal_links_empty" class="text-muted fs-7 @if($links->isNotEmpty()) d-none @endif">
            Сделок пока нет. Найдите нужные ниже — можно привязать несколько.
        </div>
    </div>

    {{-- Поиск --}}
    <div class="row g-2 mb-3">
        <div class="col-12 col-lg-6">
            <div class="position-relative">
                <i class="fa-light fa-magnifying-glass fs-4 position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                <input type="text" id="deal_q" class="form-control form-control-solid ps-12"
                       value="{{ $params['q'] }}"
                       autocomplete="off"
                       placeholder="Название, ID сделки, компания, заказчик" />
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <select id="deal_manager" class="form-select form-select-solid">
                <option value="">Все менеджеры</option>
                @foreach($managers as $manager)
                    <option value="{{ $manager }}" @selected($params['manager'] === $manager)>
                        {{ trim(\Illuminate\Support\Str::afterLast($manager, ']')) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-lg-3">
            <select id="deal_stage" class="form-select form-select-solid">
                <option value="">Все стадии</option>
                @foreach($stages as $stage)
                    <option value="{{ $stage }}" @selected($params['stage'] === $stage)>{{ $stage }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <label class="form-check form-check-custom form-check-solid flex-shrink-0"
               title="Скрыть сделки, уже привязанные к другим КП">
            <input class="form-check-input" type="checkbox" id="deal_only_free"
                   @checked($params['only_free']) />
            <span class="form-check-label fs-7 text-nowrap">Только свободные сделки</span>
        </label>

        <div class="text-muted fs-8 text-end" id="deal_hint">
            Поиск подставил название компании из КП. Одна сделка принадлежит одному КП —
            занятые сделки привязать нельзя.
        </div>
    </div>

    {{-- Результаты --}}
    <div id="deal_results" style="max-height: 380px; overflow-y: auto;">
        @include('pub.proposal.boxes.deal_rows', ['deals' => $deals])
    </div>
@endsection

@section('footer')
    <button type="button" class="btn btn-light" onclick="javascript:deal_done();">Закрыть</button>
@endsection

@section('modal')
<script>
    var deal_search_timer = null;
    var deal_dirty = false;

    function deal_search() {
        $("#deal_results").css("opacity", .4);

        $.ajax({
            url: "{{ route('api.proposal.deal_search', $proposal) }}",
            type: "GET",
            data: {
                q: $("#deal_q").val(),
                manager: $("#deal_manager").val(),
                stage: $("#deal_stage").val(),
                only_free: $("#deal_only_free").prop("checked") ? 1 : 0,
                _token: csrf_token()
            },
            dataType: "json",
            success: function (response) {
                $("#deal_results").css("opacity", 1);
                deal_render(response.rows);
                $("#deal_hint").html(
                    response.count > 0
                        ? "Найдено: " + response.count + ". Показаны первые совпадения — уточните запрос, если нужной сделки нет."
                        : "Ничего не найдено. Снимите фильтры или измените запрос."
                );
            },
            error: function () {
                $("#deal_results").css("opacity", 1);
                toastr.error("Не получилось выполнить поиск", "Это провал!", {
                    progressBar: true, timeOut: 3000
                });
            }
        });
    }

    function deal_render(rows) {
        if (!rows || !rows.length) {
            $("#deal_results").html(
                '<div class="text-center text-muted py-10">Сделки не найдены</div>'
            );
            return;
        }

        var html = '';
        rows.forEach(function (row) {
            var amount = row.amount
                ? cost_normalize(Math.round(row.amount)) + ' ' + (row.currency ?? '')
                : '';
            var sub = [row.company, row.customer, row.manager, row.stage].filter(Boolean).join(' · ');

            html += '<div class="d-flex align-items-center justify-content-between border-bottom py-3 deal-row"'
                +     ' id="deal_row_' + row.id + '"'
                +     ' data-id="' + row.id + '"'
                +     ' data-title="' + $('<span>').text(row.title ?? '').html() + '"'
                +     ' data-sub="' + $('<span>').text(sub).html() + '"'
                +     ' data-amount="' + amount + '">'
                +   '<div class="pe-3 overflow-hidden">'
                +     '<div class="fw-semibold text-truncate">'
                +       '<span class="text-muted me-2">#' + row.id + '</span>'
                +       $('<span>').text(row.title ?? '').html()
                +     '</div>'
                +     '<div class="fs-8 text-muted text-truncate">' + $('<span>').text(sub).html() + '</div>'
                +   '</div>'
                +   '<div class="d-flex align-items-center flex-shrink-0 gap-3">'
                +     (amount ? '<span class="fw-bold text-nowrap">' + amount + '</span>' : '')
                +     (row.is_taken
                        ? '<span class="badge badge-light-warning" title="Уже привязана к другому КП">занята</span>'
                        : '<button type="button" class="btn btn-sm btn-light-primary" onclick="deal_attach(' + row.id + ')">'
                          + '<i class="fa-light fa-link fs-6 me-2"></i>Привязать</button>')
                +   '</div>'
                + '</div>';
        });

        $("#deal_results").html(html);
    }

    /** Строка привязанной сделки — рисуем из данных найденной строки */
    function deal_link_html(id, title, sub, amount) {
        return '<div class="deal-link d-flex align-items-center justify-content-between bg-light-success rounded p-3 mb-2" data-id="' + id + '">'
            +   '<div class="d-flex align-items-center pe-3 overflow-hidden">'
            +     '<i class="fa-light fa-link fs-2 text-success me-3"></i>'
            +     '<div class="overflow-hidden">'
            +       '<div class="fw-bold text-truncate"><span class="text-muted me-2">#' + id + '</span>' + title
            +         '<span class="badge badge-success fs-9 ms-2 deal-main-badge d-none">главная</span></div>'
            +       '<div class="fs-8 text-muted text-truncate">' + sub + '</div>'
            +     '</div>'
            +   '</div>'
            +   '<div class="d-flex align-items-center flex-shrink-0 gap-2">'
            +     (amount ? '<span class="fw-bold text-nowrap me-2">' + amount + '</span>' : '')
            +     '<button type="button" class="btn btn-sm btn-light deal-main-btn" onclick="deal_main(' + id + ')" title="Сделать главной">'
            +       '<i class="fa-light fa-star fs-6"></i></button>'
            +     '<button type="button" class="btn btn-sm btn-light-danger" onclick="deal_detach(' + id + ')" title="Отвязать">'
            +       '<i class="fa-light fa-link-slash fs-6"></i></button>'
            +   '</div>'
            + '</div>';
    }

    function deal_links_refresh() {
        var count = $("#deal_links .deal-link").length;
        $("#deal_links_count").text(count);
        $("#deal_links_empty").toggleClass("d-none", count > 0);

        // главная должна быть ровно одна: если её нет — первая в списке
        if (count > 0 && $("#deal_links .deal-main-badge:not(.d-none)").length === 0) {
            deal_main_mark($("#deal_links .deal-link").first().data("id"));
        }
    }

    function deal_main_mark(id) {
        $("#deal_links .deal-link").each(function () {
            var is_main = String($(this).data("id")) === String(id);
            $(this).find(".deal-main-badge").toggleClass("d-none", !is_main);
            $(this).find(".deal-main-btn").toggleClass("d-none", is_main);
        });
    }

    function deal_attach(deal_id, main) {
        var row = $("#deal_row_" + deal_id);
        body_block();

        $.ajax({
            url: "{{ route('api.proposal.deal_attach', [$proposal, $proposal->iteration]) }}",
            type: "POST",
            data: { deal_id: deal_id, main: main ? 1 : 0, _token: csrf_token() },
            dataType: "json",
            success: function (response) {
                body_unblock();

                if (response.result !== 'success') {
                    toastr.error(response.message ?? "Не получилось привязать", "Это провал!", {
                        progressBar: true, timeOut: 4000
                    });
                    return;
                }

                deal_dirty = true;

                if (row.length && !$("#deal_links .deal-link[data-id='" + deal_id + "']").length) {
                    $("#deal_links").append(deal_link_html(
                        deal_id, row.data("title"), row.data("sub"), row.data("amount")
                    ));
                    row.remove();
                }

                if (main) deal_main_mark(deal_id);
                deal_links_refresh();

                toastr.success("Сделка привязана", "Это успех!", { progressBar: true, timeOut: 1500 });
            },
            error: function () {
                body_unblock();
                toastr.error("Не получилось привязать", "Это провал!", {
                    progressBar: true, timeOut: 3000
                });
            }
        });
    }

    function deal_main(deal_id) {
        body_block();

        $.ajax({
            url: "{{ route('api.proposal.deal_attach', [$proposal, $proposal->iteration]) }}",
            type: "POST",
            data: { deal_id: deal_id, main: 1, _token: csrf_token() },
            dataType: "json",
            success: function (response) {
                body_unblock();

                if (response.result !== 'success') {
                    toastr.error(response.message ?? "Не получилось", "Это провал!", {
                        progressBar: true, timeOut: 4000
                    });
                    return;
                }

                deal_dirty = true;
                deal_main_mark(deal_id);
                toastr.success("Главная сделка изменена", "Готово", { progressBar: true, timeOut: 1500 });
            },
            error: function () {
                body_unblock();
                toastr.error("Не получилось", "Это провал!", { progressBar: true, timeOut: 3000 });
            }
        });
    }

    function deal_detach(deal_id) {
        if (!confirm("Отвязать сделку #" + deal_id + " от этого КП?")) return;

        body_block();

        $.ajax({
            url: "{{ route('api.proposal.deal_detach', [$proposal, $proposal->iteration]) }}?_token=" + csrf_token()
                + "&deal_id=" + deal_id,
            type: "DELETE",
            dataType: "json",
            success: function () {
                body_unblock();
                deal_dirty = true;
                $("#deal_links .deal-link[data-id='" + deal_id + "']").remove();
                deal_links_refresh();
                toastr.success("Сделка отвязана", "Готово", { progressBar: true, timeOut: 1500 });
            },
            error: function () {
                body_unblock();
                toastr.error("Не получилось отвязать", "Это провал!", {
                    progressBar: true, timeOut: 3000
                });
            }
        });
    }

    /** Закрытие: если что-то менялось — обновляем страницу под попапом */
    function deal_done() {
        box_close();
        if (deal_dirty) setTimeout(function () { location.reload(); }, 300);
    }

    $("#deal_q").on("keyup", function () {
        clearTimeout(deal_search_timer);
        deal_search_timer = setTimeout(deal_search, 350);
    });

    $("#deal_manager, #deal_stage, #deal_only_free").on("change", function () {
        deal_search();
    });
</script>
@endsection
