@extends('components.box.box-static-large')

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

        #asset_visit .card-body .row label,
        #asset_visit .card-body .row label + div {
            padding-top: 2px !important;
            padding-bottom: 0!important;
            font-size: 14px;
        }

        #asset_visit ol li {
            font-size: 14px;
        }
        #asset_visit .col-sm-8:has(input#date_fact) {
            padding-top: 0 !important;
        }

        #asset_visit input#date_fact {
            font-size: 14px !important;
            font-weight: 300 !important;
            margin-left: -4px;
            width: 160px;
            padding-top: 3px;
            padding-bottom: 3px;
        }
        code {
            max-width: 50px;
            overflow: clip;
        }
        code.success {
            color: #347b3d;
        }
    </style>
    <div id="asset_visit">
        <form class="form-horizontal" id="asset_visit">
            <div class="card-body p-0">
                <div class="mb-1 row">
                    <label for="fname" class="col-sm-4 text-end control-label col-form-label pb-0">Адрес</label>
                    <div class="col-sm-8 pt-1 font-16 ps-4">
                        {{ $visit->order_task_address->address }}
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
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label pb-0">
                        {{ $visit->users->count() == 1 ? "Пробоотборщик" : "Пробоотборщики" }}
                    </label>
                    <div class="col-sm-8 ps-1">
                        <ol class="m-0">
                            @foreach($visit->users as $user)
                                <li>
                                    {{ $user->full_name }}
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label ps-0">Факт. передача проб
                        для анализа</label>
                    <div class="col-sm-8">
                        <input type="datetime-local" class="form-control" name="date" id="date_fact"
                               value="{{ ($visit->give_samples_at ?? now())->format('Y-m-d H:i') }}" class="w-auto">
                    </div>
                </div>

            </div>

            <div class="points table-responsive">
                @foreach($points as $point)
                <div class="bg-light-secondary mt-3 px-2 py-1">
                    <h6 class="m-0 font-12 fw-bold d-flex align-items-center">
                        <x-ui.badge.default type="danger">{{ $point->number }}</x-ui.badge.default>
                        <x-ui.icon.light icon="fa-map-pin" class="mx-2"></x-ui.icon.light>
                        <span>{{ $point->name }}</span>
                    </h6>
                </div>
                <table id="measures" class="table customize-table mb-0 v-middle border-1" border="1">
                        <tbody point="{{ $point->id }}">
                        <tr class="header">
                            <td/>
                            <td/>
                            {{--                            <td class="text-center font-10 fw-bold">Всего по ТЗ</td>--}}
                            <td class="text-center font-10 fw-bold">План</td>
                            <td class="text-center font-10 fw-bold">Остаток</td>

                            <x-visit.container_header_th container="1"></x-visit.container_header_th>

                            <td width="1">
                                <a href="javascript:void(0);" onclick="javascript:add_container({{ $point->id }});">
                                    <x-ui.icon.duotone icon="fa-plus-circle" class="text-secondary"></x-ui.icon.duotone>
                                </a>
                            </td>
                        </tr>
                        @foreach($measures_data[$point->id] as $visit_order_task_measure)
                            @php
                                $measure = $visit_order_task_measure->order_task_measure;
                            @endphp
                            <tr>
                                <td style="padding-right: 0!important;">
                                    @if($visit_order_task_measure->assetted_count >= $measure->count ?? 0)
                                        <x-ui.icon.solid class="text-success" icon="fa-check"></x-ui.icon.solid>
                                    @else
                                        <x-ui.icon.solid class="text-danger" icon="fa-xmark"></x-ui.icon.solid>
                                    @endif
                                </td>
                                <td>
                                    {{ $measure->measure->name }}
                                </td>

                                {{--                                <td class="text-center ">--}}
                                {{--                                    <span  @class(['counter', 'cursor-pointer', 'fw-bold text-success' => ($measure->count <= ($counts[$measure->id] ?? 0))])>--}}
                                {{--                                        {{ "?" }} / {{ $measure->count }}--}}
                                {{--                                    </span>--}}
                                {{--                                </td>--}}
                                <td class="text-center" width="60">
                                    <span  @class(['counter', 'cursor-pointer', 'fw-bold text-success' => ($measure->count <= ($counts[$measure->id] ?? 0))])>
                                        {{ $counts[$measure->id] ?? 0 }}
                                    </span>
                                </td>
                                <td class="text-center" width="60">
                                    <span  @class(['counter', 'cursor-pointer', 'fw-bold text-success' => ($measure->count <= ($counts[$measure->id] ?? 0))])>
                                        {{ $counts[$measure->id] ?? 0 }}
                                    </span>
                                </td>
                                <td class="text-center" align="center" width="60" container="1">
                                    {{-- Выводим все измерения --}}
                                    @php
                                        $max = $visit_order_task_measure->max_asset;
                                        $max = $counts[$measure->id] ?? 0;
                                    @endphp
                                        @if($visit->onlyViewAssets())

                                        @else
                                            <input name="count[1][{{ $visit_order_task_measure->id }}]" type="number"
                                                   class="form-control text-center p-0 font-12 count_inp d-inline-block " min="0"
                                                   value="{{ 1 }}"
                                                   style="width: 40px"

                                                   @if(!$max) readonly @endif
                                            >
                                        @endif
                                </td>
                                <td/>
                            </tr>
                        @endforeach
                        </tbody>
                </table>
                @endforeach
            </div>
        </form>
    </div>

    @unless($visit->onlyViewAssets())
    <script>
        {{-- @lang('js') --}}


        function save() {
            if (!count_check() || !confirm('Вы действительно хотите создать выезд?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.visit.create', $visit->order_task_address) }}?_token={{ _token() }}',
                data: $("form#create_visit").serialize(),
                method: "POST",
                dataType: 'json',
                success: function (answer) {
                    if (answer.result == 'success') {
                        location.reload();
                    } else {
                        $("body").unblock();
                        toastr.error("Не получилось сохранить выезд", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                },
                error: function () {
                    $("body").unblock();
                    toastr.error("Не получилось сохранить выезд", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                }
            })
        }


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

        $(document).ready(function () {
            $("form#asset_visit input").on("change keyup", function () {
                var obj = $(this);
                if (obj.val() > 0 && obj.val() !== obj.attr("max")) {
                    obj.next('i').removeClass("invisible");
                } else {
                    obj.next('i').addClass("invisible");
                }
                check_button();
            });

            $("i.clone").on("click", function () {
               if(!confirm('Вы действительно хотите откопировать маркировку на все пробы в точке?'))
                   return false;

               $(this).parents("tbody[point]").find(".mark").val($(this).prev('input').val());
            });
            check_button();
        });


        function draft() {
            if(!save_check() || !confirm('Вы действительно хотите сохранить черновик?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.visit.draft', $visit) }}?_token={{ _token() }}',
                data: $("form#asset_visit").serialize(),
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
            if(!save_check() || !confirm('Вы действительно хотите передать отборы в работу?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.visit.finish', $visit) }}?_token={{ _token() }}',
                data: $("form#asset_visit").serialize(),
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

        function set_mark(point_id, i) {

            let mark = prompt('Укажите название маркировки (для удаление введите пустое значение):');
            if (mark.length > 0) {
                $("tbody[point='" + point_id + "'] td[container='" + i + "'] code").addClass('success').attr('title', mark).html(mark);
                $("tbody[point='" + point_id + "'] td[container='" + i + "'] input").val(mark);
            } else {
                $("tbody[point='" + point_id + "'] td[container='" + i + "'] code").removeClass('success').attr('title', 'Без маркировки').html('<Пусто>');
                $("tbody[point='" + point_id + "'] td[container='" + i + "'] input").val('');
            }
        }

        function add_container(point_id) {
            let header = $("tbody[point='" + point_id + "'] tr.header")
            let last = header.find("td[container]:last");
            let i = last.attr('container') - 0 + 1;

            // заголовок
            let template_th = `<x-visit.container_header_th container="-1"></x-visit.container_header_th>`;
            template_th = template_th.replace(/-1/g, i);
            console.log(template_th);
            last.after(template_th);

            $("tbody[point='" + point_id + "'] tr:not('.header')").each(function () {
                let last = $(this).find("td[container]:last");
                let template_td = last.prop('outerHTML');
                last.after(template_td);

                // обновим
                last = $(this).find("td[container]:last");
                last.attr("container");
                let inp = last.find("input");
                inp.attr("name", inp.attr("name").replace("count[" + last.attr("container") + "]", "count[" + i + "]")).val(0);

            });

        }
    </script>
    @endunless
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>


        <div>
            @unless($visit->onlyViewAssets())
                <x-ui.button.default id="btn_draft" btn_type="secondary" onclick="javascript:draft();" disabled>
                    <x-ui.icon.duotone icon="fa-ruler" class="me-1"></x-ui.icon.duotone>
                    <span>Сохранить как черновик</span>
                </x-ui.button.default>

                <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:finish();" disabled>
                    <x-ui.icon.duotone icon="fa-save" class="me-1"></x-ui.icon.duotone>
                    <span>Зарегистрировать пробы</span>
                </x-ui.button.default>
            @endunless
        </div>
    </div>
@endsection


