@extends('layouts.layout')

@section('styles')
    <link href="/dist/modules/jstree/themes/default/style.min.css" rel="stylesheet"/>
@endsection


@section('content')
    @csrf
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="card card-body" id="catalog-pad">
                    <div id="tree" data-url="{{ route('api.lab-object.index') }}"></div>
                    <a href="javascript:void(0)" id="btn-add-contact" class="btn btn-info mt-4" data-bs-toggle="modal"
                       data-bs-target="#delete-modal">
                        Сохранить дерево</a>
                </div>
            </div>
            <div class="col-lg-6 d-none" id="object_edit">

            </div>
        </div>
    </div>

    <div
        id="delete-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Сохранение дерева</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p>
                        Сохранение дерева необратимо!
                        <br/>
                        Если вы удалили записи, содержащие вложенные страницы, то они также будут удалены!
                    </p>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Не сохранять
                    </button>
                    <button
                        type="button"
                        onclick="javascript:tree_save();"
                        data-bs-dismiss="modal"
                        class="
                                btn btn-light-danger
                                text-danger
                                font-weight-medium
                              "
                    >
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

    <script src="/dist/modules/jstree/jstree.min.js"></script>
    {{--    <script src="/dist/js/pages/menu.js"></script>--}}

    <script>
        window.lock_panel = true;
        $(document).ready(function () {
            var tree = $('#tree').jstree({
                "core": {
                    "icon": "/images/tree/save.png",
                    "check_callback": true,
                    "data": {
                        "url": $('#tree').data("url") + '?_token={{ auth()->user()->ajax_token }}',
                        "dataType": "json" // needed only if you do not supply JSON headers
                    },
                    "close_all": true,
                    "multiple": false
                },
                "plugins": ["dnd", "contextmenu", "state"]
            }).on("changed.jstree", function (e, data) {
                if (data.selected.length) {
                    if (!window.lock_panel) show_panel(data.instance.get_node(data.selected[0]).id);
                }
            }).on("ready.jstree", function() {
                $('#tree').jstree('close_all');
            });


            $('#html li').each(function () {
                $("#html").jstree().disable_node(this.id);
            })
            window.tree = tree;


            $(".select2").select2();
            setTimeout(() => {
                window.lock_panel = false;
            }, 1000);
        });


        function tree_recursion(id) {
            var list = [];
            var is_exist = false;
            $("#" + id + ">ul>li").filter(".jstree-node").each(function (index, element) {
                is_exist = true;
                childs = tree_recursion($(element).attr('id'));
                if (childs == false) list.push({
                    "id": $(element).attr('id'),
                    "name": $(element).find("a").html().replace(/<\/?[^>]+>/gi, '')
                });
                else list.push({
                    "id": $(element).attr('id'),
                    "name": $(element).find("a").html().replace(/<\/?[^>]+>/gi, ''),
                    "children": childs
                });
            });

            if (is_exist == false) return false;
            return list;
        }


        function tree_save() {
            var instance = $('#tree').jstree(true);

            instance.open_all();
            var data = JSON.stringify(tree_recursion('tree'));
            //instance.close_all();

            $.ajax({
                url: "{{ route('api.lab-object.update') }}",
                global: false,
                type: "POST",
                data: ({
                    data: data,
                    _token: '{{ auth()->user()->ajax_token }}'
                }),
                dataType: "json",
                success: function (result) {
                    if (result.status == 'success') {
                        toastr.success("Меню сохранено", "Это успех!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    } else {
                        toastr.error("Не получилось сохранить меню", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                    // $(".menu3_msg").html("Дерево успешно сохранено!").show().parents("tr").fadeIn(500);
                    // setTimeout("hide_msg();", 5000);
                    // var instance = $('#html').jstree(true);
                    // instance.refresh();
                    // $("#panel_pad").html("");
                }
            });
        }

        function show_panel(id) {
            $("#object_edit").removeClass("d-none");
            $.ajax({
                url: "{{ route('api.lab-object.view') }}/" + id,
                global: false,
                type: "POST",
                data: ({
                    _token: '{{ auth()->user()->ajax_token }}'
                }),
                dataType: "json",
                success: function (answer) {
                    if (answer.result == 'success') {
                        $("#object_edit").html(answer.html);

                    } else {
                        toastr.error("Произошла ошибка!", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                }
            });
        }
    </script>
    <script src="/assets/libs/jquery.repeater/jquery.repeater.min.js"></script>
@endsection

