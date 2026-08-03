@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="keys_form">
        <div class="card-body">
            <div class="form-group mb-3 row pb-3">
                <label for="inp_key" class="col-sm-3 text-end control-label col-form-label"></label>
                <div class="col-sm-9">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="flexCheckChecked" name="active" @checked($key->active)>
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
                    <input value="{{ $key->key }}" name="key" type="text" class="form-control w-100 text-start" id="inp_key" required>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_closed_at" class="col-sm-3 text-end control-label col-form-label">Дата действия, с
                    <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <input name="active_from" type="date" min="0" class="form-control w-25" id="date_from" value="{{ $key->active_from->format("Y-m-d") }}" required>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_closed_at" class="col-sm-3 text-end control-label col-form-label">Дата действия, по
                    <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <input name="active_to" type="date" min="0" class="form-control w-25" id="date_to" value="{{ $key->active_to->format("Y-m-d") }}" required>
                </div>
            </div>

            <div class="form-group mb-3 row pb-3">
                <label for="inp_closed_at" class="col-sm-3 text-end control-label col-form-label">Спецификация</label>
                <div class="col-sm-9">
                    @if($specs->isNotEmpty())
                        <x-ui.select.single value="{{ $key->specification->id ?? null }}" name="specification" :items="$specs" value-name="name_full" id="id"></x-ui.select.single>
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

        function key_delete() {
            if(!confirm("Вы действительно хотите удалить этот ключ?")) return;
            $.ajax({
                url: "{{ route('api.license-keys.delete', [$key, '_token' => _token() ]) }}",
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
                url: "{{ route('api.license-keys.update', [$key, '_token' => _token() ]) }}",
                type: "POST",
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
            $("form#keys_form input, form#keys_form select").on("keyup change", function() {
                box_check_form();
            }) ;
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">

        <x-ui.button.default btn_type="danger" onclick="javascript:key_delete();">
            <x-ui.icon.solid icon="fa-trash" class="me-1"></x-ui.icon.solid>
            <span>Удалить</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" >
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
