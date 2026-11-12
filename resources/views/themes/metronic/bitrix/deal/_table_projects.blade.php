{{--
    Шапка и строки таблицы реестра на вкладках «Проекты» и «Архив проектов» (patch v49).

    Одна строка — один проект, его сделки списком внутри строки. Проект попадает
    в таблицу, если хотя бы одна его сделка прошла фильтр и поиск; в строке —
    все сделки проекта, прошедшие отбор не выделяются.

    Подключается из _table внутри <table>: сама таблица, её атрибуты
    (сортировка по умолчанию — `id` по убыванию) и скрипты — там.

    Сортировка колонок — по ключу data-key ячейки (dealKeySort из _table):
    проект — по id проекта, сделки — по самой свежей сделке, сумма — по итогу.

    Параметры:
      $items    — строки проектов из CrmDealRegistryService::projectRows();
      $semantic — цвета плашек стадий (из _table);
      $symbols  — значки валют (из _table).
--}}
@php
    /** Инициалы менеджера: «[83] Александр Максимов» → «АМ» */
    $initials = fn($name) => collect(preg_split('/\s+/u', trim(\Illuminate\Support\Str::afterLast((string) $name, ']'))))
        ->filter()->take(2)
        ->map(fn($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');

    /** Имя менеджера без кода Битрикса */
    $clean = fn($name) => trim(\Illuminate\Support\Str::afterLast((string) $name, ']')) ?: (string) $name;
@endphp
<thead>
<tr>
    <th data-field="id" data-align="center" data-width="150"
        data-sortable="true" data-sorter="dealKeySort">Проект</th>

    <th data-field="deals" data-align="left"
        data-sortable="true" data-sorter="dealKeySort">Сделки</th>

    <th data-field="manager" data-align="center" data-width="60"
        data-sortable="true" data-sorter="dealKeySort">Менеджер</th>

    <th data-field="partner" data-align="left" data-width="125"
        data-sortable="true" data-sorter="dealKeySort">Партнёр и заказчик</th>

    <th data-field="country" data-align="center" data-width="90"
        data-sortable="true" data-sorter="dealKeySort">Страна</th>

    <th data-field="amount" data-align="right" data-width="120"
        data-sortable="true" data-sorter="dealKeySort">Сумма</th>

    <th data-field="proposal" data-align="center" data-width="180"
        data-sortable="true" data-sorter="dealKeySort">КП</th>
</tr>
</thead>
<tbody>
@foreach($items as $item)
    @php
        $project = $item->project;
    @endphp
    <tr>
        {{-- проект: плашка открывает карточку проекта (там же архив и итог), рядом — правка и закрытие --}}
        <td>
            <div class="cell" data-key="{{ $item->id }}">
                <div class="d-flex flex-column align-items-center gap-1">
                    <a href="javascript:void(0)"
                       onclick="javascript:box({href:'{{ route('deal_project.box_info', $project) }}'})"
                       @class(["fs-7 badge d-inline-flex align-items-center text-decoration-none",
                            "badge-light-secondary" => $project->is_archived,
                            "badge-light-warning bg-hover-warning text-hover-white" => !$project->is_archived && $project->is_pilot,
                            "badge-light-info bg-hover-info text-hover-white" => !$project->is_archived && !$project->is_pilot,
                        ])
                       title="{{ $project->is_archived ? 'Проект в архиве' : 'Открыть карточку проекта' }}">
                        <i class="fa-light fa-diagram-project me-2"></i>
                        {{ $project->date_start?->format('d.m.Y') ?? 'проект' }}
                    </a>

                    <span class="fs-8 text-muted text-nowrap">
                        №{{ $project->id }}{{ $project->is_pilot ? ' · пилот' : '' }}
                    </span>

                    @if($project->is_pilot && $project->deadline)
                        <span class="fs-8 text-muted text-nowrap" title="Срок пилота">до {{ $project->deadline->format('d.m.Y') }}</span>
                    @endif

                    @if($project->is_archived)
                        <span class="fs-8 text-muted text-nowrap">
                            закрыт {{ $project->archived_at?->format('d.m.Y') }}
                        </span>
                        @if($project->result_label)
                            <span @class(['fs-8 badge', 'badge-light-success' => $project->result === \App\Modules\Pub\DealProject\Models\DealProject::RESULT_SUCCESS,
                                          'badge-light-danger' => $project->result !== \App\Modules\Pub\DealProject\Models\DealProject::RESULT_SUCCESS])>
                                {{ $project->result_label }}
                            </span>
                        @endif
                    @endif

                    <div class="d-flex align-items-center gap-3 mt-1">
                        <a href="javascript:void(0)" class="text-muted text-hover-primary"
                           onclick="javascript:box({href:'{{ route('deal_project.box_edit', $project) }}'})"
                           title="Редактировать проект">
                            <i class="fa-light fa-pen fs-8"></i>
                        </a>
                        @unless($project->is_archived)
                            <a href="javascript:void(0)" class="text-muted text-hover-danger"
                               onclick="javascript:box({href:'{{ route('deal_project.box_close', $project) }}'})"
                               title="Закрыть проект с итогом (в архив)">
                                <i class="fa-light fa-box-archive fs-8"></i>
                            </a>
                        @endunless
                    </div>
                </div>
            </div>
        </td>

        {{-- сделки проекта: ID и название ведут в Битрикс, стадия, сумма; справа — попапы КП и проекта сделки --}}
        <td>
            <div class="cell d-flex flex-column gap-1" data-key="{{ (int) $item->deals->max('id') }}">
                @foreach($item->deals as $deal)
                    @php
                        $deal_proposals = $deal->proposals ?? collect();
                        $currency = $symbols[$deal->currency_id] ?? $deal->currency_id;
                    @endphp
                    <div class="d-flex align-items-center gap-2 fs-7">
                        <a href="{{ $deal->deal_url }}" target="_blank" class="fw-semibold text-nowrap" title="Открыть сделку в Битрикс24">
                            {{ $deal->id }}
                        </a>

                        <a href="{{ $deal->deal_url }}" target="_blank" class="text-gray-800 text-hover-primary text-truncate"
                           style="max-width: 280px" title="{{ $deal->title }}">
                            {{ $deal->title ?: 'без названия' }}
                        </a>

                        <span class="badge badge-light-{{ $semantic[$deal->stage_semantic_id] ?? 'dark' }} fs-8 text-nowrap"
                              title="{{ $deal->date_create ? 'Создана ' . \Carbon\Carbon::parse($deal->date_create)->format('d.m.Y') : '' }}">
                            {{ $deal->stage_name ?: '—' }}
                        </span>

                        <span @class(['ms-auto text-nowrap', 'text-success' => $deal->currency_id !== 'RUB'])>
                            @if((float) $deal->opportunity > 0)
                                {{ tools()->cost_normalize(round($deal->opportunity)) }} {{ $currency }}
                            @else
                                -
                            @endif
                        </span>

                        {{-- привязка КП к этой сделке — тот же попап, что в колонке «КП» вкладки сделок --}}
                        <a href="javascript:void(0)" class="text-muted text-hover-primary"
                           onclick="javascript:box({href:'{{ route('crm-deal.box.proposal', $deal->id) }}'})"
                           title="{{ $deal_proposals->isNotEmpty() ? 'Изменить привязку к КП' : 'Привязать КП к сделке' }}">
                            <i class="fa-light {{ $deal_proposals->isNotEmpty() ? 'fa-link' : 'fa-link-slash' }} fs-8"></i>
                        </a>

                        {{-- проект сделки: открепить или перенести — попап из колонки «Проект» вкладки сделок --}}
                        <a href="javascript:void(0)" class="text-muted text-hover-primary"
                           onclick="javascript:box({href:'{{ route('deal_project.box_form', $deal->id) }}'})"
                           title="Сделка в проекте: открепить">
                            <i class="fa-light fa-diagram-project fs-8"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </td>

        {{-- менеджеры сделок без повторов: инициалы, полные имена в подсказке --}}
        <td>
            <div class="cell fs-7 fw-semibold d-flex flex-column align-items-center" data-key="{{ $clean($item->managers->first()) }}">
                @forelse($item->managers as $manager)
                    <span class="text-nowrap" title="{{ $clean($manager) }}">{{ $initials($manager) ?: '—' }}</span>
                @empty
                    —
                @endforelse
            </div>
        </td>

        {{-- партнёры и заказчики сделок без повторов, как в строке сделки --}}
        <td>
            <div class="cell d-flex flex-column align-items-start gap-1" style="max-width: 125px"
                 data-key="{{ $item->companies->first() ?? '' }}">
                @forelse($item->companies as $company)
                    <x-ui.badge.light type="info" class="d-block mw-100 text-truncate text-start text-info-700 bg-hover-info text-hover-white"
                                      title="{{ $company }}">
                        {{ $company }}
                    </x-ui.badge.light>
                @empty
                    <span class="text-muted fs-8">партнёр не указан</span>
                @endforelse

                @foreach($item->customers as $customer)
                    <x-ui.badge.light type="primary" class="d-block mw-100 text-truncate text-start text-primary-700 bg-hover-primary text-hover-white"
                                      title="{{ $customer }}">
                        {{ $customer }}
                    </x-ui.badge.light>
                @endforeach
            </div>
        </td>

        <td>
            <div class="cell fs-7" data-key="{{ $item->countries->first() ?? '' }}">
                {!! $item->countries->map(fn($country) => e($country))->implode('<br>') !!}
            </div>
        </td>

        {{-- сумма сделок проекта по валютам; ключ сортировки — итог без учёта курса --}}
        <td>
            <div class="cell" data-key="{{ (int) round($item->amounts->sum()) }}">
                @forelse($item->amounts as $currency_id => $sum)
                    <div @class(['text-nowrap', 'text-success' => $currency_id !== 'RUB'])>
                        {{ tools()->cost_normalize(round($sum)) }} {{ $symbols[$currency_id] ?? $currency_id }}
                    </div>
                @empty
                    -
                @endforelse
            </div>
        </td>

        {{-- КП всех сделок проекта без повторов; привязка меняется у каждой сделки в колонке «Сделки» --}}
        <td>
            <div class="cell" data-key="{{ $item->proposals->count() }}">
                <div class="d-flex justify-content-center align-items-center flex-wrap gap-1">
                    @forelse($item->proposals as $proposal)
                        <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}"
                           class="fs-7 badge badge-light-success d-inline-flex align-items-center text-decoration-none bg-hover-success-700 text-hover-white-900"
                           title="{{ $proposal->name }}">
                            {{ $proposal->number ?: 'КП' }}
                        </a>
                    @empty
                        <span class="text-muted fs-8">нет КП</span>
                    @endforelse
                </div>
            </div>
        </td>
    </tr>
@endforeach
</tbody>
