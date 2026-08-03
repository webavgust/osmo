@extends('layouts.layout')

@section('styles')
    <link rel="stylesheet" type="text/css"
          href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" type="text/css" href="/dist/modules/dropzone/dropzone.css"/>
    <script src="/dist/modules/dropzone/dropzone-min.js"></script>
    <style>
        .select2-selection__rendered {
            line-height: 38px !important;
            font-weight: 400;
            color: #54667a !important;
        }

        .select2-container .select2-selection--single {
            height: 38px !important;
        }

        .select2-selection__arrow {
            height: 37px !important;
        }

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

        .structure ul.addresses .content > .row > div:first-of-type {
            padding-left: 40px;
        }

        .structure ul.points .content > .row > div:first-of-type {
            padding-left: 70px;
        }

        .structure ul.measures .content > .row > div:first-of-type {
            padding-left: 10px;
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
            border-top: 20px solid white;
        }

        .addresses > li {
            border-top: 3px solid #AAA;
        }

        .fa-solid.fa-chevron-left {
            transform: rotate(-45deg);
            color: #CCC;
        }

        .add_measure {
            margin-left: 100px;
            margin-bottom: 10px;
            margin-top: 10px;
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
            color: #54667a !important;
        }

        .select2-container .select2-selection--single {
            height: 38px !important;
        }

        .select2-selection__arrow {
            height: 37px !important;
        }
    </style>
@endsection

@section('breadcrumb_right')
    @if($evaluation->canEdit())
        <x-ui.a.box href="{{ route('evaluation.box_copy', $evaluation) }}" btn_type="info" class="btn-light-warning text-warning">
            <x-ui.icon.regular icon="fa-copy" class="me-2"/>
            Копировать другое приложение
        </x-ui.a.box>
    @endif
@endsection

