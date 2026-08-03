@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="keys_form">
        <div class="card-body">
            <div class="form-group mb-3 row pb-3">
                <label for="inp_key" class="col-sm-3 text-end control-label col-form-label"></label>
                <div class="col-sm-9">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="flexCheckChecked" checked="" name="active">
                        <label class="form-check-label" for="flexCheckChecked">
                            Активный
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_key" class="col-sm-3 text-end control-label col-form-label">Ключ
                    <span class="text-danger">*</span>
                </label>
                <div class="col-sm-9">
                    <input value="" name="key" type="text" min="0" class="form-control w-100 text-start" id="inp_key" required>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_closed_at" class="col-sm-3 text-end control-label col-form-label">Дата действия, с
                    <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <input name="active_from" type="date" min="0" class="form-control w-25" id="date_from" value="" required>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_closed_at" class="col-sm-3 text-end control-label col-form-label">Дата действия, по
                    <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <input name="active_to" type="date" min="0" class="form-control w-25" id="date_to" value="" required>
                </div>
            </div>

            <div class="form-group mb-3 row pb-3">
                <label for="inp_closed_at" class="col-sm-3 text-end control-label col-form-label">Спецификация</label>
                <div class="col-sm-9">
                    @if($specs->isNotEmpty())
                        <x-ui.select.single name="specification" :items="$specs" value-name="name_full" id="id"></x-ui.select.single>
                    @else
                        Нет созданных спецификаций
                    @endif
                </div>
            </div>
        </div>
    </form>

    <script>
        function box_check_form() {
            var err = false;
            $("form#keys_form input[required]").each(function() {
               if(!$(this).val()) err = true;
            });

            if(err) {
                $("#btn_submit").attr("disabled", "1");
            } else {
                $("#btn_submit").removeAttr("disabled");
            }

            return !err;
        }

        function license_key_delete() {
            if(!confirm("Вы действительно хотите удалить этот ключ?")) return;
            $.ajax({
                url: "{{ route('api.license-keys.delete', [$company, '_token' => _token() ]) }}",
                type: "DELETE",
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error("Не получилось удалить запись", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось удалить запись", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });

        }
        function save() {
            if (!box_check_form()) return;

            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.license-keys.create', [$company, '_token' => _token() ]) }}",
                type: "PUT",
                dataType: "json",
                data: $("form#keys_form").serialize(),
                success: function (response) {
                    if (response.result == 'success') {
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
                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });
        }


        $(document).ready(function() {
            $("form#keys_form input, form#form select").on("keyup change", function() {
                box_check_form();
            }) ;
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <div/>
        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" disabled >
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
