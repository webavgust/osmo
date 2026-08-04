@extends('components.box.box-static-large')

@section('body')
    <div class="card">
        <form id="filter" class="form-horizontal r-separator border-top">
            <div class="card-body">


                <!-- Статус -->
                <div class="form-group mb-3 row pb-3">
                    <label for="inputEmail3" class="col-sm-3 text-end control-label col-form-label">Статус</label>
                    <div class="col-sm-9">
                        <x-ui.select.multiple
                            :selected="$filter['stage_name'] ?? []"
                            name="filter[stage_name][]" class="select2" :items="$stage_name" blank-ignore="1" key-as-value="1"/>
                    </div>
                </div>

                <!-- Ответственный менеджер -->
                <div class="form-group mb-3 row pb-3">
                    <label for="inputEmail3" class="col-sm-3 text-end control-label col-form-label">Ответственный менеджер</label>
                    <div class="col-sm-9">
                        <x-ui.select.multiple
                            :selected="$filter['assigned_by'] ?? []"
                            name="filter[assigned_by][]" class="select2" :items="$assigned_by" blank-ignore="1" key-as-value="1"/>
                    </div>
                </div>

                <!-- Вероятность -->
                <div class="form-group mb-3 row pb-3">
                    <label for="inputEmail3" class="col-sm-3 text-end control-label col-form-label">Вероятность</label>
                    <div class="col-sm-9">
                        <x-ui.select.multiple
                            :selected="$filter['assigned_by'] ?? []"
                            name="filter[assigned_by][]" class="select2" :items="$assigned_by" blank-ignore="1" key-as-value="1"/>
                    </div>
                </div>

                <!-- Страна получения средств -->
                <div class="form-group mb-3 row pb-3">
                    <label for="inputEmail3" class="col-sm-3 text-end control-label col-form-label">Страна получения средств</label>
                    <div class="col-sm-9">
                        <x-ui.select.multiple
                            :selected="$filter['assigned_by'] ?? []"
                            name="filter[assigned_by][]" class="select2" :items="$assigned_by" blank-ignore="1" key-as-value="1"/>
                    </div>
                </div>



            </div>
        </form>
    </div>

    <script>
        function save() {
            $("body").block(block_default);

            $.ajax({
                url: "{{ route('api.bitrix.dashboard.set_filter', ['_token' => _token() ]) }}",
                type: "POST",
                data: $("form#filter").serialize(),
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error(response.answer, "Это провал!", {
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
            $(".select2").select2({
                dropdownParent: $(".modal  .modal-content"),
                width: '100%',
            });
            $("textarea#data").on("keyup change", function() {
                recalc();
            });
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_save" btn_type="success" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