@section('content')
    <script>
        var measure_cost = @json($measures)
    </script>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="row">
                    <div class="col-12 col-xl-8" id="block_main">

                        <form method="post"
                              action="{{ route('evaluation.init_save', [$evaluation]) }}"
                              id="evaluation_create">
                            @csrf
                            <input type="hidden" name="period">
                            <input type="hidden" name="responsible">
                            <textarea name="comment" class="d-none"></textarea>

                            <div class="card mb-1">
                                <div
                                    class="card-body d-flex justify-content-between align-items-center flex-column flex-sm-row">
                                    <h4 class="card-title mb-0">Адреса и виды анализов</h4>
                                </div>
                                <div class="card-body p-0 structure">
                                    <ul class="objects">
                                        <x-evaluation.create.object_row></x-evaluation.create.object_row>
                                    </ul>

                                    <x-ui.button.outline btn_type="info" onclick="javascript:add_object()"
                                                         class="add_object ms-3 mt-2 mb-3"><i
                                            class="fa-thin fa-circle-plus me-1"></i> объект
                                    </x-ui.button.outline>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="col-12 col-xl-6 d-none" id="block_document" style="z-index: 1000">
                        <div class="card mb-0 position-fixed" style="height: calc(100% - 10vh);">
                            <div class="position-relative">
                                <button type="button" class="btn btn-danger btn-circle position-absolute" style="right: -15px; top: -15px" onclick="doc_close();">
                                    <x-ui.icon.regular icon="fa-xmark"></x-ui.icon.regular>
                                </button>
                            </div>
                            {{--                            <object><embed src="" width="100%" height="100%" id="doc_embed"/></object>--}}
                            <iframe width="100%" height="100%" id="doc_embed"></iframe>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4" id="block_secondary">
                        <x-ui.a.box id="doc_btn" class="w-100 mb-2" btn_type="primary" href="{{ route('evaluation.box_import') }}">Загрузить документ (PDF, DOCX, XLSX)</x-ui.a.box>

                        <div class="card mb-0">
                            <div
                                class="card-body d-flex justify-content-between align-items-center flex-column flex-sm-row">
                                <h4 class="card-title mb-0">Информация</h4>
                            </div>
                            <div class="card-body">
                                <div class="card-table">
                                    <x-ui.card.card_table_tr field="Оценщик" required="1">
                                        <x-ui.select.single :items="$responsibles" name="responsible_out"
                                                            class="select2 course mb-2" id="id"
                                                            value-name="full_name"></x-ui.select.single>
                                    </x-ui.card.card_table_tr>
                                    <x-ui.card.card_table_tr field="Срок исполнения">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Укажите срок" name="period_out" value="">
                                        </div>
                                    </x-ui.card.card_table_tr>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3 mb-0">
                            <div
                                class="card-body d-flex justify-content-between align-items-center flex-column flex-sm-row">
                                <h4 class="card-title mb-0">Комментарий</h4>
                            </div>
                            <div class="card-body p-1">
                                <textarea name="comment_out" class="form-control" rows="5"></textarea>
                            </div>
                        </div>


                        <x-files.dropzone :files="[]" mode="evaluation" id="{{ $evaluation->id }}" block="other"
                                          name="Файлы"
                                          class="mt-3 mb-0"></x-files.dropzone>

                        <x-ui.button.default btn_type="success" onclick="javascript:form_confirm();"
                                             class=" w-100 mt-3"><i
                                class="fa-thin fa-circle-plus"></i> Сохранить приложение
                        </x-ui.button.default>
                    </div>

                </div>
            </div>
        </div>
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
                    <h4 class="modal-title text-dark" id="danger-header-modalLabel">Сохранение приложения</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                </div>
                <div class="modal-body">
                    Вы действительно хотите сохранить приложение?
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

    <div
        id="paste_modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLab el"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-light-secondary text-dark-warning">
                    <h4 class="modal-title text-dark" id="danger-header-modalLabel">Копирование измерения</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не копировать"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите откопировать измерение?</p>

                    <div class="d-flex align-items-center justify-content-start">
                        <span class="me-2">Количество записей для вставки:</span>
                        <input id="paste_count" type="number" class="form-control text-center" min="1" max="100"
                               value="1" style="width: 70px">
                    </div>
                </div>


                <div class="modal-footer">
                    <button type="button" class="btn btn-light text-secondary" data-bs-dismiss="modal">
                        Не вставлять
                    </button>
                    <button type="button" id="btn_status_confirm" data-bs-dismiss="modal" class="
                                btn btn-light-success text-success
                          font-weight-medium
                              " onclick="javascript:paste_measure_confirm()">
                        ВСТАВИТЬ
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

    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/locales/bootstrap-datepicker.ru.min.js"></script>
    <script src="/assets/libs/select2/dist/js/select2.full.min.js"></script>
    <script src="/assets/libs/inputmask/dist/min/jquery.inputmask.bundle.min.js"></script>
    <script>

        var error_container = [];

        function doc_show(path) {
            var width = $(document).width();
            $("#main-wrapper")
                .attr('data-sidebartype', 'mini-sidebar')
                .addClass('mini-sidebar')
            ;
            $("#doc_btn").addClass("d-none");
            $(".row.page-titles").addClass("d-none");
            $("footer").addClass("d-none");


            if (width > 1600 || 1) {
                $("#block_main").removeClass("col-xl-8").addClass("col-xl-6");
                $("#block_secondary").removeClass("col-xl-4").addClass("col-xl-6");
                setTimeout(function () {
                    $("#block_document").removeClass("d-none");
                    $("#block_document > .position-fixed").css("width", $("#block_document").width());
                    $("#doc_embed").attr("src", path).css("height", $("#block_document > .card").height());
                }, 500);
            } else {
                setTimeout(function () {
                    $("#block_document").removeClass("d-none");
                    $("#block_secondary").css("margin-bottom", "40vh");
                    $("#block_document > .position-fixed")
                        .css("width", $("#block_main").width())
                        .css("height", "40vh")
                        .css("bottom", 0)
                        .css("right", "30px")
                        .css("background", "#EEE")
                    ;
                    $("#doc_embed").css("height", $("#block_document > .card").height())
                    $("#doc_embed").attr("src", path);
                }, 500);


            }

            return true;
        }

        function doc_close() {
            $("#main-wrapper")
                .attr('data-sidebartype', 'full')
                .removeClass('mini-sidebar')
            ;

            $("#doc_btn").removeClass("d-none");
            $(".row.page-titles").removeClass("d-none");
            $("footer").removeClass("d-none");

            $("#block_main").addClass("col-xl-8").removeClass("col-xl-6");
            $("#block_secondary").addClass("col-xl-4").removeClass("col-xl-6");
            $("#block_document").addClass("d-none");
        }

        function error_flush() {
            $(".error").removeClass('error');
            error_container = [];
        }

        function error_msg(message) {
            error_container.push(message);
        }

        function error_out(message) {
            if (error_container.length) {
                var text = error_container.join("<br/>");
                Swal.fire({
                    type: "error",
                    html: text,
                });
                return false;
            }
            return true;
        }


        function form_submit() {
            if (!form_check())
                return false;
            $("form#evaluation_create").submit();
        }

        function form_check() {
            error_flush();

            if (!$("[name='responsible_out']").val()) {
                $("[name='responsible_out'] + .select2").addClass("error");
                error_msg("Укажите ответственное лицо");
            } else {
                $("[name='responsible']").val($("[name='responsible_out']").val());
                $("[name='period']").val($("[name='period_out']").val());
                $("[name='comment']").val($("[name='comment_out']").val());
            }

            // проверим заполнение адресов
            $(".objects > li").each(function () {
                if (!$(this).find(".object_name").val()) {
                    $(this).find(".object_name").addClass("error");
                    error_msg("<b>" + $(this).find('h5').html() + "</b>: не заполнено название");
                }

                var cur_obj = $(this);
                if (!cur_obj.find(".addresses > li").length) {
                    error_msg("<b>" + $(this).find('h5').html() + "</b>: нет добавленных адресов");
                }
                cur_obj.find(".addresses > li").each(function () {
                    if (!$(this).find(".address").val()) {
                        $(this).find(".address").addClass("error");
                        error_msg("<b>" + cur_obj.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: нет адреса");
                    }
                    var cur_adr = $(this);
                    if (!cur_adr.find(".points > li").length) {
                        error_msg("<b>" + cur_obj.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: нет добавленных точек");
                    }
                    cur_adr.find(".points > li").each(function () {
                        if (!$(this).find(".point_name").val()) {
                            $(this).find(".point_name").addClass("error");
                            $(this).addClass("error");
                            error_msg("<b>" + cur_obj.find('h5').html() + "</b>, <b>" + cur_adr.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: нет названия");
                        }

                        var cur_point = $(this);
                        if (!cur_point.find(".measures > li").length) {
                            error_msg("<b>" + cur_point.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: нет добавленных измерений");
                        }
                        cur_point.find(".measures > li").each(function () {
                            if (!$(this).find(".measure_item").val()) {
                                $(this).find(".measure_item + .select2").addClass("error");
                                error_msg("<b>" + cur_obj.find('h5').html() + "</b>, <b>" + cur_adr.find('h5').html() + "</b> / <b>" + cur_point.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: не выбрано измерение");
                            }
                            if ($(this).find(".measure_count").val() - 0 < 1 || isNaN($(this).find(".measure_count").val() - 0)) {
                                $(this).find(".measure_count + .select2").addClass("error");
                                error_msg("<b>" + cur_obj.find('h5').html() + "</b>, <b>" + cur_adr.find('h5').html() + "</b> / <b>" + cur_point.find('h5').html() + "</b> / <b>" + $(this).find('h5').html() + "</b>: неправильное количество");
                            }
                        });
                    });
                });
            });

            return error_container.length == 0;
        }


        function form_confirm() {
            form_check();
            if (!error_out()) return false;

            $("form textarea[name='comment']").val($("#comment").val());
            $("form input[name='responsible']").val($("#responsible").val());

            $('#submit_modal').modal('show');
        }


        function li_del(obj) {
            $(obj).parents('li').first().remove();
        }


        var st = [];

        function recalc_timer(uid) {
            if (typeof st[uid] !== 'undefined') {
                clearTimeout(st[uid]);
                st[uid] = null;
            }
            st[uid] = setTimeout(function () {
                st[uid] = null;
                recalc(uid);
            }, 1000);
        }


        function recalc(uid) {
            block_elem = "li[uid='" + uid + "']";
            $(block_elem).block(block_default);

            $(block_elem).find('.course_type').on("change", function () {
                $(block_elem).find('.group_pad').addClass('d-none');
                if ($(this).val() == 'internal_room' || $(this).val() == 'webinar') {
                    $(block_elem).find('.group_pad').removeClass('d-none');
                }
            });

            $(block_elem).unblock();
        }

        $(document).ready(function () {
            $(".select2").select2({
                height: 50,
            });

            $(".datepicker").datepicker({
                format: "dd.mm.yyyy",
                startView: "days",
                minViewMode: "days",
                orientation: 'auto bottom',
                language: 'ru',
                autoclose: true
            });

            $("#btn_methodst_cancel_confirm").on("click", function () {
                methodist_cancel_submit();
            });


            $("ul.objects > li").each(function () {
                var uid = $(this).attr("uid");
                object__lab_filter(uid);

                $(this).find(".lab_object").on("change", function () {
                    object__lab_filter(uid);
                });
            });
        });

        function check_measures() {

            $(".measure_once").each(function() {
                base = $(this).find("option:selected").attr("base");
                object = $(this).parents('li.object').find(".object_type").val();
                icon = $(this).find(".row_icon");
                if(!$(this).find("option:selected").val() || (base && base.indexOf(';' + object + ';') >= 0)) {
                    // всё ок, снимаем выделение
                    icon.removeClass("text-white bg-danger cursor-help").attr("title", "");
                } else {
                    // не ок, красим
                    icon.addClass("text-white bg-danger cursor-help").attr("title", "Анализ будет сделан вне области аккредитации");
                }
            });
        }
        function object__lab_filter(uid) {
            check_measures();
            return false;
            var object = $("li[uid='" + uid + "']");
            var lab_object = object.find(".lab_object");


            // найдём все подходящие списки с измерениями и отфильтруем их
            object.find(".measure_item").each(function () {
                $(this).select2({
                    matcher: function (params, data) {
                        if (params.term && params.term.length > 0 && data.text.toLowerCase().indexOf(params.term.toLowerCase()) < 0)
                            return null;

                        str = $(data.element).attr("base");
                        if (!str)
                            return false;
                        res = str.indexOf(';' + lab_object.val() + ';') >= 0;

                        if (res) {
                            return data;
                        } else {
                            return null;
                        }
                    }
                });

                str = $(this).find("option:selected").attr("base");
                if (!str || str.indexOf(';' + lab_object.val() + ';') < 0)
                    $(this).val(0);
            });
        }

        function add_object(uid) {
            var block_elem = $("body");
            $(block_elem).block(block_default);
            $.ajax({
                url: "{{ route('evaluation.component.object', ['_token' => auth()->user()->ajax_token ]) }}",
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

        function add_address(uid) {
            var block_elem = $("body");
            $(block_elem).block(block_default);
            $.ajax({
                url: "{{ route('evaluation.component.address', ['_token' => auth()->user()->ajax_token ]) }}",
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

        function add_point(uid) {
            var block_elem = $("body");
            $(block_elem).block(block_default);
            $.ajax({
                url: "{{ route('evaluation.component.point', ['_token' => auth()->user()->ajax_token ]) }}",
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

        function add_measure(uid) {
            var block_elem = $("body");
            $(block_elem).block(block_default);
            $.ajax({
                url: "{{ route('evaluation.component.measure', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: {
                    uid: uid,
                    num: $("li[uid='" + uid + "'] .measures > li").length + 1
                },
                dataType: "html",
                success: function (result) {
                    $("li[uid='" + uid + "'] .measures").append(result);
                    $(block_elem).unblock();
                    rebind();
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

        function copy_measure(uid, data) {

            var block_elem = $("body");
            $(block_elem).block(block_default);
            $.ajax({
                url: "{{ route('evaluation.component.measure_paste', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: {
                    uid: uid,
                    data: data
                },
                dataType: "html",
                success: function (result) {
                    $("li[uid='" + uid + "'] .measures").append(result);
                    $(block_elem).unblock();
                    rebind();
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

        function rebind() {
            // objects (once)
            $(".objects > li.needbind").each(function () {
                var uid = $(this).attr("uid");
                console.log("OBJECT INIT (once): " + uid);

                $(this).removeClass('needbind');

                $(".objects > li[uid='" + uid + "']").find(".lab_object").on("change", function () {
                    object__lab_filter(uid);
                }).select2();

                $(this).find(".specialist_count").on("keyup change", function() {
                    var count = $(this).val();
                    var total  = count * $(this).attr("cost")-0;
                    $(this).parents('.input-group').find('.specialist_amount').html(cost_normalize(total));
                });
            });


            // measures (once)
            $(".measures > li.needbind").each(function () {
                var obj = $(this);
                $(this).find(".measure_item").select2();
                $(this).removeClass('needbind');
                var uid = obj.parents(".object[uid]").attr("uid");
                console.log("MEASURE INIT (once). Parent UID: " + uid);
                object__lab_filter(uid);

                $(this).find("input,select").on("change keyup", function() {
                    var row = $(this).parents('.measure_once');
                    setTimeout(() => {
                        count = row.find('.measure_count').val();
                        cost = row.find('.measure_cost').val();
                        total = count * cost;

                        row.find('.row_amount').html(cost_normalize(total));

                        check_measures();
                    }, 100);
                });

                $(this).find(".measure_count").on("change keyup", function() {
                    var count = $(this).val() - 0;
                    if(count > 1) {
                        obj.find(".measure_comment").removeClass("d-none");
                    } else {
                        obj.find(".measure_comment").addClass("d-none");
                    }
                });
            });


            $("select.measure_item").on("change", function () {
                if (!$(this).val()) {
                    $(this).parents('.measure_once').find('.cost_real').attr("title", "");
                    $(this).parents('.measure_once').find('.measure_cost').val(0);
                } else {
                    $(this).parents('.measure_once').find('.cost_real').attr("title", "Обычная цена за это измерение: " + cost_normalize(measure_cost[$(this).val()]) + " ₽");
                    // if($(this).parents('.row').find('.measure_cost').val() == '0')
                    $(this).parents('.measure_once').find('.measure_cost').val(measure_cost[$(this).val()]);
                }
            });
        }

        function measure_copy(uid) {
            $(".copy i").removeClass("fa-solid").removeClass("text-success").addClass("fa-regular");
            $(".paste_measure").addClass("d-none");
            if (window.measure_copy_uid == uid) {
                paste_measure_clear();
            } else {
                var row = $(".measure_once[uid='" + uid + "']");
                row.find(".copy i").removeClass("fa-regular").addClass("fa-solid").addClass("text-success");
                window.measure_copy_uid = uid
            }

            if (window.measure_copy_uid) {
                $(".paste_measure").removeClass("d-none");
            }
        }

        function paste_measure(uid_to) {
            if (!window.measure_copy_uid)
                return false;

            // проверка на тип объекта
            reference = $("[uid='" + window.measure_copy_uid + "']");
            pad = $("[uid='" + uid_to + "']");

            if (
                reference.parents('.object').find(".lab_object").val()
                !==
                pad.parents('.object').find(".lab_object").val()
            ) {
                alert("Типы объектов не совпадают, копирование невозможно!");
                return false;
            }

            window.measure_copy_to_uid = uid_to;
            $("#paste_count").val(1);
            $("#paste_modal").modal("show");
        }

        function paste_measure_confirm() {
            if (!window.measure_copy_to_uid)
                return false;

            reference = $("[uid='" + window.measure_copy_uid + "']");
            paste_count = $("#paste_count").val() - 0;
            if (paste_count < 1 || paste_count > 100)
                paste_count = 1;

            copy_measure(window.measure_copy_to_uid, {
                have: $("[uid='" + window.measure_copy_to_uid + "'] .measures li").length,
                row_count: paste_count,
                measure: reference.find(".select2").val(),
                count: reference.find(".measure_count").val(),
                cost: reference.find(".measure_cost").val()
            });

            rebind();

            paste_measure_clear();
        }

        function paste_measure_clear() {
            window.measure_copy_uid = null;
            $(".copy i").removeClass("fa-solid").removeClass("text-success").addClass("fa-regular");
            $("button.paste_measure").addClass("d-none");
        }


        // COPY - PASTE

        function copy_check(object) {
            have = object.find('.cb_copy:checked').length > 0;
            if (have) {
                object.find('.copy_measure').removeClass('d-none');
            } else {
                object.find('.copy_measure').addClass('d-none');
            }
        }

        function copy_clear_uid(uid) {
            copy_clear($(".object[uid='" + uid + "']"));
        }

        function copy_clear(object) {
            object.find(".cb_copy:checked").prop("checked", "");
            object.find(".copy_measure").addClass("d-none");
        }


        function copy_selected_measure(uid_to) {
            object = $(".points [uid='" + uid_to + "']").parents(".object");

            selected = object.find(".cb_copy:checked");

            if (selected.length == 0)
                return false;

            measures = [];
            $.each(selected, function (index, item) {
                measure = $(item).parents('.measure_once');
                measures.push({
                    measure: measure.find(".select2").val(),
                    count: measure.find(".measure_count").val(),
                    cost: measure.find(".measure_cost").val()
                });
            });

            data = {
                have: $("[uid='" + uid_to + "'] .measures li").length,
                measures: measures
            };


            var block_elem = $("body");
            $(block_elem).block(block_default);
            $.ajax({
                url: "{{ route('evaluation.component.measure_copy_selected', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: {
                    uid: uid_to,
                    data: data
                },
                dataType: "html",
                success: function (result) {
                    $("li[uid='" + uid_to + "'] .measures").append(result);
                    $(block_elem).unblock();
                    rebind();
                },
                error: function () {
                    toastr.error("Не получилось добавить измерение", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });

            copy_clear(object);
        }


        //

        function clone_point(uuid) {
            measures = [];
            point = $(".points li[uid='" + uuid + "']");
            point.find('.measure_once').each(function() {
                var measure = $(this);
                measures.push({
                    measure: measure.find(".select2").val(),
                    count: measure.find(".measure_count").val(),
                    cost: measure.find(".measure_cost").val()
                });
            });
            var address_uid = point.parents('.addresses li[uid]').attr("uid");
            data = {
                have: $(".address[uid='" + address_uid + "'] .points li.point").length,
                measures: measures
            };

            var block_elem = $("body");
            $(block_elem).block(block_default);
            $.ajax({
                url: "{{ route('evaluation.component.point_duplicate', ['_token' => auth()->user()->ajax_token ]) }}",
                type: "GET",
                data: {
                    address_uid: address_uid,
                    data: data
                },
                dataType: "json",
                success: function (json) {
                    $(".addresses li.address[uid='" + address_uid + "'] .points").append(json.point);
                    var point = $(".addresses li.address[uid='" + address_uid + "'] .points .point:last-of-type");
                    point.find('.measures').html(json.measures);

                    $(block_elem).unblock();
                    rebind();
                },
                error: function () {
                    toastr.error("Не получилось добавить измерение", "Это провал!", {
                        progressBar: true,
                        "timeOut": 30000,
                    });
                    $(block_elem).unblock();
                }
            });
        }
        $(document).ready(function () {
            $(".cb_copy").on("change", function () {
                object = $(this).parents(".object");
                copy_check(object);
            });
        });

        rebind();
    </script>
@endsection
