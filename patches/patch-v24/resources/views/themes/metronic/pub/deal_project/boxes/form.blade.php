{{--
    Попап проекта по сделке (patch v24).

    Один шаблон на три случая:
      · создание проекта для сделки (`deal_project.box_form`, у сделки проекта нет);
      · редактирование проекта этой сделки (тот же адрес, проект уже есть) —
        добавляется кнопка «Открепить сделку»;
      · редактирование проекта без сделки (`deal_project.box_edit`).

    Партнёр обычно не выбирается: он определяется компанией сделки через
    сопоставление partner_crm_companies (patch v23). Если сопоставления нет,
    попап предлагает выбрать партнёра руками — выбор запоминается в
    partner_crm_companies, и в следующий раз партнёр определится сам. У
    партнёра компаний Битрикса может быть несколько (в Битриксе партнёр —
    текстовое поле, названия расходятся), у компании партнёр — один.

    Спецификации приезжают все партнёрские, а список фильтруется на клиенте по
    выбранной компании: компанию можно сменить прямо в попапе.
--}}
@extends('components.box.box-static-large')

@section('body')
    @if(empty($partner))
        @php $can_match = $deal && !empty($deal->company_id) && !empty($partners) && count($partners); @endphp

        <div class="text-center py-10">
            <i class="fa-light fa-link-slash fs-2x text-muted mb-4 d-block"></i>
            <div class="fs-5 fw-semibold text-gray-800 mb-2">Партнёр сделки не определён</div>
            <div class="text-muted">
                Компания сделки
                @if($deal && $deal->company_name)
                    «{{ $deal->company_name }}»
                @endif
                не сопоставлена ни с одним партнёром портала.
                Сопоставьте её в редактировании партнёра — поле «Компании в Битрикс24»@if($can_match),
                    или выберите из списка ниже@endif.
            </div>
        </div>

        @if($can_match)
            <div class="separator separator-dashed my-6"></div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Партнёр</label>
                <div class="col-sm-9">
                    <select id="deal_project_partner" class="form-select" data-placeholder="выберите партнёра">
                        <option></option>
                        @foreach($partners as $row)
                            <option value="{{ $row['id'] }}">{{ $row['name'] }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        Компания «{{ $deal->company_name ?: '#' . $deal->company_id }}» закрепится за
                        выбранным партнёром — как в поле «Компании в Битрикс24» его формы.
                        У партнёра таких компаний может быть несколько, у компании партнёр только один.
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    /** Сопоставить компанию сделки с партнёром и открыть попап заново */
                    window.deal_project_partner_save = function () {
                        var partner_id = $('#deal_project_partner').val();

                        if (!partner_id) {
                            toastr.error('Выберите партнёра из списка', 'Это провал!', {progressBar: true, timeOut: 4000});
                            return;
                        }

                        body_block();

                        $.ajax({
                            url: @json(route('api.deal_project.partner', $deal->id)),
                            type: 'POST',
                            data: 'partner_id=' + encodeURIComponent(partner_id) + '&_token=' + csrf_token(),
                            dataType: 'json',
                            success: function (response) {
                                body_unblock();

                                if (response.result !== 'success') {
                                    toastr.error(response.message || 'Не получилось сопоставить партнёра', 'Это провал!', {progressBar: true, timeOut: 6000});
                                    return;
                                }

                                toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 4000});

                                // партнёр теперь определяется — открываем тот же попап заново
                                box({href: @json(route('deal_project.box_form', $deal->id))});
                            },
                            error: function (xhr) {
                                body_unblock();
                                toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                            }
                        });
                    };

                    $(document).ready(function () {
                        $('#deal_project_partner').select2({
                            width: '100%',
                            dropdownParent: $('#staticBackdrop'),
                            placeholder: 'выберите партнёра',
                            allowClear: true
                        });
                    });
                })();
            </script>
        @endif
    @else
        @php
            /** Отмеченные спецификации: из КП (не снимаются) + отмеченные руками */
            $locked_ids = $locked->keys()->map(fn($id) => (int) $id)->all();
            $checked_ids = array_map('intval', $checked);
            $is_new = empty($project);
        @endphp

        <style>
            #deal_project_form .spec_row { border-bottom: 1px dashed #eff2f5; padding: 6px 0; }
            #deal_project_form .spec_row:last-child { border-bottom: 0; }
            #deal_project_form .spec_list { max-height: 260px; overflow-y: auto; }
        </style>

        <form id="deal_project_form" onsubmit="return false;">
            {{-- Шапка: партнёр и сделка, ради которой открыт попап --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-5 fs-7">
                <span class="text-muted">Партнёр:</span>
                <span class="badge badge-light-warning fs-7">{{ $partner->name }}</span>

                @if($deal)
                    <span class="text-muted ms-3">Сделка:</span>
                    <a href="{{ \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::url($deal->id) }}"
                       target="_blank" class="badge badge-light-primary fs-7 text-decoration-none">
                        #{{ $deal->id }} {{ \Illuminate\Support\Str::limit($deal->title, 60) }}
                        <i class="fa-light fa-arrow-up-right-from-square fs-8 ms-1"></i>
                    </a>
                @endif

                @if($project && $project->is_archived)
                    <span class="badge badge-light-dark fs-7 ms-3">
                        <i class="fa-light fa-box-archive me-1"></i>
                        в архиве с {{ $project->archived_at?->format('d.m.Y') }}
                    </span>
                @endif
            </div>

            {{-- Новый проект или прикрепить к существующему (только при создании) --}}
            @if($is_new && $projects->isNotEmpty())
                <div class="mb-5">
                    <div class="d-flex flex-wrap gap-6 mb-3">
                        <label class="form-check form-check-custom form-check-sm">
                            <input class="form-check-input" type="radio" name="target" value="new" checked/>
                            <span class="form-check-label fw-semibold">Новый проект</span>
                        </label>
                        <label class="form-check form-check-custom form-check-sm">
                            <input class="form-check-input" type="radio" name="target" value="existing"/>
                            <span class="form-check-label fw-semibold">Прикрепить к существующему</span>
                        </label>
                    </div>

                    <div id="deal_project_existing" class="d-none">
                        <select name="deal_project_id" id="deal_project_id" class="form-select">
                            @foreach($projects as $item)
                                <option value="{{ $item->id }}" data-company="{{ (int) $item->company_id }}">
                                    Проект от {{ $item->date_start?->format('d.m.Y') }}
                                    @if($item->is_pilot) · пилот @endif
                                    · {{ $item->company?->name ?: 'без компании' }}
                                    · сделок: {{ $item->deals->count() }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Прикрепить можно к проекту того же партнёра и той же компании.
                        </div>
                    </div>
                </div>

                <div class="separator separator-dashed mb-5"></div>
            @endif

            {{-- Поля проекта --}}
            <div id="deal_project_fields">
                <div class="row g-4 mb-4">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Дата начала <span class="text-danger">*</span></label>
                        <input type="date" name="date_start" id="deal_project_date_start" class="form-control"
                               value="{{ $project?->date_start?->format('Y-m-d') ?? now()->format('Y-m-d') }}"/>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Компания</label>
                        <select name="company_id" id="deal_project_company" class="form-select">
                            <option value="">не указана</option>
                            @foreach($companies as $item)
                                <option value="{{ $item->id }}" @selected((int) $company?->id === (int) $item->id)>
                                    {{ $item->name }}@if(!$item->active) (неактивна)@endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Компании партнёра {{ $partner->name }}</div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label class="form-check form-check-custom form-check-sm mt-8">
                                <input class="form-check-input" type="checkbox" name="is_pilot" value="1"
                                       id="deal_project_pilot" @checked($project?->is_pilot)/>
                                <span class="form-check-label fw-semibold">Пилот</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- «Срок» показывается, только когда «Пилот» снят --}}
                <div class="row g-4 mb-4 @if($project?->is_pilot) d-none @endif" id="deal_project_deadline_row">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Срок</label>
                        <input type="date" name="deadline" id="deal_project_deadline" class="form-control"
                               value="{{ $project?->deadline?->format('Y-m-d') }}"/>
                        <div class="form-text">Плановое окончание проекта</div>
                    </div>
                </div>

                {{-- Спецификации --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Спецификации
                        <span class="text-muted fw-normal fs-8">— из КП сделок отмечены и не снимаются</span>
                    </label>

                    @if($specs->isEmpty())
                        <div class="text-muted fs-7">У партнёра нет спецификаций по рамочным договорам.</div>
                    @else
                        <div class="border rounded p-3 spec_list" id="deal_project_specs">
                            @foreach($specs as $spec)
                                @php
                                    $spec_id = (int) $spec->id;
                                    $is_locked = in_array($spec_id, $locked_ids, true);
                                    $proposals = $is_locked ? $locked[$spec_id]['proposals'] : collect();
                                    $date = $spec->date_create ?? $spec->contract?->date;
                                @endphp
                                <div class="spec_row d-flex align-items-start gap-3"
                                     data-company="{{ (int) $spec->company_id }}">
                                    <label class="form-check form-check-custom form-check-sm mt-1">
                                        <input class="form-check-input spec_check" type="checkbox"
                                               name="specs[]" value="{{ $spec_id }}"
                                               @checked($is_locked || in_array($spec_id, $checked_ids, true))
                                               @if($is_locked) onclick="return false;" data-locked="1" @endif/>
                                    </label>
                                    <div class="flex-grow-1 fs-7">
                                        <div class="fw-semibold">
                                            {{ $spec->name ?: 'без названия' }}
                                            @if($is_locked)
                                                <span class="badge badge-light-success fs-9 ms-1">
                                                    из КП {{ $proposals->map(fn($item) => '№' . ($item->number ?: '—'))->implode(', ') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-muted fs-8">
                                            {{ $spec->company?->name ?: 'без компании' }}
                                            @if($spec->contract) · договор {{ $spec->contract->number }} @endif
                                            @if($date) · {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }} @endif
                                            @if($spec->amount) · {{ tools()->cost_normalize(round($spec->amount)) }} {{ $spec->currency_slug }} @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text" id="deal_project_specs_empty" style="display: none;">
                            У выбранной компании спецификаций нет.
                        </div>
                    @endif
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold">Комментарий</label>
                    <textarea name="comment" class="form-control" rows="3">{{ $project?->comment }}</textarea>
                </div>
            </div>
        </form>

        <script>
            (function () {
                var isNew = {{ $is_new ? 'true' : 'false' }};

                /** Показать «Срок» только когда «Пилот» снят */
                function deal_project_pilot() {
                    $('#deal_project_deadline_row').toggleClass('d-none', $('#deal_project_pilot').is(':checked'));
                }

                /** Спецификации выбранной компании: остальные прячем и снимаем */
                function deal_project_specs() {
                    var company = parseInt($('#deal_project_company').val()) || 0;
                    var shown = 0;

                    $('#deal_project_specs .spec_row').each(function () {
                        var own = !company || parseInt($(this).data('company')) === company;
                        var $check = $(this).find('.spec_check');

                        // спецификация из КП видна всегда: её связь уже существует
                        if ($check.data('locked')) own = true;

                        $(this).toggle(own);
                        if (own) shown++;
                        else $check.prop('checked', false);
                    });

                    $('#deal_project_specs').toggle(shown > 0);
                    $('#deal_project_specs_empty').toggle(shown === 0);
                }

                /** Новый проект / прикрепить к существующему */
                function deal_project_target() {
                    var existing = $('#deal_project_form input[name="target"]:checked').val() === 'existing';

                    $('#deal_project_existing').toggleClass('d-none', !existing);
                    $('#deal_project_fields').toggleClass('d-none', existing);
                    $('#deal_project_save span').text(existing ? 'Прикрепить' : 'Сохранить');
                }

                /**
                 * Сохранить: создать проект, изменить его либо прикрепить сделку
                 * к существующему проекту — адрес зависит от режима попапа.
                 */
                window.deal_project_save = function () {
                    var existing = isNew && $('#deal_project_form input[name="target"]:checked').val() === 'existing';
                    var url, data = '_token=' + csrf_token();

                    if (existing) {
                        url = '{{ $deal ? route('api.deal_project.attach', ['project' => '__ID__', 'deal' => $deal->id]) : '' }}'
                            .replace('__ID__', $('#deal_project_id').val());
                    } else {
                        if (!$('#deal_project_date_start').val()) {
                            toastr.error('Укажите дату начала', 'Это провал!', {progressBar: true, timeOut: 4000});
                            return;
                        }

                        url = @json($is_new
                            ? ($deal ? route('api.deal_project.store', $deal->id) : '')
                            : route('api.deal_project.update', $project));

                        data = $('#deal_project_fields :input').serialize() + '&_token=' + csrf_token();
                        if (!$('#deal_project_pilot').is(':checked')) data += '&is_pilot=0';
                    }

                    deal_project_request(url, data);
                };

                /** Открепить сделку от её проекта */
                window.deal_project_detach = function () {
                    if (!confirm('Открепить сделку от проекта?')) return;

                    deal_project_request(
                        @json($deal ? route('api.deal_project.detach', $deal->id) : ''),
                        '_token=' + csrf_token()
                    );
                };

                /** Архив / возврат из архива */
                window.deal_project_archive = function (back) {
                    deal_project_request(
                        back
                            ? @json($project ? route('api.deal_project.unarchive', $project) : '')
                            : @json($project ? route('api.deal_project.archive', $project) : ''),
                        '_token=' + csrf_token()
                    );
                };

                /** Общий запрос попапа: тостер и обновление того, что под ним */
                function deal_project_request(url, data) {
                    if (!url) return;

                    body_block();
                    $('#deal_project_form').find(':input').prop('disabled', true);

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: data,
                        dataType: 'json',
                        success: function (response) {
                            body_unblock();
                            $('#deal_project_form').find(':input').prop('disabled', false);

                            if (response.result !== 'success') {
                                toastr.error(response.message || 'Не получилось сохранить проект', 'Это провал!', {progressBar: true, timeOut: 6000});
                                return;
                            }

                            toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});
                            box_close();

                            // страница со сделками перерисовывает себя сама
                            if (typeof window.deal_project_changed === 'function') window.deal_project_changed();
                            else location.reload();
                        },
                        error: function (xhr) {
                            body_unblock();
                            $('#deal_project_form').find(':input').prop('disabled', false);
                            toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                        }
                    });
                }

                $(document).ready(function () {
                    $('#deal_project_company, #deal_project_id').select2({width: '100%', dropdownParent: $('#staticBackdrop')});

                    $('#deal_project_pilot').on('change', deal_project_pilot);
                    $('#deal_project_company').on('change', deal_project_specs);
                    $('#deal_project_form input[name="target"]').on('change', deal_project_target);

                    deal_project_pilot();
                    deal_project_specs();
                    if (isNew) deal_project_target();
                });
            })();
        </script>
    @endif
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <div class="d-flex gap-2">
            <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
                <span>Закрыть</span>
            </x-ui.button.default>

            @if($partner && $project && $deal)
                <x-ui.button.default btn_type="light-danger" class="text-danger" onclick="javascript:deal_project_detach();">
                    <i class="fa-light fa-link-slash me-2"></i>
                    <span>Открепить сделку</span>
                </x-ui.button.default>
            @endif
        </div>

        @if(empty($partner) && $deal && !empty($deal->company_id) && !empty($partners) && count($partners))
            <div class="d-flex gap-2">
                <x-ui.button.default btn_type="success" onclick="javascript:deal_project_partner_save();">
                    <i class="fa-light fa-link me-2"></i>
                    <span>Сопоставить и продолжить</span>
                </x-ui.button.default>
            </div>
        @endif

        @if($partner)
            <div class="d-flex gap-2">
                @if($project && !$project->is_archived)
                    <x-ui.button.default btn_type="light-dark" onclick="javascript:deal_project_archive(false);">
                        <i class="fa-light fa-box-archive me-2"></i>
                        <span>Отправить в архив</span>
                    </x-ui.button.default>
                @elseif($project)
                    <x-ui.button.default btn_type="light-primary" onclick="javascript:deal_project_archive(true);">
                        <i class="fa-light fa-box-open me-2"></i>
                        <span>Вернуть из архива</span>
                    </x-ui.button.default>
                @endif

                <x-ui.button.default id="deal_project_save" btn_type="success" onclick="javascript:deal_project_save();">
                    <i class="fa-light fa-floppy-disk me-2"></i>
                    <span>Сохранить</span>
                </x-ui.button.default>
            </div>
        @endif
    </div>
@endsection
