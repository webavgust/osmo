@extends('layouts.layout')

@section('content')
    <link
        rel="stylesheet"
        type="text/css"
        href="/assets/libs/ckeditor/samples/toolbarconfigurator/lib/codemirror/neo.css"
    />

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">

                    <form id="form_create">
                        <div class="card-body">
                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Название<span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input required name="name" type="text" class="form-control " id="tb-fname"
                                           placeholder="" value="{{ $row->name }}">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Группа</label>
                                <div class="col-sm-4">
                                    <input  name="group" type="text" class="form-control " id="tb-fname"
                                            placeholder="" value="{{ $row->group  }}">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Язык</label>
                                <div class="col-sm-4">
                                    <div class="d-flex">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="lang" id="lang_ru" value="ru" @checked($row->lang == 'ru')>
                                            <label class="form-check-label" for="lang_ru">
                                                Русский
                                            </label>
                                        </div>
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="radio" name="lang" id="lang_en" value="en" @checked($row->lang == 'en')>
                                            <label class="form-check-label" for="lang_en">
                                                English
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Расширение содержание</label>
                                <div class="col-sm-8">
                                    <textarea id="extended" name="extended" type="text" class="form-control d-none"
                                              placeholder="">{{ $row->extended }}</textarea>

                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Примечание</label>
                                <div class="col-sm-8">
                                    <textarea id="notice" name="notice" type="text" class="form-control d-none"
                                              placeholder="">{{ $row->notice }}</textarea>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Длительность (ч.)</label>
                                <div class="col-sm-1">
                                    <input name="count" type="text" class="form-control text-end " id="tb-fname"
                                           placeholder="" value="{{ $row->count ?? 0 }}">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Стоимость</label>
                                <div class="col-sm-1">
                                    <input name="cost" type="text" class="form-control text-end " id="tb-fname"
                                           placeholder="" value="{{ $row->cost ?? 0 }}">
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-sm-4 col-ml">
                                    <button type="button" id="submit" class=" btn btn-info font-weight-medium rounded-pill px-4 disabled" onclick="javascript:sbm();">
                                        <div class="d-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-send feather-sm fill-white me-2">
                                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                            </svg>
                                            Сохранить
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12">
                <div class="d-md-flex align-items-center mt-3">

                    <div class="ms-auto mt-3 mt-md-0">
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    @parent

    <script src="/assets/libs/ckeditor/ckeditor.js"></script>
    <script>
        $(document).ready(function() {
            $("input[required],select[required]").on("keyup change", function() {
                form_check();
            }) ;
            form_check();
        });

        CKEDITOR.replace("extended", {
            height: 150,
            toolbar: [
                {
                    name: 'basicstyles',
                    items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']
                },
                {
                    name: 'paragraph',
                    items: ['NumberedList', 'BulletedList']
                },
                {
                    name: 'styles',
                    items: ['Styles']
                }
            ]
        });

        CKEDITOR.replace("notice", {
            height: 150,
            toolbar: [
                {
                    name: 'basicstyles',
                    items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']
                },
                {
                    name: 'paragraph',
                    items: ['NumberedList', 'BulletedList']
                },
                {
                    name: 'styles',
                    items: ['Styles']
                }
            ]
        });

        function form_check() {
            var err = 0;
            $("input[required],select[required]").each(function() {
               if($(this).attr("type") == "checkbox") {
                   if(!$(this).prop("checked")) err++;
               } else if(!$(this).val()) err++;
            });

            if(err) {
                $("#submit").addClass("disabled");
            } else {
                $("#submit").removeClass("disabled");
            }

            return err == 0;
        }


        function sbm() {
            if(!form_check) return;
            if(!confirm("Вы действительно хотите это сделать?")) return;


            $("body").block(block_default);

            var editorInstance = CKEDITOR.instances.extended;
            var editorData = editorInstance.getData();
            $("form #extended").val(editorData);

            var editorInstance = CKEDITOR.instances.notice;
            var editorData = editorInstance.getData();
            $("form #notice").val(editorData);


            $.ajax({
                url: "{{ route('api.work.update', [$row, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: $("form#form_create").serialize(),
                success: function (response) {
                    if (response.result == 'success') {
                        location.replace(response.url);
                    } else {
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });
        }
    </script>

@endsection
