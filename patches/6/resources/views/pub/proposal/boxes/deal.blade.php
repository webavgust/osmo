@extends('components.box.box-static-large')

@section('title')
    Привязка сделки Битрикс24
    <span class="text-muted fs-7 ms-2">КП «{{ $proposal->name }}»</span>
@endsection

@section('body')
    {{-- Текущая привязка --}}
    <div id="deal_current" class="@if(empty($proposal->crm_deal_id)) d-none @endif mb-4">
        <div class="d-flex align-items-center justify-content-between bg-light-success rounded p-3">
            <div class="d-flex align-items-center">
                <i class="fa-light fa-link fs-2 text-success me-3"></i>
                <div>
                    <div class="fw-bold">
                        @if($proposal->crm_deal)
                            #{{ $proposal->crm_deal->id }} — {{ $proposal->crm_deal->title }}
                        @else
                            Сделка #{{ $proposal->crm_deal_id }}
                        @endif
                    </div>
                    <div class="fs-8 text-muted">
                        @if($proposal->crm_deal)
                            {{ $proposal->crm_deal->company_name }}
                            @if($proposal->crm_deal->stage_name)
                                · {{ $proposal->crm_deal->stage_name }}
                            @endif
                        @else
                            Сделка не найдена в выгрузке Битрикса
                        @endif
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-light-danger" onclick="javascript:deal_detach();">
                <i class="fa-light fa-link-slash fs-5 me-2"></i>
                Отвязать
            </button>
        </div>
    </div>

    {{-- Поиск --}}
    <div class="row g-2 mb-3">
        <div class="col-12 col-lg-5">
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

        <div class="col-12 col-lg-1 d-flex align-items-center">
            <label class="form-check form-check-custom form-check-solid" title="Скрыть сделки, уже привязанные к другим КП">
                <input class="form-check-input" type="checkbox" id="deal_only_free"
                       @checked($params['only_free']) />
                <span class="form-check-label fs-8">Свободные</span>
            </label>
        </div>
    </div>

    <div class="text-muted fs-8 mb-2" id="deal_hint">
        Поиск подставил название компании из КП. Связь 1:1 — одна сделка может быть привязана только к одному КП.
    </div>

    {{-- Результаты --}}
    <div id="deal_results" style="max-height: 420px; overflow-y: auto;">
        @include('pub.proposal.boxes.deal_rows', ['deals' => $deals])
    </div>
@endsection

@section('footer')
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Закрыть</button>
@endsection

@section('modal')
<script>
    var deal_search_timer = null;

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

            html += '<div class="d-flex align-items-center justify-content-between border-bottom py-3 deal-row">'
                +   '<div class="pe-3 overflow-hidden">'
                +     '<div class="fw-semibold text-truncate">'
                +       '<span class="text-muted me-2">#' + row.id + '</span>'
                +       $('<span>').text(row.title ?? '').html()
                +     '</div>'
                +     '<div class="fs-8 text-muted text-truncate">'
                +       $('<span>').text([row.company, row.customer, row.manager, row.stage]
                            .filter(Boolean).join(' · ')).html()
                +     '</div>'
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

    function deal_attach(deal_id) {
        body_block();

        $.ajax({
            url: "{{ route('api.proposal.deal_attach', [$proposal, $proposal->iteration]) }}",
            type: "POST",
            data: { deal_id: deal_id, _token: csrf_token() },
            dataType: "json",
            success: function (response) {
                body_unblock();

                if (response.result !== 'success') {
                    toastr.error(response.message ?? "Не получилось привязать", "Это провал!", {
                        progressBar: true, timeOut: 4000
                    });
                    return;
                }

                box_close();
                toastr.success("Сделка привязана", "Это успех!", {
                    progressBar: true, timeOut: 2000
                });
                setTimeout(function () { location.reload(); }, 600);
            },
            error: function () {
                body_unblock();
                toastr.error("Не получилось привязать", "Это провал!", {
                    progressBar: true, timeOut: 3000
                });
            }
        });
    }

    function deal_detach() {
        if (!confirm("Отвязать сделку от этого КП?")) return;

        body_block();

        $.ajax({
            url: "{{ route('api.proposal.deal_detach', [$proposal, $proposal->iteration]) }}?_token=" + csrf_token(),
            type: "DELETE",
            dataType: "json",
            success: function () {
                body_unblock();
                box_close();
                toastr.success("Сделка отвязана", "Готово", {
                    progressBar: true, timeOut: 2000
                });
                setTimeout(function () { location.reload(); }, 600);
            },
            error: function () {
                body_unblock();
                toastr.error("Не получилось отвязать", "Это провал!", {
                    progressBar: true, timeOut: 3000
                });
            }
        });
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
