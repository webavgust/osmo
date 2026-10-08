{{--
    Попап привязки КП к сделке Битрикса (patch v24, переделан в patch v43).

    Обратная сторона попапа «Прикрепление сделки к Битрикс24» с карточки КП
    (pub/proposal/boxes/deal.blade.php): там к КП подбирают сделки, здесь к
    сделке подбирают КП. Связь многие-ко-многим (решение владельца 08.10.2026):
    к сделке можно привязать несколько КП, у КП может быть несколько сделок
    (одна из них главная). Привязка живёт на всей группе итераций.

    Вверху — все КП, привязанные к сделке, у каждого своя кнопка «Отвязать»
    (уходит proposal_group). В выдаче поиска кнопка «Привязать» есть всегда;
    КП, уже привязанное к этой сделке, помечено «привязано», а другие сделки
    КП показаны серой справкой «ещё сделок: N» — это не запрет. После
    привязки и отвязки попап не закрывается: список привязанных перерисовывается
    из ответа API, строка выдачи — по месту. Реестр под попапом обновляется
    при закрытии попапа любым способом, если что-то менялось.

    Если партнёр сделки известен (компания Битрикса сопоставлена с партнёром
    портала, patch v23), в выдаче только его КП: чужие в этой сделке всё равно
    не нужны. Область партнёра снимается галочкой — на случай, когда КП завели
    не на того партнёра. Партнёра нет — ищем словами, строка поиска приходит
    заполненной компанией сделки.
--}}
@extends('components.box.box-static-large')

@php
    /** Подробности строки КП, которых нет в ответе attach/detach: партнёр, компания, сумма */
    $proposal_extra = fn($proposal) => [
        'sub' => collect([
            $proposal->partner?->name,
            $proposal->company?->name,
            $proposal->sended_at?->format('d.m.Y'),
        ])->filter()->implode(' · '),
        'currency' => strtoupper((string) $proposal->currency_slug),
        'amount' => (float) $proposal->cost_total,
    ];

    /** Привязанные КП — в том же виде, что приходит в ответе API, плюс подробности */
    $extra_by_group = $proposals->keyBy('group')->map($proposal_extra);
    $linked_json = collect(\App\Modules\Pub\Proposal\Services\ProposalDealService::dealProposalRows((int) $deal->id))
        ->map(fn($row) => $row + ($extra_by_group[$row['group']] ?? []))
        ->values()->all();

    /** Первая выдача — сразу в разметке, дальше её обновляет поиск */
    $rows_json = $rows->map(fn($proposal) => [
        'group' => $proposal->group,
        'number' => $proposal->number,
        'name' => $proposal->name,
        'partner' => $proposal->partner?->name,
        'company' => $proposal->company?->name,
        'manager' => $proposal->manager?->full_name,
        'date' => $proposal->sended_at?->format('d.m.Y'),
        'currency' => strtoupper((string) $proposal->currency_slug),
        'amount' => (float) $proposal->cost_total,
        'deals_count' => $proposal->deals_count,
        'attached_here' => (bool) $proposal->attached_here,
        'deals' => $proposal->deals ?: [],
        'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
    ])->all();
@endphp

