{{--
    Попап «Новый проект» со страницы компании (patch v48).

    Партнёр и компания фиксированы — выводятся только текстом. Пользователь
    выбирает дату старта, признак «Пилот» (срок — только пилоту) и спецификации
    компании. Сделки Битрикса подтягиваются сами по цепочке
    спецификация → КП → сделка: блок «Подтянется само» показывает их заранее,
    сделки из чужих проектов помечаются «будет пропущена».

    Спецификации, уже входящие в действующий проект, выводятся неактивными.
    Проект без спецификаций допустим — перед созданием спрашиваем подтверждение.
--}}
@extends('components.box.box-static-large')

@section('body')
    @if(empty($partner))
        <div class="text-center py-10">
            <i class="fa-light fa-link-slash fs-2x text-muted mb-4 d-block"></i>
            <div class="fs-5 fw-semibold text-gray-800 mb-2">У компании не указан партнёр</div>
            <div class="text-muted">
                Компания «{{ $company->name }}» не привязана к партнёру портала — проект завести нельзя.
                Укажите партнёра в карточке компании и откройте попап заново.
            </div>
        </div>
    @else
        @php
            /** Названия сделок для блока «Подтянется само»: зеркало Битрикса — отдельный запрос */
            $deal_titles = $spec_deals->isEmpty() ? collect()
                : \App\Modules\Bitrix\CrmDeal\Models\CrmDeal::whereIn('id', $spec_deals->flatten()->unique()->values()->all())
                    ->pluck('title', 'id');
            $deal_url = fn($id) => \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::url($id);
            $active_projects = $projects->filter(fn($item) => !$item->is_archived);
        @endphp

        <style>
            #deal_project_company_form .spec_row { border-bottom: 1px dashed #eff2f5; padding: 6px 0; }
            #deal_project_company_form .spec_row:last-child { border-bottom: 0; }
            #deal_project_company_form .spec_list { max-height: 260px; overflow-y: auto; }
            #deal_project_company_form .auto_row + .auto_row { margin-top: 6px; }
        </style>

        <form id="deal_project_company_form" onsubmit="return false;">
            {{-- Шапка: партнёр и компания — только текстом, менять их здесь нельзя --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-5 fs-7">
                <span class="text-muted">Партнёр:</span>
                <span class="badge badge-light-warning fs-7">{{ $partner->name }}</span>

                <span class="text-muted ms-3">Компания:</span>
                <span class="badge badge-light-primary fs-7">{{ $company->name }}</span>
            </div>

            @if($active_projects->isNotEmpty())
                <div class="text-muted fs-8 mb-4">
                    <i class="fa-light fa-circle-info me-1"></i>
                    У компании уже есть действующие проекты:
                    {{ $active_projects->map(fn($item) => $item->label)->implode(', ') }}.
                </div>
            @endif

            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Дата начала <span class="text-danger">*</span></label>
                    <input type="date" name="date_start" id="deal_project_company_date_start" class="form-control"
                           value="{{ now()->format('Y-m-d') }}"/>
                </div>

                <div class="col-lg-4">
                    <label class="form-check form-check-custom form-check-sm mt-lg-11">
                        <input class="form-check-input" type="checkbox" name="is_pilot" value="1"
                               id="deal_project_company_pilot"/>
                        <span class="form-check-label fw-semibold">Пилот</span>
                    </label>
                </div>

                {{-- «Срок» — только пилоту (правка владельца 11.09.2026) --}}
                <div class="col-lg-4 d-none" id="deal_project_company_deadline_col">
                    <label class="form-label fw-semibold">Срок</label>
                    <input type="date" name="deadline" class="form-control"/>
                    <div class="form-text">Плановое окончание пилота</div>
                </div>
            </div>

            {{-- Спецификации компании --}}
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Спецификации
                    <span class="text-muted fw-normal fs-8">— сделки Битрикса подтянутся по их КП</span>
                </label>

                @if($specs->isEmpty())
                    <div class="text-muted fs-7">У компании нет спецификаций по договорам партнёра.</div>
                @else
                    <div class="border rounded p-3 spec_list">
                        @foreach($specs as $spec)
                            @php
                                $spec_id = (int) $spec->id;
                                $busy_project = $busy->get($spec_id);
                                $type = \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $spec->contract?->type);
                                $currency = $spec->currency?->symbol ?: $spec->currency_slug;
                            @endphp
                            <div class="spec_row d-flex align-items-start gap-3 @if($busy_project) opacity-50 @endif">
                                <label class="form-check form-check-custom form-check-sm mt-1">
                                    <input class="form-check-input spec_check" type="checkbox"
                                           name="specs[]" value="{{ $spec_id }}"
                                           @if($busy_project) disabled data-busy="1" @endif/>
                                </label>
                                <div class="flex-grow-1 fs-7">
                                    <div class="fw-semibold">
                                        @if($type)
                                            <span class="badge badge-light-{{ $type->data()['color'] }} fs-9 me-1">{{ $type->data()['label'] }}</span>
                                        @endif
                                        {{ $spec->name ?: 'без названия' }}
                                        @if($busy_project)
                                            <span class="badge badge-light fs-9 ms-1">
                                                в проекте от {{ $busy_project->date_start?->format('d.m.Y') ?? '—' }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-muted fs-8">
                                        @if($spec->contract) договор {{ $spec->contract->number ?: 'б/н' }} @endif
                                        @if($spec->date) · {{ \Carbon\Carbon::parse($spec->date)->format('d.m.Y') }} @endif
                                        @if($spec->amount) · {{ tools()->cost_normalize(round($spec->amount)) }} {{ $currency }} @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Подтянется само: сделки по отмеченным спецификациям --}}
            @if($specs->isNotEmpty())
                <div class="bg-light-primary rounded p-4 mb-4 fs-7 d-none" id="deal_project_company_auto">
                    <div class="fw-semibold mb-2">
                        <i class="fa-light fa-wand-magic-sparkles text-primary me-1"></i>
                        Подтянется само
                    </div>

                    @php
                        /** Сделка показывается один раз, даже если её КП принесло несколько спецификаций */
                        $type_label = function ($spec) {
                            $type = \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $spec->contract?->type);
                            return trim(($type ? $type->data()['label'] . ' · ' : '') . ($spec->name ?: 'без названия'));
                        };
                        $free_specs = $specs->reject(fn($spec) => $busy->has((int) $spec->id));
                        $auto_deals = [];
                        foreach ($free_specs as $spec) {
                            foreach ($spec_deals->get((int) $spec->id, []) as $deal_id) {
                                $auto_deals[(int) $deal_id][] = $spec;
                            }
                        }
                    @endphp

                    @foreach($auto_deals as $deal_id => $deal_specs)
                        @php $other = $deal_projects->get((int) $deal_id); @endphp
                        <div class="auto_row d-none ps-1 mb-1" data-specs="{{ collect($deal_specs)->pluck('id')->implode(',') }}">
                            <a href="{{ $deal_url($deal_id) }}" target="_blank" class="text-decoration-none">
                                #{{ $deal_id }} {{ \Illuminate\Support\Str::limit($deal_titles->get($deal_id), 60) }}
                            </a>
                            @if($other)
                                <span class="badge badge-light-warning fs-9 ms-1">
                                    будет пропущена · в проекте от {{ $other->date_start?->format('d.m.Y') ?? '—' }}
                                </span>
                            @endif
                            <div class="text-muted fs-8">по спецификациям: {{ collect($deal_specs)->map($type_label)->implode('; ') }}</div>
                        </div>
                    @endforeach

                    {{-- спецификации без сделок --}}
                    @foreach($free_specs as $spec)
                        @continue(!empty($spec_deals->get((int) $spec->id, [])))
                        <div class="auto_row d-none ps-1 mb-1 text-muted" data-specs="{{ (int) $spec->id }}">
                            {{ $type_label($spec) }}: сделок нет, проект будет без сделки
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mb-2">
                <label class="form-label fw-semibold">Комментарий</label>
                <textarea name="comment" class="form-control" rows="3" placeholder="необязательно"></textarea>
            </div>
        </form>

        <script>
            (function () {
                /** Показать «Срок» только когда стоит «Пилот» */
                function deal_project_company_pilot() {
                    $('#deal_project_company_deadline_col').toggleClass('d-none', !$('#deal_project_company_pilot').is(':checked'));
                }

                /** Блок «Подтянется само»: строки только по отмеченным спецификациям */
                function deal_project_company_auto() {
                    var shown = 0;

                    $('#deal_project_company_auto .auto_row').each(function () {
                        // строка видна, если отмечена хотя бы одна её спецификация
                        var checked = String($(this).data('specs')).split(',').some(function (id) {
                            return $('#deal_project_company_form .spec_check[value="' + id + '"]').is(':checked');
                        });
                        $(this).toggleClass('d-none', !checked);
                        if (checked) shown++;
                    });

                    $('#deal_project_company_auto').toggleClass('d-none', shown === 0);
                }

                /** Создать проект компании */
                window.deal_project_company_save = function () {
                    var $form = $('#deal_project_company_form');

                    if (!$('#deal_project_company_date_start').val()) {
                        toastr.error('Укажите дату начала', 'Это провал!', {progressBar: true, timeOut: 4000});
                        return;
                    }

                    // проект без спецификаций допустим, но сделки к нему не подтянутся
                    if (!$form.find('.spec_check:checked').length
                        && !confirm('Спецификации не отмечены — проект будет без спецификаций и без сделок. Создать?')) {
                        return;
                    }

                    var data = $form.find(':input').serialize() + '&_token=' + csrf_token();
                    if (!$('#deal_project_company_pilot').is(':checked')) data += '&is_pilot=0';

                    body_block();
                    $form.find(':input').not('[data-busy]').prop('disabled', true);

                    $.ajax({
                        url: @json($action),
                        type: 'POST',
                        data: data,
                        dataType: 'json',
                        success: function (response) {
                            body_unblock();
                            $form.find(':input').not('[data-busy]').prop('disabled', false);

                            if (response.result !== 'success') {
                                toastr.error(response.message || 'Не получилось создать проект', 'Это провал!', {progressBar: true, timeOut: 6000});
                                return;
                            }

                            toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});
                            if (response.warning) {
                                // страница сейчас перезагрузится — предупреждение покажем после неё
                                try { sessionStorage.setItem('deal_project_warning', response.warning); } catch (e) {}
                            }

                            box_close();
                            location.reload();
                        },
                        error: function (xhr) {
                            body_unblock();
                            $form.find(':input').not('[data-busy]').prop('disabled', false);
                            toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                        }
                    });
                };

                $(document).ready(function () {
                    $('#deal_project_company_pilot').on('change', deal_project_company_pilot);
                    $('#deal_project_company_form .spec_check').on('change', deal_project_company_auto);

                    deal_project_company_pilot();
                    deal_project_company_auto();
                });
            })();
        </script>
    @endif
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
            <span>Закрыть</span>
        </x-ui.button.default>

        @if($partner)
            <x-ui.button.default btn_type="success" onclick="javascript:deal_project_company_save();">
                <i class="fa-light fa-floppy-disk me-2"></i>
                <span>Создать проект</span>
            </x-ui.button.default>
        @endif
    </div>
@endsection
