@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="generate" >
        <div class="row">
            <div class="col-12 d-flex justify-content-start align-items-start">
                <span class="fs-6">
                    <x-ui.icon.regular icon="fa-circle-1"/>
                </span>
                <div class="fs-6 ms-3 flex-grow-1">
                    Перейдите по адресу
                    <input type="text" class="form-control text-dark-danger" value="https://b29439504.bi.bitrix24.ru/sqllab">
                </div>
            </div>

            <div class="col-12 d-flex justify-content-start align-items-start mt-4">
                <span class="fs-6">
                    <x-ui.icon.regular icon="fa-circle-2"/>
                </span>
                <div class="fs-6 ms-3 flex-grow-1">
                    Выполните запрос
                    <textarea class="form-control" rows="6">{!! $query !!}</textarea>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-start align-items-start mt-4">
                <span class="fs-6">
                    <x-ui.icon.regular icon="fa-circle-3"/>
                </span>
                <div class="fs-6 ms-3 flex-grow-1">
                    Скопируйте результат в буфер и вставьте ниже
                    <textarea class="form-control" id="data"></textarea>
                </div>
            </div>

        </div>
    </form>

    <script>
        function recalc() {
            data = $("textarea#data").val();
            if(data) {
                $("#btn_save").removeAttr("disabled");
                return true;
            } else {
                $("#btn_save").attr("disabled", 1);
                return false;
            }
        }

        function save() {
            if(!recalc() || !confirm("Вы действительно хотите удалить текущие данные и внести новые?")) return;

            $("body").block(block_default);

            $.ajax({
                url: "{{ route('api.bitrix.sync.refresh', [$table, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: {
                    data: $("textarea#data").val()
                },
                success: function (response) {
                    if (response.result == 'success') {
                        box_close();
                        toastr.success(response.answer, "Это успех!");
                        tr = $("tr[table='{{ $table }}']");
                        tr.find(".count").html(response.count);
                        tr.find(".date").html(response.date);

                        $("body").unblock();
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

        <x-ui.button.default id="btn_save" btn_type="success" onclick="javascript:save();" disabled>
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Синхронизировать</span>
        </x-ui.button.default>
    </div>
@endsection
