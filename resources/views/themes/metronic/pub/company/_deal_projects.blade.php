{{--
    Блок «Проекты» на странице компании (patch v48).

    Данные собирает CompanyController::dealProjects():
    $deal_projects — проекты (действующие сверху), $specs_free — спецификации
    в работе без действующего проекта, $spec_links / $spec_proposals — КП
    спецификаций, $deal_url — ссылка на сделку в Битрикс24.
--}}
@php
    $projects_active = $deal_projects->reject(fn($row) => $row['project']->is_archived);
    $projects_closed = $deal_projects->filter(fn($row) => $row['project']->is_archived);
    // строки таблицы спецификаций ниже — для ссылок-якорей
    $company_spec_ids = $company->specifications->pluck('id')->map(fn($id) => (int) $id)->all();
@endphp

<div class="card mb-5 company-projects">
    <div class="card-header d-flex justify-content-between align-items-center pe-2">
        <h3 class="m-0 d-flex align-items-center gap-2">
            <i class="fa-light fa-diagram-project text-primary"></i>
            Проекты
            <span class="badge badge-light fs-8">{{ $deal_projects->count() }}</span>
        </h3>

        @if(empty($log_state))
            <x-ui.a.box btn_type="light-primary" class="btn-sm" :href="route('deal_project.box_company', $company)">
                <i class="fa-light fa-plus me-1"></i>Создать проект
            </x-ui.a.box>
        @endif
    </div>

    {{-- подсказка: есть спецификации в работе, не попавшие ни в один действующий проект --}}
    @if($specs_free->isNotEmpty())
        <div class="cp-hint d-flex align-items-center gap-4 flex-wrap px-6 py-4 border-bottom">
            <span class="cp-hint-ico"><i class="fa-light fa-diagram-project"></i></span>
            <div class="flex-grow-1 min-w-200px">
                <div class="fw-bold fs-6">
                    {{ tools()->num_rus($specs_free->count(), ['спецификации', 'спецификация', 'спецификаций'], true) }} без проекта
                </div>
                <div class="text-muted fs-7">
                    {{ $specs_free->take(3)->map(fn($spec) => $spec->name ?: 'без названия')->implode(' · ') }}
                    @if($specs_free->count() > 3) и ещё {{ $specs_free->count() - 3 }} @endif
                </div>
            </div>
            @if(empty($log_state))
                <x-ui.a.box btn_type="primary" class="btn-sm" :href="route('deal_project.box_company', $company)">
                    <i class="fa-light fa-plus me-1"></i>Создать проект
                </x-ui.a.box>
            @endif
        </div>
    @endif

    @forelse($projects_active->merge($projects_closed) as $row)
        @php
            $project = $row['project'];
            $closed = $project->is_archived;
        @endphp

        {{-- закрытые проекты свёрнуты под действующими --}}
        @if($closed && $loop->index === $projects_active->count())
            <div class="px-6 py-3 border-top">
                <a href="javascript:void(0)" class="text-muted fw-semibold" data-bs-toggle="collapse"
                   data-bs-target="#company-projects-closed" aria-expanded="false">
                    <i class="fa-light fa-chevron-down me-1"></i>
                    Закрытые проекты: {{ $projects_closed->count() }}
                </a>
            </div>
            <div class="collapse" id="company-projects-closed">
        @endif

        <div @class(['cp-proj border-top', 'cp-closed' => $closed])>
            {{-- шапка: название, статус, пилот и срок --}}
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 px-6 pt-4 pb-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <a href="javascript:void(0)" onclick="javascript:box({href:'{{ route('deal_project.box_info', $project) }}'})"
                       class="fs-5 fw-bold text-gray-900 text-hover-primary">{{ $project->label }}</a>

                    @if($closed)
                        @php $result_color = ['success' => 'success', 'fail' => 'danger'][$project->result] ?? 'secondary'; @endphp
                        <x-ui.badge.light :type="$result_color">
                            <i class="fa-light fa-flag-checkered me-1"></i>
                            Закрыт {{ $project->archived_at->format('d.m.Y') }}{{ $project->result_label ? ': ' . $project->result_label : '' }}
                        </x-ui.badge.light>
                    @else
                        <x-ui.badge.light type="primary"><i class="fa-light fa-play me-1"></i>В работе</x-ui.badge.light>
                    @endif

                    @if($project->is_pilot)
                        <x-ui.badge.light type="info">пилот</x-ui.badge.light>
                    @endif

                    @if($project->deadline)
                        @php
                            $days = (int) now()->startOfDay()->diffInDays($project->deadline->copy()->startOfDay(), false);
                            $overdue = !$closed && $days < 0;
                        @endphp
                        <x-ui.badge.light :type="$closed ? 'secondary' : ($overdue ? 'danger' : 'warning')">
                            <i class="fa-light fa-clock me-1"></i>
                            срок {{ $project->deadline->format('d.m.Y') }}
                            @if(!$closed)
                                · {{ $overdue
                                    ? 'просрочен на ' . tools()->num_rus(-$days, ['дня', 'день', 'дней'], true)
                                    : ($days === 0 ? 'сегодня' : 'через ' . tools()->num_rus($days, ['дня', 'день', 'дней'], true)) }}
                            @endif
                        </x-ui.badge.light>
                    @endif
                </div>

                <span class="text-muted fs-8">
                    {{ $project->author?->name ?: '—' }} · {{ $project->created_at?->format('d.m.Y') }}
                </span>
            </div>

            @if($closed && !empty($project->result_comment))
                <div class="px-6 pb-2 text-gray-700 fs-7">
                    <i class="fa-light fa-comment me-1 text-muted"></i>{{ $project->result_comment }}
                </div>
            @endif

            {{-- цифры: суммы по валютам не складываются --}}
            <div class="d-flex flex-wrap gap-6 px-6 pb-3 text-gray-700 fs-7">
                <span>Сумма
                    <b class="text-gray-900 text-nowrap">
                        @forelse($row['sums'] as $sum)
                            {{ tools()->cost_normalize(round($sum['amount'])) }} {{ $sum['symbol'] }}@if(!$loop->last) + @endif
                        @empty
                            —
                        @endforelse
                    </b>
                </span>
                <span>Спецификаций <b class="text-gray-900">{{ $row['specs']->count() }}</b></span>
                <span>Оплачено
                    <b class="text-gray-900 text-nowrap">
                        @forelse($row['paid'] as $sum)
                            {{ tools()->cost_normalize(round($sum['amount'])) }} {{ $sum['symbol'] }}@if(!$loop->last) + @endif
                        @empty
                            —
                        @endforelse
                    </b>
                </span>
                <span>Ближайший платёж
                    @if($row['next_payment'])
                        <b @class(['text-nowrap', 'text-danger' => $row['next_payment']['date']->lt(now()->startOfDay()), 'text-gray-900' => !$row['next_payment']['date']->lt(now()->startOfDay())])>
                            {{ $row['next_payment']['date']->format('d.m.Y') }}
                            @if($row['next_payment']['amount'])
                                · {{ tools()->cost_normalize(round($row['next_payment']['amount'])) }} {{ $row['next_payment']['symbol'] }}
                            @endif
                        </b>
                    @else
                        <b class="text-gray-900">—</b>
                    @endif
                </span>
            </div>

            {{-- прогресс оплат: своя полоса на каждую валюту --}}
            @foreach($row['sums'] as $currency => $sum)
                @php
                    $paid_amount = $row['paid'][$currency]['amount'] ?? 0;
                    $percent = $sum['amount'] > 0 ? min(100, (int) round($paid_amount / $sum['amount'] * 100)) : 0;
                @endphp
                <div class="px-6 pb-3">
                    <div class="d-flex justify-content-between fs-8 text-muted mb-1">
                        <span>Оплаты{{ count($row['sums']) > 1 ? ', ' . $sum['symbol'] : '' }}</span>
                        <span>{{ $percent }} %</span>
                    </div>
                    <div class="progress h-6px bg-light">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%"
                             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            @endforeach

            {{-- три колонки: спецификации, КП и конфигурации, сделки Битрикса --}}
            @php
                $project_spec_ids = $row['specs']->pluck('id')->map(fn($id) => (int) $id)->all();
                $project_proposals = $spec_links
                    ->filter(fn($link) => in_array((int) $link->contract_specification_id, $project_spec_ids, true))
                    ->map(fn($link) => $spec_proposals->get($link->proposal_group))
                    ->filter()
                    ->unique('group')
                    ->values();
                $project_configs = $row['specs']->flatMap(fn($spec) => $spec->project_configurations)->unique('id')->values();
            @endphp
            <div class="row g-0 border-top cp-links">
                <div class="col-12 col-md-4 px-6 py-3">
                    <div class="cp-h4">Спецификации</div>
                    @forelse($row['specs'] as $spec)
                        @php $status = \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::tryFrom((string) $spec->status)?->data(); @endphp
                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                            <div class="min-w-0">
                                @if(in_array((int) $spec->id, $company_spec_ids, true))
                                    <a href="#spec-row-{{ $spec->id }}" class="fw-semibold">{{ $spec->name ?: 'без названия' }}</a>
                                @else
                                    <span class="fw-semibold">{{ $spec->name ?: 'без названия' }}</span>
                                @endif
                                <div class="fs-8 text-muted">
                                    @if($spec->contract)
                                        {{ \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $spec->contract->type)?->data()['label'] ?? '' }}
                                        · {{ $spec->contract->number ?: 'б/н' }}
                                    @endif
                                </div>
                            </div>
                            @if($status)
                                <x-ui.badge.light :type="$status['color']" class="fs-9">{{ $status['label'] }}</x-ui.badge.light>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted fs-7">нет</span>
                    @endforelse
                </div>

                <div class="col-12 col-md-4 px-6 py-3">
                    <div class="cp-h4">КП и конфигурации</div>
                    @foreach($project_proposals as $linked)
                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                            <div class="min-w-0">
                                <a href="{{ route('proposal.detail', [$linked, $linked->iteration]) }}" class="fw-bold">{{ $linked->number ?: 'б/н' }}</a>
                                @if(!empty($linked->name))
                                    <div class="fs-8 text-muted cp-clamp" title="{{ $linked->name }}">{{ $linked->name }}</div>
                                @endif
                            </div>
                            <x-proposal.status :proposal="$linked"/>
                        </div>
                    @endforeach

                    @foreach($project_configs as $config)
                        <div class="mb-2">
                            <code class="fs-7">{{ $config->number }}</code>
                            @if(!empty($config->comment))
                                <div class="fs-8 text-muted">{{ $config->comment }}</div>
                            @endif
                        </div>
                    @endforeach

                    @if($project_proposals->isEmpty() && $project_configs->isEmpty())
                        <span class="text-muted fs-7">нет</span>
                    @endif
                </div>

                <div class="col-12 col-md-4 px-6 py-3">
                    <div class="cp-h4">Сделки Битрикс24</div>
                    @forelse($row['deals'] as $item)
                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                            <div class="min-w-0">
                                <a href="{{ $deal_url($item['id']) }}" target="_blank" rel="noopener">
                                    #{{ $item['id'] }} <i class="fa-light fa-arrow-up-right-from-square fs-9"></i>
                                </a>
                                @if($item['deal'])
                                    <div class="fs-8 text-muted cp-clamp" title="{{ $item['deal']->title }}">{{ $item['deal']->title }}</div>
                                @endif
                            </div>
                            @if($item['deal'] && (float) $item['deal']->opportunity > 0)
                                <span class="text-nowrap fs-7">{{ tools()->cost_normalize(round($item['deal']->opportunity)) }} {{ $item['deal']->currency_id }}</span>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted fs-7">нет</span>
                    @endforelse
                </div>
            </div>

            {{-- действия --}}
            @if(empty($log_state))
                <div class="d-flex justify-content-end flex-wrap gap-2 px-6 py-3 border-top">
                    @if($closed)
                        <button type="button" class="btn btn-sm btn-light-primary" onclick="company_project_unarchive({{ $project->id }})">
                            <i class="fa-light fa-rotate-left me-1"></i>Вернуть в работу
                        </button>
                    @else
                        <x-ui.a.box btn_type="light" class="btn-sm" :href="route('deal_project.box_edit', $project)">
                            <i class="fa-light fa-pen-to-square me-1"></i>Изменить
                        </x-ui.a.box>
                        <x-ui.a.box btn_type="light-danger" class="btn-sm" :href="route('deal_project.box_close', $project)">
                            <i class="fa-light fa-flag-checkered me-1"></i>Закрыть проект
                        </x-ui.a.box>
                    @endif
                </div>
            @endif
        </div>

        @if($closed && $loop->last)
            </div>{{-- /#company-projects-closed --}}
        @endif
    @empty
        @if($specs_free->isEmpty())
            <div class="px-6 py-4 text-muted">Проектов у компании пока нет.</div>
        @endif
    @endforelse
</div>

@section('styles')
    @parent
    <style>
        /* patch v48: блок «Проекты» на странице компании */
        .company-projects .cp-hint-ico {
            width: 40px; height: 40px; border-radius: 10px; flex: none;
            display: grid; place-items: center; font-size: 17px;
            background: var(--bs-primary-light); color: var(--bs-primary);
        }
        .company-projects .cp-closed { opacity: .7; }
        .company-projects .cp-closed:hover { opacity: 1; }
        .company-projects .cp-h4 {
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .05em; color: var(--bs-gray-500); margin-bottom: 8px;
        }
        /* колонки считаем по ширине карточки, а не экрана: правая колонка страницы узкая */
        .company-projects { container-type: inline-size; }
        .company-projects .cp-links > div + div { border-left: 1px solid var(--bs-border-color); }
        @container (max-width: 620px) {
            .company-projects .cp-links > div { width: 100%; }
            .company-projects .cp-links > div + div { border-left: 0; border-top: 1px solid var(--bs-border-color); }
        }
        .company-projects .min-w-0 { min-width: 0; }
        .company-projects .cp-clamp {
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
    </style>
@endsection

@section('js')
    @parent
    <script>
        /** Проект поменяли в попапе (создали, изменили, закрыли) — перерисовать страницу */
        window.deal_project_changed = function () {
            location.reload();
        };

        /** Предупреждение, оставленное попапом перед перезагрузкой (например, пропущенные сделки) */
        $(function () {
            var warning = null;
            try {
                warning = sessionStorage.getItem('deal_project_warning');
                sessionStorage.removeItem('deal_project_warning');
            } catch (e) {}
            if (warning) toastr.warning(warning, 'Внимание', {progressBar: true, timeOut: 15000});
        });

        /** Вернуть закрытый проект в работу */
        window.company_project_unarchive = function (id) {
            body_block();

            $.ajax({
                url: @json(route('api.deal_project.unarchive', ['project' => '__id__'])).replace('__id__', id),
                type: 'POST',
                data: '_token=' + csrf_token(),
                dataType: 'json',
                success: function (response) {
                    body_unblock();

                    if (response.result !== 'success') {
                        toastr.error(response.message || 'Не получилось вернуть проект в работу', 'Это провал!', {progressBar: true, timeOut: 6000});
                        return;
                    }

                    toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});
                    location.reload();
                },
                error: function (xhr) {
                    body_unblock();
                    toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                }
            });
        };
    </script>
@endsection
