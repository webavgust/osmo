{{--
    Карточка проекта по сделкам (patch v24).

    Открывается плашкой «Проект» из реестра сделок и вкладок партнёра; её же
    будет открывать значок проекта из patch v25. Только чтение плюс две
    кнопки: «Редактировать» (тот же попап формы) и архив/возврат.
--}}
@extends('components.box.box-static-large')

@section('body')
    <style>
        /* платежи внутри ячейки: узкие строки, чтобы таблица не разъезжалась */
        .spec-payments > div + div { margin-top: 6px; }
        .spec-payments { font-size: .95em; }
    </style>

    @php
        $service = \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::class;
        $semantic = ['S' => 'success', 'F' => 'danger', 'P' => 'primary'];
    @endphp

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <table class="table table-row-bordered table-row-dashed align-middle fs-7 m-0">
                <tbody>
                    <tr>
                        <td class="text-muted" style="width: 150px">Партнёр</td>
                        <td class="fw-bold">
                            @if($project->partner)
                                <a href="{{ route('partner.detail', $project->partner) }}">{{ $project->partner->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Компания</td>
                        <td>{{ $project->company?->name ?: 'не указана' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Дата начала</td>
                        <td>{{ $project->date_start?->format('d.m.Y') ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Тип</td>
                        <td>
                            @if($project->is_pilot)
                                <span class="badge badge-light-info fs-8">пилот</span>
                                {{-- срок есть только у пилота (правка владельца 11.09.2026) --}}
                                @if($project->deadline)
                                    <span class="text-muted ms-2">срок: {{ $project->deadline->format('d.m.Y') }}</span>
                                @endif
                            @else
                                <span class="badge badge-light-primary fs-8">проект</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Состояние</td>
                        <td>
                            @if($project->is_archived)
                                <span class="badge badge-light-dark fs-8">
                                    <i class="fa-light fa-box-archive me-1"></i>
                                    в архиве с {{ $project->archived_at?->format('d.m.Y') }}
                                </span>
                            @else
                                <span class="badge badge-light-success fs-8">действующий</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="col-lg-5">
            <div class="bg-light rounded p-3 fs-7 h-100">
                <div class="text-muted mb-1">Комментарий</div>
                <div>{!! $project->comment ? nl2br(e($project->comment)) : '<span class="text-muted">пусто</span>' !!}</div>
            </div>
        </div>
    </div>

    {{-- Сделки проекта --}}
    <div class="fs-6 fw-bold mb-2">
        Сделки <span class="badge badge-light ms-1">{{ $deals->count() }}</span>
    </div>

    @if($deals->isEmpty())
        <div class="text-muted fs-7 mb-5">К проекту не прикреплено ни одной сделки.</div>
    @else
        <div class="table-responsive mb-5">
            <table class="table table-row-bordered align-middle fs-7 m-0">
                <thead>
                    <tr class="fw-bold fs-8 text-muted text-uppercase">
                        <th style="width: 70px">ID</th>
                        <th>Название</th>
                        <th style="width: 140px">Стадия</th>
                        <th style="width: 100px">Дата</th>
                        <th style="width: 120px">КП</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deals as $row)
                        @php $proposal = $proposals->get($row->id); @endphp
                        <tr>
                            <td>
                                <a href="{{ $service::url($row->id) }}" target="_blank">{{ $row->id }}</a>
                            </td>
                            <td>
                                <a href="{{ $service::url($row->id) }}" target="_blank" class="fw-semibold">
                                    {{ $row->title ?: 'без названия' }}
                                    <i class="fa-light fa-arrow-up-right-from-square fs-8 ms-1 text-muted"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-light-{{ $semantic[$row->stage_semantic_id] ?? 'dark' }} fs-8">
                                    {{ $row->stage_name ?: '—' }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                {{ $row->date_create ? \Carbon\Carbon::parse($row->date_create)->format('d.m.Y') : '—' }}
                            </td>
                            <td>
                                @if($proposal)
                                    <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}"
                                       class="badge badge-light-success fs-8 text-decoration-none">
                                        <i class="fa-light fa-link me-1"></i>{{ $proposal->number ?: 'КП' }}
                                    </a>
                                @else
                                    <span class="text-muted fs-8">нет</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Спецификации проекта --}}
    <div class="fs-6 fw-bold mb-2">
        Спецификации <span class="badge badge-light ms-1">{{ $specifications->count() }}</span>
    </div>

    @if($specifications->isEmpty())
        <div class="text-muted fs-7">К проекту не отнесено ни одной спецификации.</div>
    @else
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle fs-7 m-0">
                <thead>
                    <tr class="fw-bold fs-8 text-muted text-uppercase">
                        <th>Спецификация</th>
                        <th style="width: 150px">Компания</th>
                        <th style="width: 90px">Дата</th>
                        <th style="width: 230px">Оплаты</th>
                        <th class="text-end" style="width: 120px">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($specifications as $row)
                        @php
                            $spec = $row['spec'];
                            $date = $spec->date_create ?? $spec->contract?->date;
                            // сумма считается по плановым платежам — так же, как в
                            // таблице договоров на карточке партнёра (patch v25)
                            $spec_amount = $spec->payments->sum('amount_plan');
                            $symbol = $spec->currency?->symbol ?: $spec->currency_slug;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $spec->name ?: 'без названия' }}
                                    @if($row['from_proposal'])
                                        <span class="badge badge-light-success fs-9 ms-1">из КП</span>
                                    @endif
                                </div>
                                @if($spec->contract)
                                    <div class="text-muted fs-8">договор {{ $spec->contract->number }}</div>
                                @endif
                            </td>
                            <td>{{ $spec->company?->name ?: '—' }}</td>
                            <td class="text-nowrap">{{ $date ? \Carbon\Carbon::parse($date)->format('d.m.Y') : '—' }}</td>
                            {{-- оплаты по этой спецификации: ячейка повторяет таблицу
                                 договоров на карточке партнёра (правка владельца 11.09.2026) --}}
                            <td class="px-2">
                                @if($spec->payments->isEmpty())
                                    <span class="text-muted fs-8">нет платежей</span>
                                @else
                                    <div class="spec-payments">
                                        @foreach($spec->payments as $payment)
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="d-flex align-items-center text-nowrap">
                                                    <span class="me-1 text-center text-{{ $payment->status['color'] }}" style="width: 18px">
                                                        <x-ui.icon.solid icon="{{ $payment->status['icon'] }}"/>
                                                    </span>

                                                    @if($payment->is_unknown)
                                                        (неизвестно)
                                                    @endif

                                                    @if(!empty($payment->date_plan))
                                                        {{ $payment->date_plan->format('d.m.Y') }}
                                                    @endif

                                                    @if(!empty($payment->date_fact) && !$payment->date_fact->isSameDay($payment->date_plan))
                                                        @if(!empty($payment->date_plan))
                                                            <i class="fa-light fa-arrow-right mx-1 fs-8"></i>
                                                        @endif
                                                        {{ $payment->date_fact->format('d.m.Y') }}
                                                        @if(!empty($payment->delay))
                                                            <span class="text-danger ms-1">(+ {{ tools()->num_rus($payment->delay, ['дня', 'день', 'дней'], true) }})</span>
                                                        @endif
                                                    @endif
                                                </span>
                                                <span class="text-nowrap text-end">
                                                    @if(!empty($payment->amount_plan))
                                                        {{ tools()->cost_normalize($payment->amount_plan) }} {{ $symbol }}
                                                    @endif

                                                    @if(!empty($payment->amount_fact) && $payment->amount_plan !== $payment->amount_fact)
                                                        @if(!empty($payment->amount_plan))
                                                            <i class="fa-light fa-arrow-right mx-1 fs-8"></i>
                                                        @endif
                                                        {{ tools()->cost_normalize($payment->amount_fact) }} {{ $symbol }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($spec_amount)
                                    {{ tools()->cost_normalize($spec_amount) }} {{ $symbol }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <script>
        /** Архив / возврат из архива прямо из карточки */
        window.deal_project_info_archive = function (back) {
            body_block();

            $.ajax({
                url: back
                    ? @json(route('api.deal_project.unarchive', $project))
                    : @json(route('api.deal_project.archive', $project)),
                type: 'POST',
                data: '_token=' + csrf_token(),
                dataType: 'json',
                success: function (response) {
                    body_unblock();

                    if (response.result !== 'success') {
                        toastr.error(response.message || 'Не получилось изменить проект', 'Это провал!', {progressBar: true, timeOut: 6000});
                        return;
                    }

                    toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});
                    box_close();

                    if (typeof window.deal_project_changed === 'function') window.deal_project_changed();
                    else location.reload();
                },
                error: function (xhr) {
                    body_unblock();
                    toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                }
            });
        };
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
            <span>Закрыть</span>
        </x-ui.button.default>

        <div class="d-flex gap-2">
            @if($project->is_archived)
                <x-ui.button.default btn_type="light-primary" onclick="javascript:deal_project_info_archive(true);">
                    <i class="fa-light fa-box-open me-2"></i>
                    <span>Вернуть из архива</span>
                </x-ui.button.default>
            @else
                <x-ui.button.default btn_type="light-dark" onclick="javascript:deal_project_info_archive(false);">
                    <i class="fa-light fa-box-archive me-2"></i>
                    <span>Отправить в архив</span>
                </x-ui.button.default>
            @endif

            <x-ui.a.box btn_type="info" :href="route('deal_project.box_edit', $project)">
                <i class="fa-light fa-pen me-2"></i>
                <span>Редактировать</span>
            </x-ui.a.box>
        </div>
    </div>
@endsection
