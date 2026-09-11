{{--
    Попап привязки КП к сделке Битрикса (patch v24).

    Обратная сторона попапа «Прикрепление сделки к Битрикс24» с карточки КП
    (pub/proposal/boxes/deal.blade.php): там к КП подбирают сделки, здесь к
    сделке подбирают КП. Правила общие (ProposalDealService): одна сделка
    принадлежит одному КП, у КП сделок может быть несколько, привязка живёт
    на всей группе итераций.

    Если партнёр сделки известен (компания Битрикса сопоставлена с партнёром
    портала, patch v23), в выдаче только его КП: чужие в этой сделке всё равно
    не нужны. Область партнёра снимается галочкой — на случай, когда КП завели
    не на того партнёра. Партнёра нет — ищем словами, строка поиска приходит
    заполненной компанией сделки.
--}}
@extends('components.box.box-static-large')

@php
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
        'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
    ])->all();
@endphp

@section('body')
    <style>
        #deal_proposal_results .row-line { border-bottom: 1px dashed #eff2f5; }
        #deal_proposal_results .row-line:last-child { border-bottom: 0; }
    </style>

    {{-- Сделка, ради которой открыт попап --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-5 fs-7">
        <span class="text-muted">Сделка:</span>
        <a href="{{ $deal_url }}" target="_blank" class="badge badge-light-primary fs-7 text-decoration-none">
            #{{ $deal->id }} {{ \Illuminate\Support\Str::limit($deal->title, 70) }}
            <i class="fa-light fa-arrow-up-right-from-square fs-8 ms-1"></i>
        </a>

        @if($deal->company_name)
            <span class="badge badge-light-warning fs-7">{{ $deal->company_name }}</span>
        @endif

        @if((float) $deal->opportunity > 0)
            <span class="fw-bold ms-2 text-nowrap">
                {{ tools()->cost_normalize(round($deal->opportunity)) }} {{ $deal->currency_id }}
            </span>
        @endif
    </div>

    {{-- Текущая привязка --}}
    <div id="deal_proposal_current" class="@unless($proposal) d-none @endunless mb-5">
        <div class="d-flex align-items-center justify-content-between bg-light-success rounded p-3">
            <div class="d-flex align-items-center pe-3 overflow-hidden">
                <i class="fa-light fa-link fs-2 text-success me-3"></i>
                <div class="overflow-hidden">
                    <div class="fw-bold text-truncate">
                        @if($proposal)
                            <span class="text-muted me-2">{{ $proposal->number ?: 'без номера' }}</span>
                            <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}"
                               target="_blank">{{ $proposal->name }}</a>
                        @endif
                    </div>
                    <div class="fs-8 text-muted text-truncate">
                        @if($proposal)
                            {{ collect([
                                $proposal->partner?->name,
                                $proposal->company?->name,
                                $proposal->sended_at?->format('d.m.Y'),
                            ])->filter()->implode(' · ') }}
                        @endif
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-light-danger flex-shrink-0"
                    onclick="javascript:deal_proposal_detach();" title="Отвязать КП от сделки">
                <i class="fa-light fa-link-slash fs-6 me-2"></i>Отвязать
            </button>
        </div>
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
                <span class="form-check-label fs-7 text-nowrap">КП всех партнёров</span>
            </label>
        @else
            <span></span>
        @endif

        <div class="text-muted fs-8 text-end" id="deal_proposal_hint">
            @if($partner)
                Партнёр сделки — {{ $partner->name }}, показаны только его КП.
            @else
                Поиск подставил компанию сделки.
            @endif
            Одна сделка принадлежит одному КП; у КП сделок может быть несколько.
        </div>
    </div>

    {{-- Результаты --}}
    <div id="deal_proposal_results" class="border border-gray-300 rounded"
         style="max-height: 380px; overflow-y: auto;"></div>
@endsection

@section('footer')
    <button type="button" class="btn btn-light" onclick="javascript:deal_proposal_done();">Закрыть</button>
@endsection

@section('modal')
    <script>
        (function () {
            var timer = null;
            var dirty = false;

            /** Отрисовать выдачу поиска */
            function render(rows) {
                if (!rows || !rows.length) {
                    $('#deal_proposal_results').html(
                        '<div class="text-center text-muted py-10">КП не найдены</div>'
                    );
                    return;
                }

                var html = '';

                rows.forEach(function (row) {
                    var esc = function (value) { return $('<span>').text(value == null ? '' : value).html(); };
                    var amount = row.amount ? cost_normalize(Math.round(row.amount)) + ' ' + (row.currency || '') : '';
                    var sub = [row.partner, row.company, row.manager, row.date].filter(Boolean).join(' · ');

                    html += '<div class="row-line d-flex align-items-center justify-content-between py-3 px-3">'
                        +   '<div class="pe-3 overflow-hidden">'
                        +     '<div class="fw-semibold text-truncate">'
                        +       '<span class="text-muted me-2">' + esc(row.number || 'без номера') + '</span>'
                        +       '<a href="' + esc(row.url) + '" target="_blank">' + esc(row.name) + '</a>'
                        +     '</div>'
                        +     '<div class="fs-8 text-muted text-truncate">' + esc(sub) + '</div>'
                        +   '</div>'
                        +   '<div class="d-flex align-items-center flex-shrink-0 gap-3">'
                        +     (amount ? '<span class="fw-bold text-nowrap">' + esc(amount) + '</span>' : '')
                        +     (row.deals_count
                                ? '<span class="badge badge-light-secondary fs-8 text-nowrap" title="Сделок уже привязано к этому КП">'
                                  + row.deals_count + ' сдел.</span>'
                                : '')
                        +     '<button type="button" class="btn btn-sm btn-light-primary text-nowrap"'
                        +       ' onclick="deal_proposal_attach(\'' + esc(row.group) + '\')">'
                        +       '<i class="fa-light fa-link fs-6 me-2"></i>Привязать</button>'
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
                        render(response.rows);

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
                request(@json(route('api.crm_deal.proposal_attach', $deal->id)),
                    'proposal_group=' + encodeURIComponent(group) + '&_token=' + csrf_token());
            };

            /** Отвязать КП от сделки */
            window.deal_proposal_detach = function () {
                if (!confirm('Отвязать КП от сделки?')) return;

                request(@json(route('api.crm_deal.proposal_detach', $deal->id)), '_token=' + csrf_token());
            };

            /** Закрыть попап и обновить то, что под ним, если что-то менялось */
            window.deal_proposal_done = function () {
                box_close();

                if (!dirty) return;

                // страница со сделками умеет перерисовывать себя сама
                if (typeof window.deal_changed === 'function') window.deal_changed();
                else location.reload();
            };

            /** Общий запрос попапа */
            function request(url, data) {
                body_block();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        body_unblock();

                        if (response.result !== 'success') {
                            toastr.error(response.message || 'Не получилось изменить привязку', 'Это провал!', {progressBar: true, timeOut: 6000});
                            return;
                        }

                        dirty = true;
                        toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});

                        // привязка одна: сделали дело — закрываем попап
                        deal_proposal_done();
                    },
                    error: function (xhr) {
                        body_unblock();
                        toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                    }
                });
            }

            $(document).ready(function () {
                render(@json($rows_json));

                $('#deal_proposal_q').on('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(search, 400);
                });

                $('#deal_proposal_all').on('change', search);
            });
        })();
    </script>
@endsection
