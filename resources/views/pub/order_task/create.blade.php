@extends('layouts.layout')

@section('styles')
    <style>
        input.error, select.error {
            background: #ffe9e9;
        }
        .structure ul li {
            list-style: none;
            position: relative;
        }
        .structure ul li .del {
            position: absolute;
            right: -20px;
            top: 10px;
            color: #d10909;
            font-size: 26px;
            opacity: 0;
            transition: opacity .75s;
            cursor: pointer;
        }
        .structure ul li .content:hover {
            background: #F8F8F8;
        }
        .structure ul li .content:hover .del {
            opacity: 1;
        }
        .structure ul {
            margin: 0;
            padding: 0;
        }
        .structure .content {
            padding: 10px 20px;
        }
        .structure ul.addresses .content > .row > div:first-of-type
        {
            padding-left: 40px;
        }
        .structure ul.points .content > .row > div:first-of-type

        {
            padding-left: 70px;
        }
        .structure ul.measures .content > .row > div:first-of-type

        {
            padding-left: 100px;
        }

        li.add {
            font-size: 12px;
            padding: 0 15px;
        }

        ul.addresses > li:first-of-type {
            border-top: 1px dotted #888;
        }
        ul.points > li:first-of-type {
            border-top: 1px dotted #AAA;
        }
        ul.measures > li:first-of-type {
            border-top: 1px dashed #CCC;
        }

        .objects > li:not(:first-of-type) {
            border-top: 10px solid #eef5f9;
            margin-top: 10px;
        }

        .addresses > li {
            border-top: 3px solid #AAA;
        }

        .fa-solid.fa-chevron-left {
            transform: rotate(-45deg);
            color: #CCC;
        }
        .add_point {
            margin-left: 70px;
            margin-bottom: 10px;
            margin-top: 10px;
        }
        .add_address {
            margin-left: 40px;
            margin-bottom: 10px;
        }
        .structure ul.addresses {
            border-bottom: 1px dotted #999;
            margin-bottom: 10px;
        }

        .select2-selection__rendered {
            line-height: 38px !important;
            font-weight: 400;
            color: #54667a!important;
        }
        .select2-container .select2-selection--single {
            height: 38px !important;
        }
        .select2-selection__arrow {
            height: 37px !important;
        }
        .select2-container--default .select2-selection--single {
            border: 0;
        }

    </style>
@endsection

