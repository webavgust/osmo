@extends('layouts.layout')

@section('styles')
    <link href="/dist/modules/jstree/themes/default/style.min.css" rel="stylesheet"/>
@endsection


@section('content')
    @csrf
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3">
                <div class="card card-body" id="catalog-pad">
                    <div id="tree" data-url="{{ route('api.menu.index') }}"></div>
                    <a href="javascript:void(0)" id="btn-add-contact" class="btn btn-primary mt-4" data-bs-toggle="modal"
                       data-bs-target="#delete-modal">
                        Сохранить дерево</a>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="card card-body">

                    <div class="card">
                        <div class="card-body border-bottom">
                            <h4 class="card-title">Редактирование пункта меню</h4>
                            <h6 class="card-subtitle mb-0">
                                Здесь описание
                            </h6>
                        </div>
                        <form id="form_menu" data-link="{{ route('menu.update') }}/{{ old('id') }}" action="" method="POST"
                              class="needs-validation novalidate">
                            @csrf
                            <div class="card-body">
                                <div class="mb-3 row">
                                    <label for="tb-fname"
                                           class="col-sm-4 col-form-label fw-semibold text-lg-end"></label>
                                    <div class="col-sm-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                   id="flexCheckDefault" name="active">
                                            <label class="form-check-label" for="flexCheckDefault">
                                                Активность
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="tb-fname"
                                           class="col-sm-4 col-form-label fw-semibold text-lg-end"><span
                                            class="text-danger me-1">*</span>Название:</label>
                                    <div class="col-sm-4">
                                        <input name="name" type="text" class="form-control " id="tb-fname"
                                               placeholder="" required="" value="{{ old('name') }}">

                                        @error('name')
                                        <div
                                            class=" alert alert-danger d-flex align-items-center p-2 mt-1"
                                            role="alert">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>


                                <div class="mb-3 row">
                                    <label for="tb-access"
                                           class="col-sm-4 col-form-label fw-semibold text-lg-end">Доступ:</label>
                                    <div class="col-sm-4">

                                        <select class="select2 form-control" multiple="multiple" style="height: 36px; width: 100%" name="access[]" id="access_select">
                                            @foreach($groups as $group)
                                                <optgroup label="{{$group->name}}">
                                                    @foreach($group->accesses()->get() as $access)
                                                        <option value="{{$access->code}}" data-name="asdasd">{{$group->name}} / {{$access->name}}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>


                                <div class="mb-3 row">
                                    <label for="tb-url"
                                           class="col-sm-4 col-form-label fw-semibold text-lg-end">Ссылка:</label>
                                    <div class="col-sm-4">

                                        <input name="url" type="text" class="form-control " id="tb-url"
                                               placeholder="" value="{{ old('url') }}">


                                        @error('url')
                                        <div
                                            class=" alert alert-danger d-flex align-items-center p-2 mt-1"
                                            role="alert">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="tb-icon"
                                           class="col-sm-4 col-form-label fw-semibold text-lg-end">Иконка:</label>
                                    <div class="col-sm-4">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="" id="menu_icon"></i></span>
                                            </div>
                                            <input name="icon" type="text" class="form-control " id="tb-icon"
                                                   placeholder="" value="{{ old('icon') }}">
                                        </div>

                                    </div>
                                </div>


                                <div class="row justify-content-center">
                                    <div class="col-sm-4 col-ml">
                                        <button type="submit"
                                                class=" btn btn-primary fw-semibold rounded-pill px-4">
                                            <div class="d-flex align-items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                     stroke-width="2"
                                                     stroke-linecap="round" stroke-linejoin="round"
                                                     class="feather feather-send feather-sm fill-white me-2">
                                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                                </svg>
                                                @lang('button.save')
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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
                                fw-semibold
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
        $(document).ready(function () {

            var tree = $('#tree').jstree({
                "core": {
                    "icon": "/images/tree/save.png",
                    "check_callback": true,
                    "data": {
                        "url": $('#tree').data("url") + '?_token={{ auth()->user()->ajax_token }}',
                        "dataType": "json" // needed only if you do not supply JSON headers
                    },
                    "multiple": false
                },
                "plugins": ["dnd", "contextmenu", "state"]
            }).on("changed.jstree", function (e, data) {
                if (data.selected.length) {
                    if (!window.lock_panel) show_panel(data.instance.get_node(data.selected[0]).id);
                }
            });

            $('#html li').each(function () {
                $("#html").jstree().disable_node(this.id);
            })
            window.tree = tree;



            $(".select2").select2();


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
                url: "{{ route('api.menu.update') }}",
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
            $.ajax({
                url: "{{ route('api.menu.view') }}/" + id,
                global: false,
                type: "POST",
                data: ({
                    _token: '{{ auth()->user()->ajax_token }}'
                }),
                dataType: "json",
                success: function (result) {
                    if (result.id) {
                        form = $("form#form_menu");
                        form.attr("action", form.data('link') + result.id);
                        form.find('[name="active"]').prop('checked', result.active);
                        form.find('[name="name"]').val(result.name);
                        form.find('[name="url"]').val(result.url);
                        form.find('[name="icon"]').val(result.icon);
                        form.find('#access_select').val(result.access);
                        form.find('#access_select').trigger('change');
                        form.find('#menu_icon').attr("class", result.icon ? result.icon : 'mdi mdi-window-close');



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

