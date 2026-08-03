@extends('layouts.layout')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">

                    <form id="form_create">
                        <div class="card-body">
                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Активность</label>
                                <div class="col-sm-4">
                                    <div class="form-check pt-1">
                                        <input  class="form-check-input" name="active" type="checkbox" value="1" id="flexCheckChecked" checked="">
                                        <label class="form-check-label" for="flexCheckChecked">

                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Название<span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input required name="name" type="text" class="form-control " id="tb-fname"
                                           placeholder="" value="{{ old('name') }}">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Тип партнёра<span
                                        class="text-danger">*</span></label>
                                <div class="col-2">
                                    <x-ui.select.single name="type" required :items="$types" id="key" value-name="label"></x-ui.select.single>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Уровень партнёрства<span
                                        class="text-danger">*</span></label>
                                <div class="col-2">
                                    <x-ui.select.single name="grade" required :items="$grades" id="key" value-name="label"></x-ui.select.single>
                                </div>
                            </div>


                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Регион</label>
                                <div class="col-2">
                                    <input name="region" type="text" class="form-control " id="tb-fname"
                                           placeholder="" value="{{ old('name') }}">
                                </div>
                            </div>


                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Контактное лицо</label>
                                <div class="col-4">
                                    <input name="contact" type="text" class="form-control " id="tb-fname"
                                           placeholder="" value="{{ old('contact') }}">
                                </div>
                            </div>


                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Телефон</label>
                                <div class="col-4">
                                    <input name="phone" type="text" class="form-control " id="tb-fname"
                                           placeholder="" value="{{ old('phone') }}">
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
                                            @lang('button.create')
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

    <script>
        $(document).ready(function() {
            $("input[required],select[required]").on("keyup change", function() {
                form_check();
            }) ;
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
            $.ajax({
                url: "{{ route('api.partner.store', ['_token' => _token() ]) }}",
                type: "PUT",
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
