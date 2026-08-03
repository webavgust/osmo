@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>
    <link rel="stylesheet" href="/dist/modules/daterangepicker/daterangepicker.css"/>
    <style>
        .border-left-1 {
            border-left-width: 1px;
        }

        tr.tr_max > :nth-child(n+4),
        tr.tr_max_sub > :nth-child(n+0) {
            background: #f4fff5;
        }
    </style>
@endsection


@section('content')
    @php
        $can_save = false;
    @endphp
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @if(!empty($filter['act']))
                    <x-ui.notification.light type="secondary" class="mb-4 p-2 bg-white d-flex justify-content-between align-items-center">
                        <span>
                            <x-ui.icon.light icon="fa-filter" class="mx-1"></x-ui.icon.light>
                            <span>
                                Измерения отфильтрованы по акту №{!! _docnumber($filter['act']->number->id) !!}

                                <x-visit.status :visit="$filter['act']" class="ms-2"></x-visit.status>
                            </span>
                        </span>

                        <x-ui.a.default btn_type="danger" href="{{ route('visit.task', $task) }}">
                            Отменить
                        </x-ui.a.default>

                    </x-ui.notification.light>
                @endif
                @foreach($available['objects'] as $type => $objects)
                    @continue($objects->isEmpty())

                    <div class="mb-2 d-flex justify-content-between">
                        <h3>
                            <x-ui.icon.solid icon="fa-up-right" class="me-1"></x-ui.icon.solid>
                            Направление {{ $type == "A" ? "А" : "Б" }}
                        </h3>
                    </div>

                    <form id="asset_finish">
                        <table id="measures" class="table customize-table mb-0 v-middle border-1 bg-white" border="1">
                            @foreach($objects as $object)
                                <tr>
                                    <td colspan="11" class="p-2 bg-white">
                                            <h6 class="mb-0">
                                                <a href="{{ route('order_task_object.detail', $object) }}" class="d-flex">
                                                    <x-ui.icon.regular icon="fa-industry" class="me-2"></x-ui.icon.regular>
                                                    <span class="d-flex flex-column">
                                                            <span>{{ $object->name }}</span>
                                                            <span class="text-secondary mt-1 font-10" role="alert"
                                                                  style="opacity: .5">
                                                               {{ $object->lab_object?->chain_name }}
                                                            </span>
                                                      </span>
                                                </a>
                                            </h6>
                                    </td>
                                </tr>
                                @foreach($object->addresses as $address)
                                    @continue(empty($available['addresses'][$address->id]))
                                    <tr>
                                        <td width="30"></td>
                                        <td colspan="10" class="p-2">
                                            <h6 class="d-flex mb-0">
                                                <x-ui.icon.regular icon="fa-location-dot" class="me-2"></x-ui.icon.regular>
                                                <span class="d-flex flex-column">
                                                        <span>{{ $address->address }}</span>
                                                  </span>
                                            </h6>
                                        </td>
                                    </tr>
                                    @foreach($address->points as $point)
                                        @continue(empty($available['points'][$point->id]))
                                        <tr>
                                            <td width="30"></td>
                                            <td width="30"></td>
                                            <td colspan="9" class="p-2">
                                                <h6 class="d-flex mb-0">
                                                    <x-ui.icon.solid icon="fa-map-pin"
                                                                     class="me-2"></x-ui.icon.solid>
                                                    <span class="d-flex flex-column">
                                                            <span> {{ $point->name  }}</span>
                                                      </span>
                                                </h6>
                                            </td>
                                        </tr>


                                        @foreach($points_measures[$point->id] as $measure_id => $acts_measures)
                                            <tr>
                                                <td width="30"></td>
                                                <td width="30"></td>
                                                <td width="30"></td>
                                                <td colspan="10" class="p-2 bg-light-primary text-primary fw-bold">
                                                        {{ $measures[$measure_id]->name }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="30"></td>
                                                <td width="30"></td>
                                                <td width="30"></td>
                                                <th class="p-2 border-left-1">Акт</th>
                                                <th class="p-2 border-left-1 text-center">Дата отбора</th>
                                                <th class="p-2 border-left-1 text-center">По плану</th>
                                                <th class="p-2 border-left-1">Маркировка</th>
                                                <th class="p-2 border-left-1">Отборщик</th>
                                                <th class="p-2 border-left-1 text-center">Отобрал</th>
                                                <th class="p-2 border-left-1 text-center">Передал</th>
                                                <th class="p-2 border-left-1">Обработка</th>
                                            </tr>

                                            @foreach($acts_measures as $acts)
                                                @foreach($acts as $i => $act)

                                                    @php
                                                        $max = $act->max_asset;
                                                        if($max > 0)
                                                            $can_save = true;
                                                        $self = $act->self_asset;
                                                    @endphp

                                                    <tr @class(['tr_max' => !$max && $i == 0, 'tr_max_sub' => !$max && $i > 0])>
                                                        @if($i == 0)
                                                            <td rowspan="{{ $acts->count()  }}"></td>
                                                            <td rowspan="{{ $acts->count()  }}"></td>
                                                            <td rowspan="{{ $acts->count()  }}">
                                                                @unless($max)
                                                                    <x-ui.icon.solid icon="fa-check-double" class="text-success"></x-ui.icon.solid>
                                                                @endunless
                                                            </td>
                                                            <td rowspan="{{ $acts->count()  }}" class="border-left-1 p-1 font-12 align-text-top">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <x-ui.a.box class="p-0" href="{{ route('visit.box_view_detail', $act->visit_order_task_measure->visit) }}">
                                                                        <span class="font-monospace font-14">{!! _docnumber($act->visit_order_task_measure->visit->number->id) !!}</span>
                                                                    </x-ui.a.box>

                                                                    @if(empty($filter['act']))
                                                                        <a href="{{ route('visit.task', $task) }}?act={{ $act->visit_order_task_measure->visit->number->id }}" class="text-light-secondary">
                                                                            <x-ui.icon.solid icon="fa-filter"></x-ui.icon.solid>
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <th rowspan="{{ $acts->count() }}" class="border-left-1 p-1 font-12 text-center align-text-top">
                                                                {{ _date($act->visit_order_task_measure->visit->fact_visit_at) }}
                                                            </th>
                                                            <th rowspan="{{ $acts->count() }}" class="border-left-1 p-1 font-12 text-center align-text-top">
                                                                {{ $act->visit_order_task_measure->count }}
                                                            </th>
                                                        @endif


                                                        @if($i == 0)
                                                            <th rowspan="{{ $acts->count() }}" class="border-left-1 p-1 font-12 align-text-top">
                                                                @if(!empty($act->visit_order_task_measure->mark))
                                                                    <mark class="text-danger">{{ $act->visit_order_task_measure->mark }}</mark>
                                                                @endif
                                                            </th>
                                                        @endif

                                                        <th class="border-left-1 p-1 font-12 align-text-top">
                                                            {{ $act->user->last_name }}
                                                        </th>
                                                        <th class="border-left-1 p-1 font-12 text-center align-text-top">
                                                            {{ $act->count }}
                                                        </th>
                                                        <th class="border-left-1 p-1 font-12 text-center align-text-top">
                                                            {{ $act->asset_samples_at->format('d.m H:i') }}
                                                        </th>
                                                        <th class="border-left-1 p-1 font-12 align-text-top">

                                                            @foreach($act->other_assets ?? [] as $asset)
                                                                <div>
                                                                    <x-ui.badge.light :type="$asset->is_finished ? 'success' : 'warning'" class="mb-1">
                                                                        {{ $asset->count }}
                                                                        <x-ui.icon.light icon="fa-chevrons-left" class="mx-1 font-10"></x-ui.icon.light>
                                                                        {{ $asset->user->last_name }}
                                                                    </x-ui.badge.light>
                                                                    @if($asset->is_finished)
                                                                        <x-ui.badge.light type="success" class="mb-1">
                                                                            {{ $asset->updated_at->format('d.m H:i') }}
                                                                        </x-ui.badge.light>
                                                                    @endif
                                                                </div>
                                                            @endforeach


                                                            @if(!$act->canAssetByUser())
                                                                <span class="text-danger">
                                                                    <x-ui.icon.solid icon="fa-close" class="me-1"></x-ui.icon.solid>
                                                                    Нет доступа
                                                                </span>
                                                            @elseif($max > 0)
                                                                <div class="form-group d-flex align-items-center m-0 @if($act->other_assets->isNotEmpty()) mt-1 @endif">
                                                                    <input name="count[{{ $act->id }}]" type="number"
                                                                           class="form-control text-center p-0 font-12 count_inp" min="0"
                                                                           max="{{ $max }}"
                                                                           value="{{ !$max ? 0 : $self->count ?? 0 }}"
                                                                           style="width: 50px"
                                                                           @if(!$max) readonly @endif
                                                                    >
                                                                </div>
                                                            @endif
                                                        </th>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                    <tr>
                                        <td colspan="11" class="p-1"></td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </table>
                    </form>
                @endforeach
            </div>
        </div>
        @if($can_save)
            <div class="text-end">
    {{--            @if(!empty($filter['act']))--}}
                    <x-ui.button.default id="btn_draft" btn_type="secondary" onclick="javascript:draft();" disabled>
                        <x-ui.icon.duotone icon="fa-ruler" class="me-1"></x-ui.icon.duotone>
                        <span>Сохранить как черновик</span>
                    </x-ui.button.default>

                    <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:finish();" disabled>
                        <x-ui.icon.duotone icon="fa-save" class="me-1"></x-ui.icon.duotone>
                        <span>Зарегистрировать пробы</span>
                    </x-ui.button.default>
    {{--            @endif--}}
            </div>
        @elseif(!empty($filter['act']) && $filter['act']->canFinish())
            <div class="text-end mt-3">
                <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:finish();" >
                    <x-ui.icon.duotone icon="fa-flag-checkered" class="me-1"></x-ui.icon.duotone>
                    <span>Завершить акт</span>
                </x-ui.button.default>
            </div>
        @endif
    </div>

@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/extensions/export/bootstrap-table-export.min.js"></script>
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>

    @if($can_save)
        <script>
            function save_check() {
                err = true;
                $(".count_inp").each(function () {
                    if($(this).val() > $(this).attr("max")-0)
                        $(this).val($(this).attr("max"));

                    if($(this).val() < 0 || !$(this).val())
                        $(this).val(0);

                    if ($(this).val()-0) {
                        err = false;
                    }
                });

                return !err;
            }

            function check_button() {
                $("#btn_draft").attr("disabled", "disabled");
                $("#btn_submit").attr("disabled", "disabled");

                if (save_check()) {
                    $("#btn_draft").removeAttr("disabled");
                    $("#btn_submit").removeAttr("disabled");
                }
            }


            function draft() {
                if(!save_check() || !confirm('Вы действительно хотите сохранить черновик?'))
                    return false;

                $("body").block(block_default);
                $.ajax({
                    url: '{{ route('api.visit_measure_work.draft') }}?_token={{ _token() }}',
                    data: $("form#asset_finish").serialize(),
                    method: "POST",
                    dataType: 'json',
                    success: function (answer) {
                        if(answer.result == 'success') {
                            location.reload();
                        } else {
                            $("body").unblock();
                            toastr.error(answer.message, "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    },
                    error: function () {
                        $("body").unblock();
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                })
            }

            function finish() {
                if(!save_check() || !confirm('Вы действительно хотите завершить работу над указаннными отборами?'))
                    return false;

                $("body").block(block_default);
                $.ajax({
                    url: '{{ route('api.visit_measure_work.finish') }}?_token={{ _token() }}',
                    data: $("form#asset_finish").serialize(),
                    method: "POST",
                    dataType: 'json',
                    success: function (answer) {
                        if(answer.result == 'success') {
                            location.reload();
                        } else {
                            $("body").unblock();
                            toastr.error(answer.message, "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    },
                    error: function () {
                        $("body").unblock();
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                })
            }


            $(document).ready(function () {
                $("form#asset_finish input").on("change keyup", function () {
                    var obj = $(this);
                    if (obj.val() > 0 && obj.val() !== obj.attr("max")) {
                        obj.next('i').removeClass("invisible");
                    } else {
                        obj.next('i').addClass("invisible");
                    }
                    check_button();
                });

                check_button();
            });

        </script>
    @elseif(!empty($filter['act']) && $filter['act']->canFinish())
        <script>
            function finish() {
                if(!confirm('Вы действительно всё проверили и хотите завершить работу над актом?'))
                    return false;

                $("body").block(block_default);
                $.ajax({
                    url: '{{ route('api.visit.finalize', $filter['act']) }}?_token={{ _token() }}',
                    method: "POST",
                    dataType: 'json',
                    success: function (answer) {
                        if(answer.result == 'success') {
                            location.reload();
                        } else {
                            $("body").unblock();
                            toastr.error(answer.message, "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    },
                    error: function () {
                        $("body").unblock();
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                })
            }
        </script>
    @endif
@endsection