@section('body')
    <style>
        #deal_proposal_results .row-line,
        #deal_proposal_linked .row-line { border-bottom: 1px dashed #eff2f5; }
        #deal_proposal_results .row-line:last-child,
        #deal_proposal_linked .row-line:last-child { border-bottom: 0; }
    </style>

    {{-- Сделка, ради которой открыт попап --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-5 fs-7">
        <span class="text-muted">Сделка:</span>
        <a href="{{ $deal_url }}" target="_blank" class="badge badge-light-primary fs-7 text-decoration-none">
            #{{ $deal->id }} {{ \Illuminate\Support\Str::limit($deal->title, 70) }}
            <i class="fa-light fa-arrow-up-right-from-square fs-8 ms-2"></i>
        </a>

        @if($deal->company_name)
            <x-ui.badge.light type="info" class="text-info-700 bg-hover-info text-hover-white">
                {{ $deal->company_name }}
            </x-ui.badge.light>
        @endif

        @if((float) $deal->opportunity > 0)
            <span class="fw-bold ms-2 text-nowrap">
                {{ tools()->cost_normalize(round($deal->opportunity)) }} {{ $deal->currency_id }}
            </span>
        @endif
    </div>

    {{-- Привязанные КП: рисует JS из $linked_json, после операций — из ответа API --}}
    <div id="deal_proposal_current" class="mb-5">
        <div class="d-flex align-items-center mb-2 fs-7">
            <span class="fw-bold">Привязанные КП</span>
            <span class="badge badge-light ms-2" id="deal_proposal_count">{{ count($linked_json) }}</span>
        </div>
        <div id="deal_proposal_linked" class="bg-light-success rounded"></div>
    </div>

    {{-- Поиск --}}
    <div class="position-relative mb-3">
        <i class="fa-light fa-magnifying-glass fs-4 position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
        <input type="text" id="deal_proposal_q" class="form-control form-control-solid ps-12"
               value="{{ $q }}" autocomplete="off"
               placeholder="{{ $partner ? 'Номер КП или название' : 'Номер КП, название, партнёр, заказчик' }}"/>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        @if($partner)
            <label class="form-check form-check-custom form-check-solid flex-shrink-0"
                   title="Показать КП всех партнёров, а не только {{ $partner->name }}">
                <input class="form-check-input" type="checkbox" id="deal_proposal_all"/>
                <span class="form-check-label fs-7 text-nowrap">показать КП всех партнёров</span>
            </label>
        @else
            <span></span>
        @endif

        <div class="text-muted fs-8 text-end" id="deal_proposal_hint">
            @if($partner)
                Показаны только КП партнёра {{ $partner->name }}.
            @else
                Поиск подставил компанию сделки.
            @endif
            К сделке можно привязать несколько КП.
        </div>
    </div>

    {{-- Результаты --}}
    <div id="deal_proposal_results" class="border border-gray-300 rounded"
         style="max-height: 380px; overflow-y: auto;"></div>
@endsection

@section('footer')
    <button type="button" class="btn btn-light" onclick="javascript:box_close();">Закрыть</button>
@endsection

@section('modal')
    <script>
        (function () {
            var timer = null;
            var dirty = false;

            // текущая выдача поиска — после привязки/отвязки перерисовываем её по месту
            var rows = @json($rows_json);

            // подробности КП (партнёр, сумма) по группе: ответ attach/detach их не несёт,
            // берём из первой отрисовки и из выдачи поиска
            var extra = {};

            var esc = function (value) { return $('<span>').text(value == null ? '' : value).html(); };

            var money = function (item) {
                return item && item.amount ? cost_normalize(Math.round(item.amount)) + ' ' + (item.currency || '') : '';
            };

            /** Запомнить подробности строк выдачи для списка привязанных */
            function remember(list) {
                (list || []).forEach(function (row) {
                    extra[row.group] = {
                        sub: [row.partner, row.company, row.date].filter(Boolean).join(' · '),
                        currency: row.currency,
                        amount: row.amount
                    };
                });
            }

            /** Отрисовать список привязанных КП (формат ответа API: group, number, name, url, is_main) */
            function render_linked(list) {
                list = list || [];
                $('#deal_proposal_count').text(list.length);

                if (!list.length) {
                    $('#deal_proposal_linked').removeClass('bg-light-success').html(
                        '<div class="text-muted fs-7 py-3">К сделке КП не привязаны</div>'
                    );
                    return;
                }

                var html = '';

                list.forEach(function (item) {
                    var info = extra[item.group] || {};
                    var amount = money(info);

                    html += '<div class="row-line d-flex align-items-center justify-content-between py-3 px-3">'
                        +   '<div class="d-flex align-items-center pe-3 overflow-hidden">'
                        +     '<i class="fa-light fa-link fs-4 text-success me-3"></i>'
                        +     '<div class="overflow-hidden">'
                        +       '<div class="fw-semibold fs-7 text-truncate">'
                        +         '<span class="text-muted me-2">' + esc(item.number || 'без номера') + '</span>'
                        +         '<a href="' + esc(item.url) + '" target="_blank">' + esc(item.name) + '</a>'
                        +         (item.is_main
                                    ? '<span class="badge badge-light-success fs-9 ms-2" title="Эта сделка — главная у КП">главная у КП</span>'
                                    : '')
                        +       '</div>'
                        +       (info.sub ? '<div class="fs-8 text-muted text-truncate">' + esc(info.sub) + '</div>' : '')
                        +     '</div>'
                        +   '</div>'
                        +   '<div class="d-flex align-items-center flex-shrink-0 gap-3">'
                        +     (amount ? '<span class="fw-bold fs-7 text-nowrap">' + esc(amount) + '</span>' : '')
                        +     '<button type="button" class="btn btn-sm btn-light-danger text-nowrap"'
                        +       ' onclick="deal_proposal_detach(\'' + esc(item.group) + '\', \'' + esc(item.number || '') + '\')"'
                        +       ' title="Отвязать КП от сделки">'
                        +       '<i class="fa-light fa-link-slash fs-6 me-2"></i>Отвязать</button>'
                        +   '</div>'
                        + '</div>';
                });

                $('#deal_proposal_linked').addClass('bg-light-success').html(html);
            }

            /** Отрисовать выдачу поиска */
            function render() {
                if (!rows || !rows.length) {
                    $('#deal_proposal_results').html(
                        '<div class="text-center text-muted fs-7 py-10">КП не найдены</div>'
                    );
                    return;
                }

                var html = '';

                rows.forEach(function (row) {
                    var amount = money(row);
                    var sub = [row.partner, row.company, row.manager, row.date].filter(Boolean).join(' · ');
                    var deals = row.deals || [];

                    // другие сделки КП — справка, не запрет
                    var others = deals.length
                        ? '<span class="badge badge-light fs-8 text-gray-600 text-nowrap"'
                            + ' title="Другие сделки этого КП: ' + esc(deals.map(function (id) { return '#' + id; }).join(', ')) + '">'
                            + 'ещё сделок: ' + deals.length + '</span>'
                        : '';

                    var action = row.attached_here
                        ? '<span class="badge badge-light-success fs-8 text-nowrap">привязано</span>'
                            + '<button type="button" class="btn btn-sm btn-light-danger text-nowrap"'
                            + ' onclick="deal_proposal_detach(\'' + esc(row.group) + '\', \'' + esc(row.number || '') + '\')">'
                            + '<i class="fa-light fa-link-slash fs-6 me-2"></i>Отвязать</button>'
                        : '<button type="button" class="btn btn-sm btn-light-primary text-nowrap"'
                            + ' onclick="deal_proposal_attach(\'' + esc(row.group) + '\')">'
                            + '<i class="fa-light fa-link fs-6 me-2"></i>Привязать</button>';

                    html += '<div class="row-line d-flex align-items-center justify-content-between py-3 px-3">'
                        +   '<div class="pe-3 overflow-hidden">'
                        +     '<div class="fw-semibold fs-7 text-truncate">'
                        +       '<span class="text-muted me-2">' + esc(row.number || 'без номера') + '</span>'
                        +       '<a href="' + esc(row.url) + '" target="_blank">' + esc(row.name) + '</a>'
                        +     '</div>'
                        +     '<div class="fs-8 text-muted text-truncate">' + esc(sub) + '</div>'
                        +   '</div>'
                        +   '<div class="d-flex align-items-center flex-shrink-0 gap-3">'
                        +     others
                        +     (amount ? '<span class="fw-bold fs-7 text-nowrap">' + esc(amount) + '</span>' : '')
                        +     action
                        +   '</div>'
                        + '</div>';
                });

                $('#deal_proposal_results').html(html);
            }

            /** Поиск КП */
            function search() {
                $('#deal_proposal_results').css('opacity', .4);

                $.ajax({
                    url: @json(route('api.crm_deal.proposal_search', $deal->id)),
                    type: 'GET',
                    data: {
                        q: $('#deal_proposal_q').val(),
                        all_partners: $('#deal_proposal_all').prop('checked') ? 1 : 0,
                        _token: csrf_token()
                    },
                    dataType: 'json',
                    success: function (response) {
                        $('#deal_proposal_results').css('opacity', 1);
                        rows = response.rows || [];
                        remember(rows);
                        render();

                        var scope = response.partner ? ' Область: КП партнёра ' + response.partner + '.' : '';

                        $('#deal_proposal_hint').text(response.count > 0
                            ? 'Найдено: ' + response.count + '.' + scope + ' Показаны последние совпадения — уточните запрос, если нужного КП нет.'
                            : (response.partner
                                ? 'У партнёра ' + response.partner + ' таких КП нет. Снимите область партнёра галочкой слева или измените запрос.'
                                : 'Ничего не найдено. Искать можно по номеру КП, названию, партнёру и заказчику.'));
                    },
                    error: function () {
                        $('#deal_proposal_results').css('opacity', 1);
                        toastr.error('Не получилось выполнить поиск', 'Это провал!', {progressBar: true, timeOut: 3000});
                    }
                });
            }

            /** Привязать выбранное КП к сделке */
            window.deal_proposal_attach = function (group) {
                request(@json(route('api.crm_deal.proposal_attach', $deal->id)), group, true);
            };

            /** Отвязать от сделки одно КП */
            window.deal_proposal_detach = function (group, number) {
                if (!confirm('Отвязать КП ' + (number || '') + ' от сделки?')) return;

                request(@json(route('api.crm_deal.proposal_detach', $deal->id)), group, false);
            };

            /**
             * Общий запрос привязки/отвязки. Попап не закрывается: список
             * привязанных берём из ответа, строку выдачи правим по месту.
             */
            function request(url, group, attached) {
                body_block();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: 'proposal_group=' + encodeURIComponent(group) + '&_token=' + csrf_token(),
                    dataType: 'json',
                    success: function (response) {
                        body_unblock();

                        if (response.result !== 'success') {
                            toastr.error(response.message || 'Не получилось изменить привязку', 'Это провал!', {progressBar: true, timeOut: 6000});
                            return;
                        }

                        dirty = true;
                        toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});

                        render_linked(response.proposals);

                        rows.forEach(function (row) {
                            if (row.group === group) row.attached_here = attached;
                        });
                        render();
                    },
                    error: function (xhr) {
                        body_unblock();
                        toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                    }
                });
            }

            $(document).ready(function () {
                var linked = @json($linked_json);

                // подробности уже привязанных КП пришли с сервера — запоминаем их тоже
                linked.forEach(function (item) {
                    extra[item.group] = {sub: item.sub, currency: item.currency, amount: item.amount};
                });
                remember(rows);
                render_linked(linked);
                render();

                $('#deal_proposal_q').on('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(search, 400);
                });

                $('#deal_proposal_all').on('change', search);

                // реестр перерисовываем при любом закрытии попапа (кнопка, крестик),
                // если привязки менялись: колонка КП считается на сервере
                $('#box #staticBackdrop').one('hidden.bs.modal', function () {
                    if (dirty) location.reload();
                });
            });
        })();
    </script>
@endsection
