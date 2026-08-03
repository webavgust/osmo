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

        #measures th,
        #measures td {
            border-right-width: 1px;
            border-top-width: 1px;
        }

        tbody.point {
            border-top: 3px solid #999 !important;
            border-bottom: 2px solid #ddd !important;
        }

        .table > :not(caption) > * > * {
            background-color: #FFF;
        }

        .table-hover > tbody > tr:hover {
            --bs-table-accent-bg: #FFF;
        }

        tbody:hover tr.measure td {
            /*background-color: #F9F9F9!important;*/
        }

        tbody[container] tr.measure:last-of-type {
            border-bottom: 2px solid #777;
        }

        tbody:not([container]) + tbody:not([container]) {
            border-top: 2px solid red !important;
        }

        th.check, td.check {
            background: #fff6ec;
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
                <div class="d-flex justify-content-between">
                    <h2>Акт
                        <mark class="fw-bold">#{{ $visit->number->number }}</mark>
                    </h2>
                    @if($visit->canCheck())
                        <div class="d-flex flex-column">
                            <x-visit.status :visit="$visit"></x-visit.status>

                            <x-ui.a.default
                                href="{{ route('order_task_object.detail', $visit->order_task_address->object) }}"
                                btn_type="success" class="mt-1">Перейти в ТЗ
                            </x-ui.a.default>
                        </div>
                    @endif
                </div>

                <h3 class="d-flex align-items-center mt-3 mb-4">
                    <x-order-task-object.badge-direction :object="$visit->order_task_address->object"
                                                         class="me-2"></x-order-task-object.badge-direction>

                    {!! $visit->order_task_address->object->lab_object?->chain_name !!}
                </h3>


                <form id="asset_finish">
                    <table id="measures" class="table customize-table mb-0 v-middle border-1 bg-white" border="1">
                        @foreach($points as $point)
                            <tbody class="point">
                            <tr>
                                <td class="p-3">
                                    <h4 class="d-flex mb-0">
                                        <x-ui.icon.solid icon="fa-map-pin"
                                                         class="me-2"></x-ui.icon.solid>
                                        <span class="d-flex flex-column">
                                                            <span> {{ $point->name  }}</span>
                                                      </span>
                                    </h4>
                                </td>
                            </tr>
                            </tbody>

                            @foreach($containers[$point->id] as $container)
                                <tbody container="{{ $container->id }}">
                                <tr class="menu">
                                    <th>Маркировка</th>
                                    <th>Носитель</th>
                                    <th>Характеристики</th>
                                    <th>Показатели</th>
                                    <th class="text-center">Кол-во</th>
                                    <th>Анализ</th>
                                </tr>

                                @foreach($container->samples as $sample)
                                    @php
                                        $max = $sample->max_asset;
                                        if($max > 0)
                                            $can_save = true;
                                        $self = $sample->self_asset;
                                    @endphp
                                    <tr class="measure">
                                        @if($loop->iteration == 1)
                                            <td rowspan="{{ $container->samples->count() }}" class="align-top">
                                                {{ $container->mark }}</td>
                                            <td rowspan="{{ $container->samples->count() }}" class="align-top">
                                                ...
                                            </td>
                                            <td rowspan="{{ $container->samples->count() }}" class="align-top">
                                                ...
                                            </td>
                                        @endif

                                        <td>{{ $sample->visit_order_task_measure->order_task_measure->measure->name }}</td>
                                        <td class="text-center">{{ $sample->count }} шт.</td>
                                        <td class="p-0">
                                            @foreach($sample->other_assets ?? [] as $asset)
                                                <div
                                                    class="d-flex justify-content-between align-items-center border-bottom-1"
                                                    style="border-bottom: 1px solid #DDD">
                                                    <div class="d-flex justify-content-start align-items-center p-2">
                                                        <x-ui.badge.light
                                                            :type="$asset->finished_at ? 'success' : 'warning'"
                                                            class="font-18 fw-bold mx-3 ms-1">
                                                            {{ $asset->count }}
                                                        </x-ui.badge.light>
                                                        <div>
                                                            @if(!empty($asset->finished_at))
                                                                <div
                                                                    class="fw-bold font-10">{{ $asset->finished_at->format('d.m.Y H:i') }}</div>
                                                            @endif
                                                            <div class="fw-bold">
                                                                {{ $asset->creator->full_name }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if($visit->canCheck())
                                                        <div class="me-2" id="check_{{ $asset->id }}">
                                                            <x-visit.asset_check :asset="$asset"></x-visit.asset_check>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach


                                            @if(!$sample->canAssetByUser())
                                                <div class="p-2">
                                                    <span class="text-danger">
                                                        <x-ui.icon.solid
                                                            icon="fa-close"
                                                            class="me-1"></x-ui.icon.solid>
                                                        Нет доступа
                                                    </span>
                                                </div>
                                            @elseif($max > 0)
                                                <div
                                                    class="p-2 form-group d-flex align-items-center m-0 @if($sample->other_assets->isNotEmpty()) mt-1 @endif">
                                                    <input name="count[{{ $sample->id }}]" type="number"
                                                           class="form-control text-center p-0 font-16 count_inp"
                                                           min="0"
                                                           max="{{ $max }}"
                                                           value="{{ !$max ? 0 : $self->count ?? 0 }}"
                                                           style="width: 50px; border-width: 3px"
                                                           @if(!$max) readonly @endif
                                                    >
                                                </div>
                @endif
            </div>
            </td>
            </tr>
            @endforeach
            </tbody>

            @endforeach
            @endforeach
            </table>
            </form>
        </div>
    </div>


    <div class="buttons d-flex justify-content-end">
        @if($visit->hasSamplesToCheck() && $visit->canCheck())
            <div class="text-end mt-3 check_all_assets me-2">
                <x-ui.button.default btn_type="warning" onclick="javascript:check_all_assets();">
                    <x-ui.icon.regular icon="fa-check" class="me-2"></x-ui.icon.regular>
                    Проверены все работы
                </x-ui.button.default>
            </div>
        @endif

        @if($can_save)
            <div class="text-end mt-3">
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
        @elseif($visit->canFinish())
            <div class="text-end mt-3">
                <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:visit_finish();">
                    <x-ui.icon.duotone icon="fa-flag-checkered" class="me-1"></x-ui.icon.duotone>
                    <span>Закрыть акт</span>
                </x-ui.button.default>
            </div>
        @endif

    </div>
    </div>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/extensions/export/bootstrap-table-export.min.js"></script>
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>

    @if($visit->canCheck())
        <script>
            function check_all_assets() {
                if (!confirm("Вы действительно хотите поставить отметку о проверке работы?"))
                    return false;
                $("body").block();
                $.ajax({
                    url: '{{ route('api.sample-works.check_all', $visit) }}?_token={{ _token() }}',
                    method: "POST",
                    dataType: 'json',
                    success: function (answer) {
                        if (answer.result == 'success') {
                            location.reload();
                        } else {
                            toastr.error("Не получилось сохранить данные", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                            $("body").unblock();
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

            function asset_check(id) {
                if (!confirm("Вы действительно хотите поставить отметку о проверке работы?"))
                    return false;

                $("body").block();
                $.ajax({
                    url: '{{ route('api.sample-works.check') }}/' + id + '?_token={{ _token() }}',
                    method: "POST",
                    dataType: 'html',
                    success: function (result) {
                        ar = result.split("|");
                        if (ar[0] == 'ok') {
                            $("#check_" + id).html(ar[1]);
                        } else {
                            toastr.error("При сохранении отметки произошла ошибка!", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }

                        if (!$(".asset_check").length)
                            $(".check_all_assets").remove();

                        $("body").unblock();
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


    <script>
        $(document).ready(function () {
            $("#measures").bootstrapTable();
        });

        @if($can_save)
        function save_check() {
            err = true;
            $(".count_inp").each(function () {
                if ($(this).val() > $(this).attr("max") - 0)
                    $(this).val($(this).attr("max"));

                if ($(this).val() < 0 || !$(this).val())
                    $(this).val(0);

                if ($(this).val() - 0) {
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
            if (!save_check() || !confirm('Вы действительно хотите сохранить черновик?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.sample.draft') }}?_token={{ _token() }}',
                data: $("form#asset_finish").serialize(),
                method: "POST",
                dataType: 'json',
                success: function (answer) {
                    if (answer.result == 'success') {
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
            if (!save_check() || !confirm('Вы действительно хотите завершить работу над указаннными отборами?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.sample.finish') }}?_token={{ _token() }}',
                data: $("form#asset_finish").serialize(),
                method: "POST",
                dataType: 'json',
                success: function (answer) {
                    if (answer.result == 'success') {
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
        @elseif($visit->canFinish())
        function visit_finish() {
            if (!confirm('Вы действительно всё проверили и хотите завершить работу над актом?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.visit.finalize', $visit) }}?_token={{ _token() }}',
                method: "POST",
                dataType: 'json',
                success: function (answer) {
                    if (answer.result == 'success') {
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
        @endif
    </script>
@endsection
