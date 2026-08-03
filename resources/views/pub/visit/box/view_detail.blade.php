@extends('components.box.box-static-large')

@section('title')
    <div class="d-flex justify-content-start align-items-center">
        <span>{{ $title }}</span>
        <x-ui.badge.default class="ms-3" :type="\App\Modules\Pub\Visit\Models\Visit::STATUS_DATA[$visit->status]['color']['button']">{{ \App\Modules\Pub\Visit\Models\Visit::STATUS_DATA[$visit->status]['name'] }}</x-ui.badge.default>
    </div>
@endsection
@section('body')

    <style>
        #asset_visit .select2-container {
            width: 100% !important;
        }

        #asset_visit .select2-container--default .select2-selection--multiple {
            border-color: #e9ecef !important;
        }

        #asset_visit .table td {
            padding: 5px 10px !important;
            font-size: 13px;
        }

        #asset_visit .card-body .row label + div {
            padding-top: 6px !important;
        }

        #asset_visit .col-sm-8:has(input#date_fact) {
            padding-top: 0 !important;
        }

        #asset_visit input#date_fact {
            font-size: 16px !important;
            font-weight: 300 !important;
            margin-left: -4px;
            width: 200px;
        }
    </style>
    <div id="asset_visit">
        <form class="form-horizontal" id="asset_visit">
            <div class="card-body">
                <div class="mb-1 row">
                    <label for="fname" class="col-sm-4 text-end control-label col-form-label">Адрес</label>
                    <div class="col-sm-8 pt-1 font-16 ps-4">
                        {{ $visit->order_task_address->address }}
                    </div>
                </div>
                @if(!empty($visit->plan_visit))
                    <div class="mb-1 row">
                        <label for="fname" class="col-sm-4 text-end control-label col-form-label">По плану в календаре выездов</label>
                        <div class="col-sm-8 pt-1 font-16 ps-4">
                            {{ _date($visit->plan_visit->date) }}
                        </div>
                    </div>
                @endif
                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">
                        {{ $visit->users->count() == 1 ? "Пробоотборщик" : "Пробоотборщики" }}
                    </label>
                    <div class="col-sm-8 ps-1">
                        <ol>
                            @foreach($visit->users as $user)
                                <li class=" pt-1 font-16">
                                    {{ $user->full_name }}
                                    @if($user->pivot['as_admin'])
                                        <span class="ms-3 text-warning">
                                                    <x-ui.icon.light icon="fa-unlock-keyhole"></x-ui.icon.light> админ
                                                </span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">Предполагаемая дата
                        отбора</label>
                    <div class="col-sm-8 pt-1 font-16 ps-4">
                        {{ _date($visit->plan_visit_at) }}
                    </div>
                </div>
                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">Фактическая дата
                        отбора</label>
                    <div class="col-sm-8 pt-1 font-16 ps-4">
                        {{ _date($visit->fact_visit_at) }}
                    </div>
                </div>
                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">Номер акта</label>
                    <div class="col-sm-8 pt-1 font-16 ps-4">
                        {!! _docnumber($visit->number->number) !!}
                    </div>
                </div>

            </div>

            <ul class="nav nav-pills mb-2" role="tablist">
                <li class="d-flex">
                   <div class="flex-grow-1 d-flex align-items-center me-2">
                       Группировка:
                   </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#view_measures" role="tab" aria-selected="true">
                        <strong>
                            <x-ui.icon.light icon="fa-vial" class="me-1"></x-ui.icon.light>
                            По измерению
                        </strong>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#view_containers" role="tab" aria-selected="false">
                        <strong>
                            <x-ui.icon.light icon="fa-tag" class="me-1"></x-ui.icon.light>
                            По маркировке
                        </strong>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="view_measures" role="tabpanel">
                    <div class="points table-responsive">
                        <table id="measures" class="table customize-table mb-0 v-middle border-1" border="1">
                            @foreach($points as $point)
                                <tbody point="{{ $point->id }}">
                                <tr>
                                    <td colspan="10" class="p-2 bg-light-secondary">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="m-0 font-12 fw-bold d-flex align-items-center">
                                                <x-ui.icon.light icon="fa-map-pin" class="me-2"></x-ui.icon.light>
                                                <span>{{ $point->name }}</span>
                                            </h6>

                                            <div>
                                                <x-ui.badge.default type="danger">{{ $point->number }}</x-ui.badge.default>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td/>
                                    <td/>
                                    {{--                            <td class="text-center font-10 fw-bold">Всего по ТЗ</td>--}}
                                    <td class="text-center font-10 fw-bold">План</td>
                                    <td class="text-center font-10 fw-bold" width="80">Кол-во</td>
                                    <td class="text-start font-10 fw-bold">Маркировка</td>
                                    <td class="text-start font-10 fw-bold">Автор</td>
                                    <td class="text-start font-10 fw-bold">Сдана в лабораторию</td>
                                </tr>
                                @foreach($measures_data[$point->id] as $visit_order_task_measure)
                                    @for($row_i = 0; $row_i < $count = max(1, ($containers[$point->id][$visit_order_task_measure->id] ?? collect())->count()); $row_i++)
                                        @php
                                            $data = $containers[$point->id][$visit_order_task_measure->id][$row_i] ?? null;
                                        @endphp
                                        <tr>
                                            @if($row_i == 0)
                                                <td style="padding-right: 0!important;" rowspan="{{ $count }}">
                                                    @if($visit_order_task_measure->isFinished)
                                                        <x-ui.icon.solid class="text-success" icon="fa-check"></x-ui.icon.solid>
                                                    @endif
                                                </td>
                                                <td rowspan="{{ $count }}">
                                                    {{ $visit_order_task_measure->order_task_measure->measure->name }}
                                                </td>
                                                <td class="text-center" rowspan="{{ $count }}">
        {{--                                                    <span  @class(['counter', 'cursor-pointer', 'fw-bold text-success' => ($visit_order_task_measure->assetted_count >= $measure->count ?? 0)])>--}}
        {{--                                                        {{ $container['count'] ?? 0 }}--}}
        {{--                                                    </span>--}}
                                                    {{ $visit_order_task_measure->count }}
                                                </td>
                                            @endif
                                            @if(!empty($data))
                                                <td class="text-center">
                                                    {{ $data['count'] }}
                                                </td>
                                                <td class="text-start">
                                                    <code>{{ $data['container']->mark }}</code>
                                                </td>
                                                <td>
                                                    {{ $data['container']->creator->last_name }}
                                                </td>
                                                <td>
                                                    {{ $data['container']->asset_samples_at->format('d.m.Y H:i') }}
                                                </td>
                                            @else
                                                <td class="text-center">-</td>
                                                <td class="text-start">-</td>
                                                <td>-</td>
                                                <td>-</td>
                                            @endif
                                        </tr>
                                    @endfor
                                @endforeach
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                </div>


                <div class="tab-pane" id="view_containers" role="tabpanel">
                    <div class="points table-responsive">
                        <table id="measures" class="table customize-table mb-0 v-middle border-1" border="1">
                            @foreach($points as $point)
                                <tbody point="{{ $point->id }}">
                                <tr>
                                    <td colspan="10" class="p-2 bg-light-secondary">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="m-0 font-12 fw-bold d-flex align-items-center">
                                                <x-ui.icon.light icon="fa-map-pin" class="me-2"></x-ui.icon.light>
                                                <span>{{ $point->name }}</span>
                                            </h6>

                                            <div>
                                                <x-ui.badge.default type="danger">{{ $point->number }}</x-ui.badge.default>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @forelse($point->containers->where('visit_id', $visit->id) as $container)
                                    <tr>
                                        <td colspan="2">
                                            <mark><code>{{ $container->mark }}</code></mark>
                                        </td>
{{--                                        <td class="text-center font-10 fw-bold">План</td>--}}
                                        <td class="text-center font-10 fw-bold" width="80">Кол-во</td>
                                        <td class="text-start font-10 fw-bold">Автор</td>
                                        <td class="text-start font-10 fw-bold">Сдана&nbsp;в лабораторию</td>
                                    </tr>
                                    @foreach($container->samples as $sample)
                                        @php
                                            $votm = $sample->visit_order_task_measure;
                                            $measure = $votm->order_task_measure;
                                        @endphp
                                        <tr>
                                            <td width="10">
                                                @if($votm->finished_count >= $measure->count ?? 0)
                                                    <x-ui.icon.solid class="text-success" icon="fa-check"></x-ui.icon.solid>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $measure->measure->name }}
                                            </td>
{{--                                            <td class="text-center">--}}
{{--                                                {{ $votm->count }}--}}
{{--                                            </td>--}}
                                            <td class="text-center">
                                                {{ $sample->count }}
                                            </td>
                                            <td>
                                                {{ $container->creator->last_name }}
                                            </td>
                                            <td>
                                                {{ $container->asset_samples_at->format('d.m.Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-danger fw-bold">Нет созданных контейнеров</td>
                                    </tr>
                                @endforelse

                                </tbody>
                            @endforeach
                        </table>
                    </div>
                </div>


            </div>
        </form>
    </div>

@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        @if($visit->canEdit())
            <x-ui.a.default href="{{ route('visit.edit', $visit) }}" id="btn_submit" btn_type="warning" type="warning">
                <x-ui.icon.solid icon="fa-edit"></x-ui.icon.solid>
                <span>Редактировать</span>
            </x-ui.a.default>
        @else
            <div></div>
        @endif

        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>
    </div>
@endsection
