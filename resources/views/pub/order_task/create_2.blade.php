@extends('layouts.layout')

@section('styles')
    <style>
        .row.point .measure_row,
        .row.service .service_row
        {
            position: relative;
        }
        .row.point .measure_row .del,
        .row.service .service_row .del
        {
            position: absolute;
            right: -20px;
            top: 0px;
            color: #d10909;
            font-size: 26px;
            opacity: 0;
            transition: opacity .75s;
            cursor: pointer;
        }
        .row.point .measure_row:hover .del,
        .row.service .service_row:hover .del
        {
            opacity: 1;
        }


        .address {
            padding: 0 0 6px 15px;
            background: #F7F7F7;
            padding-top: 16px;
        }

        .row.point {
            padding: 10px 30px;
            border-top: 1px solid #EEE;
            border-bottom: 1px solid #EEE;
        }
        .row.service {
            padding: 10px 30px;
            border-top: 1px solid #EEE;
            border-bottom: 1px solid #EEE;
        }


        input.inp_count {
            width: 65px !important;
            flex-grow: 0 !important;
        }

        input.inp_cost {
            width: 90px !important;
            flex-grow: 0 !important;
        }

        .row_total_field {
            min-width: 100px;
        }
        .row_total {
            margin-left: 10px;
        }

        .measure_row {
            margin-bottom: 5px;
        }

        .inp_comment {
            /*width: 200px !important;*/
            /*flex-grow: 0 !important;*/
        }

        .input-group .select2 {
            flex-grow: 1;
            max-width: 450px;
        }
        .input-group .select2-container--default .select2-selection--single {
            border-radius: 0;
            border: 1px solid #e9ecef;
            height: 37px;
        }

        .input-group .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }

        .inp_cost.error {
            background: #ffe9e9;
            cursor: help;
        }
        select.error + span .select2-selection.select2-selection--single {
            background: #ffe9e9;
        }

        .measure_add {
            font-size: 11px;
        }

        .service_row {
            margin-bottom: 5px;
        }
        .service_name {
            justify-content: flex-start;
            align-items: center;
            padding-left: 10px;
            display: flex;
            border-top: 1px solid #EEE;
            border-bottom: 1px solid #EEE;
            color: #444;
        }

        .point[uid]:not(.copy) .copy_cancel {
            display: none;
        }
        .point[uid].copy {
            background: #fff2ca;
        }
        .point[uid].copy .copy {
            display: none;
        }
        .paste_control {
            display: none;
        }
        .copied .copy-icon {
            display: inline-block;
        }
        .copy-icon {
            display: none;
        }

        .point_name_pad {
            display: flex;
            align-items: center;
        }
        .point_name_pad input {
            margin-left: 5px;
            padding: 2px 5px;
            font-size: 12px;
            color: #fc4b6c;
        }

        .inp_comment {
            min-width: 120px!important;
        }

        @media screen and (max-width: 1300px) {
            .row_total_field {
                display: none!important;
            }
        }
        @media screen and (max-width: 1000px) {
            .row_icon {
                display: none!important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">

        <form method="post" action="{{ route('order_task.step2_save', $order_task) }}" id="order_task_create">
            @csrf
            <div class="row">
                <div class="col-12">
                    @foreach($order_task->objects as $object)
                        <div class="card w-100 object" uid="{{ $object->id }}">
                            <div
                                class=" d-flex border-bottom title-part-padding align-items-center p-3 justify-content-between">
                                <h3 class="card-title mb-0"><i class="fa-regular fa-industry"></i> <span>{{ $object->name }}</span></h3>
                                <div class="alert text-primary alert-light-primary p-1 ps-2 pe-2 m-0" role="alert">
                                    {{ $object->lab_object->chain_name }}
                                </div>
                            </div>
                            <div class="card-body p-3 pt-0 pb-0">
                                @foreach($object->addresses as $address)
                                    <div class="address_block">
                                        <div class="row address" uid="{{$address->id}}">
                                            <div class="col-12 justify-content-between d-flex">
                                                <h5><i class="fa-solid fa-location-dot me-1"></i> <span>{{ $address->address }}</span></h5>

                                                <div class="paste_control">
                                                    <a class='paste text-primary' href="javascript:void(0)" onclick="javascript:paste($(this))"><i class="fa-solid fa-inbox-in"></i> вставить</a>
                                                </div>

                                            </div>
                                        </div>
                                        <div>
                                            <div class="points">
                                                @foreach($address->points as $point_i => $point)
                                                    <div class="row point position-relative {{ $point_i % 2 == 0 ? "even" : "odd" }}" uid="{{ $point->id }}">
                                                        <div class="col-12 mb-1 justify-content-between d-flex">
                                                            <span class="text-danger point_name_pad"><i class="fa-solid fa-map-pin me-1"></i> <span class="point_name">
                                                            @if(!empty($point->number))
                                                                <span class="mb-1 badge bg-danger mr-1">{{ $point->number }}</span>
                                                            @endif
                                                            {{ $point->name }}</span></span>


                                                            <div class="copy_control">
                                                                <a class='copy' href="javascript:void(0)" onclick="javascript:copy($(this))"><i class="fa-regular fa-copy"></i></a>
                                                                <a class='copy_cancel text-danger' href="javascript:void(0)" onclick="javascript:copy_cancel()"><i class="fa-regular fa-ban"></i> отменить</a>
                                                                <a class="copy-icon ms-2 text-danger" href="javascript:void(0)" onclick="javascript:copy_remove($(this))" title="Это скопированная точка. При перезагрузке страницы она потеряется"><i class="fa-solid fa-delete-left"></i></a>
                                                            </div>
                                                        </div>

                                                        <div class="col-12 ms-3 mt-2">
                                                            <div class="row measure_pad">
                                                                <x-order_task.create.measure_row :point="$point" :measures="$measure_collect[$object->id]"></x-order_task.create.measure_row>
                                                            </div>

                                                            <x-ui.button.light btn_type="secondary" onclick="javascript:add_measure($(this))" class="add_address p-0 ps-2 pe-2 text-secondary measure_add">
                                                                <i class="fa-solid fa-plus"></i>
                                                                <i class="fa-solid fa-1"></i>
                                                            </x-ui.button.light>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="services">
                                    <div class="row service">

                                        <div class="col-12 mb-1 text-primary">
                                            <i class="fa-light fa-coin me-1"></i> <span class="point_name">Услуги</span>
                                        </div>
                                        <div class="col-12 ms-3 mt-2">
                                            <div class="row service_pad">
                                                @forelse($object->services as $obj_service)
                                                    <x-order_task.create.service_row :object="$object" :services="$services" :objservice="$obj_service"></x-order_task.create.service_row>
                                                @empty

                                                @endforelse
                                            </div>

                                            <x-ui.button.light btn_type="secondary" onclick="javascript:add_service({{$object->id}})" class="add_address p-0 ps-2 pe-2 text-secondary measure_add">
                                                <i class="fa-solid fa-plus"></i>
                                                <i class="fa-solid fa-1"></i>
                                            </x-ui.button.light>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-6">
                    <a href="{{ route("order_task.edit_step1", $order_task) }}" class="btn waves-effect waves-light btn-outline-secondary" btn_type="success">
                        <i class="fa-solid fa-arrow-left"></i> Вернуться назад
                    </a>
                </div>
                <div class="col-6 align-items-center justify-content-end d-flex">
                    <x-ui.button.default btn_type="success" onclick="javascript:form_confirm();"><i
                            class="fa-thin fa-circle-plus me-1"></i> Сохранить тех. задание
                    </x-ui.button.default>
                </div>
            </div>
        </form>
    </div>




    <div
        id="submit_modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLab el"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-light-secondary text-dark-warning">
                    <h4 class="modal-title text-dark" id="danger-header-modalLabel">Сохранение технического задания</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                </div>
                <div class="modal-body">
                    Вы действительно хотите сохранить первую часть технического задания? После сохранения вы сможете
                    назначить задания созданым точкам.
                </div>


                <div class="modal-footer">
                    <button type="button" class="btn btn-light text-secondary" data-bs-dismiss="modal">
                        Не сохранять
                    </button>
                    <button type="button" id="btn_status_confirm" data-bs-dismiss="modal" class="
                                btn btn-light-success text-success
                          font-weight-medium
                              " onclick="javascript:form_submit()">
                        СОХРАНИТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/select2/dist/js/select2.full.min.js"></script>
    <script>
        var costs = @json($costs);
        var error_container = [];
        function error_flush() {
            error_container = [];
        }
        function error_msg(message) {
            error_container.push(message);
        }
        function error_out(message) {
            if(error_container.length) {
                var text = error_container.join("<br/>");
                Swal.fire({
                    type: "error",
                    html: text,
                });
                return false;
            }
            return true;
        }
        function uuidv4() {
            return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
            );
        }


        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
        }

        function paste(obj)
        {
            if(!window.copy_row) return false;

            Swal.fire({
                title: "Укажите название точки",
                input: "text",
                inputAttributes: {
                    autocapitalize: "off",
                },
                confirmButtonText: "Вставить",
            }).then((result) => {
                if (result.value) {

                    uid_point_from = window.copy_row.attr("uid");
                    uid_point_to = 'new_' + (new Date().getTime() / 1000);

                    var html = window.copy_row[0].outerHTML;
                    html = html.replace(new RegExp(escapeRegExp('uid="' + uid_point_from + '"'), 'g'), 'uid="' + uid_point_to + '"');
                    html = html.replace(new RegExp(escapeRegExp('[' + uid_point_from + ']'), 'g'), '[' + uid_point_to + ']');


                    window.copy_row.find(".measure_row").each(function() {
                        uid_from = $(this).attr("uid");
                        uid_to = uuidv4();

                        html = html.replace(new RegExp(escapeRegExp('uid="' + uid_from + '"'), 'g'), 'uid="' + uid_to + '"');
                        html = html.replace(new RegExp(escapeRegExp('[' + uid_from + ']'), 'g'), '[' + uid_to + ']');
                    });


                     obj.parents(".address_block").find(".points").append(html);
                     inserted = obj.parents(".address_block").find(".points .point:last-of-type");
                     inserted.removeClass("copy").addClass("copied");
                     inserted.find(".point_name").html('' +
                         '<input type="text" class="form-control" name="point_new[' + uid_point_to + '][name]" value="' + result.value+ '">' +
                         '<input type="hidden" name="point_new[' + uid_point_to + '][address_id]" value="' + obj.parents(".address").attr("uid") + '">');
                     inserted.find(".select2-container").remove();

                    inserted.find("[data-select2-id]").each(function() {
                        $(this).removeAttr("data-select2-id");
                    });
                    inserted.find(".select2").removeClass("select2").removeClass("select2-hidden-accessible");


                    inserted.find(".measure_row").each(function(index, val) {
                        var copy_measure = window.copy_row.find(".measure_row:nth-child(" + (index + 1) +")");

                       $(this).find("select").val(copy_measure.find("select").val());
                       $(this).find(".inp_count").val(copy_measure.find(".inp_count").val());
                       $(this).find(".inp_cost").val(copy_measure.find(".inp_cost").val());
                       $(this).find(".inp_comment").val(copy_measure.find(".inp_comment").val());

                       bind_row($(this));
                    })
                }
            });

        }


        function copy(obj)
        {
            $(".point[uid]").removeClass("copy");
            $(".paste_control").show();
            var row = obj.parents(".point[uid]")
            row.addClass("copy");
            window.copy_row = row;
        }

        function copy_cancel(obj)
        {
            $(".row[uid]").removeClass("copy");
            $(".paste_control").hide();
            window.copy_row = null;
        }

        function copy_remove(obj)
        {

            var row = obj.parents(".point");
            if(row.hasClass("copy")) copy_cancel();
            if(row.hasClass("copied")) row.remove();
        }

        function form_submit() {
            $(".service_name").removeAttr("disabled");
            $("form#order_task_create").submit();
        }

        function form_check() {
            error_flush();
            // проверим заполнение объектов
            $(".object").each(function() {
                var obj_name = $(this).find("h3 span").html();
                $(this).find(".address_block").each(function() {
                    var adr_name = $(this).find("h5 span").html();

                    $(this).find(".points .point").each(function() {
                        if(!$(this).find(".measure_row").length) {
                            error_msg("<b>" + obj_name + ", " + adr_name + ", " + $(this).find('.point_name').html() + "</b>: нет добавленных исследований");
                        } else {
                            $(this).find(".measure_row").each(function() {
                                if($(this).parents('.point').find('.point_name input').length) {
                                    var point_name = $(this).parents('.point').find('.point_name input').val();
                                } else {
                                    var point_name = $(this).parents('.point').find('.point_name').html();
                                }

                                if($(this).find("select").val() == 0) {
                                    $(this).find("select").addClass('error');

                                    error_msg("<b>" + obj_name + ", " + adr_name + ", " + point_name + "</b>: не выбрано исследование");

                                } else {
                                    var count = $(this).find(".inp_count");
                                    if(count.val()-0 < 1) {
                                        $(this).find("select").addClass('error');
                                        error_msg("<b>" + obj_name + ", " + adr_name + ", " + point_name + "</b>: не указано количество");
                                    }
                                    var cost = $(this).find(".inp_cost");
                                    if(!(cost.val()-0 > 0)) {
                                        $(this).find("select").addClass('error');
                                        error_msg("<b>" + obj_name + ", " + adr_name + ", " + point_name + "</b>: не указана стоимость");
                                    }
                                }
                            });
                        }

                    });
                });
                $(this).find(".service_row").each(function() {
                    if($(this).find("select").val() == 0) {
                        $(this).find("select").addClass('error');
                        error_msg("<b>" + obj_name +  "</b>: не выбрана доп.услуга");
                    }
                });
            });

        }


        function form_confirm() {
            form_check();
            if(!error_out()) return false;
            $('#submit_modal').modal('show');
        }



        function calc_row(object_uid, uid, flush_cost = false) {
            var obj = $(".object[uid='" + object_uid + "']");
            var row = $(obj).find("[uid='" + uid + "']");
            var measure_field = row.find("select");
            var cost_field = row.find(".inp_cost");
            var count_field = row.find("input[type='number']");

            if(count_field.val() < 1 || Math.round(count_field.val()) != count_field.val())
                count_field.val(1);

            if(row.hasClass('measure_row'))
            {

                console.log(measure_field.val());
                console.log(costs[measure_field.val()]);
                if(measure_field.val() > 0) {
                    cost_field.prop('disabled', false);
                } else {
                    cost_field.val('').prop('disabled', 1).removeClass("error");
                }
                cost_field.attr("measure_cost", costs[measure_field.val()]);
                if(flush_cost || cost_field.val() <= 0) cost_field.val(costs[measure_field.val()]);

                if(cost_field.val() && cost_field.val()-0 != cost_field.attr('measure_cost')-0) {
                    cost_field.addClass('error').attr("title", "Реальная стоимость этого измерения = " + cost_field.attr("measure_cost") + 'р.').addClass("cursor-help");
                } else {
                    cost_field.removeClass('error').removeAttr("title");
                }
            }

            var total = (cost_field.val() * count_field.val());
            row.find(".row_total span").html(total);
        }

        function row_del(obj)
        {
            $(obj).parents('.measure_row').remove();
            $(obj).parents('.service_row').remove();
        }

        function add_measure(obj)
        {
            var point_id = obj.parents('.point[uid]').attr("uid");
            if(!obj.parents('.point[uid]').hasClass("copied"))
            {
                 var data = {
                     point_id: point_id
                 };
            } else {
                var data = {
                    object_id: obj.parents('.object[uid]').attr("uid"),
                    point_uid: point_id
                };
            }
            var block_elem = $("body");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
                overlayCSS: {
                    backgroundColor: "#000",
                    opacity: 0.5,
                    cursor: "wait",
                },
                css: {
                    border: 0,
                    padding: 0,
                    backgroundColor: "transparent",
                },
            });
            $.ajax({
                url: "{{ route('order_task.component.measure', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: data,
                dataType: "html",
                success: function (result) {
                    $(".row.point[uid='" + point_id + "'] .measure_pad").append(result);
                    bind_row($(".row.point[uid='" + point_id + "'] .measure_row:not([binded])"));

                    $(block_elem).unblock();
                },
                error: function () {
                    toastr.error("Не получилось добавить измерение", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }

        function add_service(object_id)
        {
            var block_elem = $("body");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
                overlayCSS: {
                    backgroundColor: "#000",
                    opacity: 0.5,
                    cursor: "wait",
                },
                css: {
                    border: 0,
                    padding: 0,
                    backgroundColor: "transparent",
                },
            });
            $.ajax({
                url: "{{ route('order_task.component.service', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: {
                    object_id: object_id
                },
                dataType: "html",
                success: function (result) {
                    $(".object[uid='" + object_id + "'] .service_pad").append(result);
                    bind_row($(".service_row:not([binded])"));
                    bind_service();
                    $(block_elem).unblock();
                },
                error: function () {
                    toastr.error("Не получилось добавить услугу", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }


        function bind_row(row_obj) {
            row_obj.find("select:not(.service_link)").select2().change(function() {
                if($(this).val() > 0) $(this).removeClass("error");
                calc_row(row_obj.parents(".object").attr("uid"), $(this).parents('[uid]:not(.object):not(.address)').attr("uid"), true);
            });

            row_obj.find("input").on("change keyup", function() {
                setTimeout(() => {
                    calc_row($(this).parents(".object[uid]").attr("uid"), $(this).parents('[uid]:not(.object):not(.address)').attr("uid"))
                }, 300);
            });

            row_obj.attr("binded", true);
        }

        function bind_service() {
            $("select.service_link:not(.linked)").each(function() {
                $(this).addClass("linked");
                $(this).select2({
                    minimum: 2,
                    ajax: {
                        delay: 250,
                        url: '{{ route('api.order_task_object.service_link', ['_token' => auth()->user()->ajax_token]) }}',
                        appenTo: $(this).parents('.input-group')    ,
                        data: function (params) {
                            return {
                                q: params.term, // search term
                                service_id: $(this).parents('.input-group').find(".service_name").val(),
                                ignore: {{ $order_task->id }}
                            };
                        },
                        processResults: function (response) {
                            return {
                                results: response
                            };
                        }
                    },
                    escapeMarkup: function(markup) {
                        return markup;
                    },

                    templateResult: function(row, e, b) {
                        if(row.disabled) return '';
                        if(row.id == 0) return 'Отменить';

                        ret = `
                            <div class="d-flex select_link_row">
                                <div class="left">
                                    `;
                        ret += `<div><span>Имя объекта:</span> ` + row.object_name + `</div>`;
                        ret += `<div><span>ID Заявки:</span> ` + row.object_task_id + `</div>`;
                        if(row.object_task_order_id) ret += `<div class="order"><span>Заявка:</span> ` + row.object_task_order_id + `</div>`;
                        ret += `
                                </div>`;
                        if(row.cost > 0) ret += `<div class="right">` + row.cost + `р.</div>`;
                        ret += `</div>`;


                        return ret;
                    },
                    templateSelection: function(row) {
                        if(row.id == 0) return '';


                        if(!row.object_task_id) {
                            row.object_task_id = $(row.element).attr("object_task_id");
                            row.object_name = $(row.element).attr("object_name");
                            row.object_task_order_id = $(row.element).attr("object_task_order_id");
                            row.cost = $(row.element).attr("cost");
                        }


                        ret = `
                            <div class="d-flex select_link_row">
                                <div class="left">
                                    <span class="name">` + row.object_task_id;
                        if(row.object_task_order_id)
                            ret += ` > ` + row.object_task_order_id;

                        ret += ` > ` + row.object_name + `</span>
                                </div>
                                <div class="right">`;

                        if(row.cost > 0) ret += `<span class="cost">` + row.cost + `р.</span>`;

                        ret += `</div>
                            </div>
                        `;
                        return ret;
                    },
                }).on("select2:select", function(e) {
                    var select = $(e.currentTarget);
                    if(select.val() > 0)
                    {
                        select.parents(".input-group").find(".service_name").select2({disabled:'readonly'});
                        select.parents(".input-group").find(".inp_count").attr("readonly", 1)
                        select.parents(".input-group").find(".inp_cost").attr("readonly", 1)
                    } else {
                        select.parents(".input-group").find(".service_name").select2({disabled: false});
                        select.parents(".input-group").find(".inp_count").removeAttr("readonly")
                        select.parents(".input-group").find(".inp_cost").removeAttr("readonly")
                    }
                });
            });
        }
        $(document).ready(function() {
           $(".measure_row:not([binded])").each(function() {
               bind_row($(this));
           });
           $(".service_row:not([binded])").each(function() {
               bind_row($(this));
           });
            bind_service();
        });
    </script>
@endsection