@section('content')

        <div class="container-fluid">

            <form method="post" action="{{ route('order_task.step1_save') }}" id="order_task_create">
                @csrf

            <input type="hidden" name="contract_id" value="{{ $contract_id }}">
            <input type="hidden" name="contract_sub_id" value="{{ $contract_sub_id }}">
            <input type="hidden" name="block_id" value="{{ $block_id }}">
            <div class="row">
                <div class="col-12">
                    <div class="card mb-1">
                        <div class="d-flex border-bottom title-part-padding align-items-center justify-content-between">
                            <h4 class="card-title mb-0">Контактные данные</h4>
                        </div>
                        <div class="card-body p-0 structure">
                            <div class="form-group">
                                <textarea name="contacts" class="form-control" rows="3" placeholder="Контактные данные заказчика"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-1 mt-3">
                        <div class="d-flex border-bottom title-part-padding align-items-center justify-content-between">
                            <h4 class="card-title mb-0">Создание объектов, адресов и точек</h4>
                        </div>
                        <div class="card-body p-0 structure">
                                <ul class="objects">
                                    @component('components.order_task.create.object_row', compact('lab_objects')) @endcomponent
                                </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-12 col-sm-3 mb-1 mb-sm-0">
                    <x-ui.button.outline btn_type="info" onclick="javascript:add_object();" class="add_object w-100"><i class="fa-thin fa-circle-plus me-1"></i> объект</x-ui.button.outline>
                </div>
                <div class="col-12 col-sm-9 align-items-center justify-content-end d-flex">
                    <x-ui.button.default btn_type="success" onclick="javascript:form_confirm();" class=" w-100"><i class="fa-thin fa-circle-plus"></i> Сохранить тех. задание</x-ui.button.default>
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
                        Вы действительно хотите сохранить первую часть технического задания? После сохранения вы сможете назначить задания созданым точкам.
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
                console.log(text);
                Swal.fire({
                    type: "error",
                    html: text,
                });
                return false;
            }
            return true;
        }


        function form_submit() {
            $("form#order_task_create").submit();
        }
        function form_check() {
            error_flush();
            // проверим заполнение объектов
            if(!$(".objects > li").length)
                error_msg("Добавьте хотя бы один объект");

            $(".objects > li").each(function() {
                    if(!$(this).find(".object_name").val()) {
                        $(this).find(".object_name").addClass("error");
                        error_msg("<b>" + $(this).find('h5').html() + "</b>: не заполнено название");
                    }
                    if($(this).find(".object_type").val() < 1) {
                        $(this).find(".object_type").addClass("error");
                        error_msg("<b>" + $(this).find('h5').html() + "</b>: не выбран тип");
                    }

                    var cur_obj = $(this);
                    if(!cur_obj.find(".addresses > li").length)
                    {
                        error_msg("<b>" + $(this).find('h5').html() + "</b>: нет добавленных адресов");
                    }
                        cur_obj.find(".addresses > li").each(function() {
                            if(!$(this).find(".address").val()) {
                                $(this).find(".address").addClass("error");
                                error_msg("<b>" + cur_obj.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: нет названия");
                            }
                            var cur_adr = $(this);
                            if(!cur_adr.find(".points > li").length)
                            {
                                error_msg("<b>" + cur_obj.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: нет добавленных точек");
                            }
                                cur_adr.find(".points > li").each(function() {
                                    if(!$(this).find(".point_name").val()) {
                                        $(this).find(".point_name").addClass("error");
                                        $(this).addClass("error");
                                        error_msg("<b>" + cur_obj.find('h5').html() + "</b>, <b>" + cur_adr.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: нет названия");
                                    }
                                });
                        });
            });
        }




        function form_confirm() {
            form_check();
            if(!error_out()) return false;
            $('#submit_modal').modal('show');
        }


        function li_del(obj)
        {
            $(obj).parents('li').first().remove();
        }
        function add_object(uid)
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
                url: "{{ route('order_task.component.object', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: {
                    uid: uid,
                    num: $(".objects > li").length + 1
                },
                dataType: "html",
                success: function (result) {
                    $(".objects").append(result);
                    $(block_elem).unblock();
                    rebind();
                },
                error: function () {
                    toastr.error("Не получилось добавить объект", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }
        function add_address(uid)
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
                url: "{{ route('order_task.component.address', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: {
                    uid: uid,
                    num: $("li[uid='" + uid + "'] .addresses > li").length + 1
                },
                dataType: "html",
                success: function (result) {
                    $("li[uid='" + uid + "'] .addresses").append(result);
                    $(block_elem).unblock();
                    rebind();
                },
                error: function () {
                    toastr.error("Не получилось добавить адрес", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }



        function add_point(uid)
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
                url: "{{ route('order_task.component.point', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: {
                    uid: uid,
                    num: $("li[uid='" + uid + "'] .points > li").length + 1
                },
                dataType: "html",
                success: function (result) {
                    $("li[uid='" + uid + "'] .points").append(result);
                    $(block_elem).unblock();
                    rebind();
                },
                error: function () {
                    toastr.error("Не получилось добавить точку", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }
        function rebind() {
            $("#order_task_create").find("input, select").off("keyup change");
            $("#order_task_create").find("input, select").on("keyup change", function() {
                $(this).removeClass('error');
            });
            $(".select2").select2({
                height: 50
            });
        }
        $(document).ready(function() {
            rebind();
        });

    </script>
@endsection
