@extends('components.box.box-static-large')

@section('body')
    <div class="row">
        <div class="col-12">
            <x-files.dropzone :files="$files ?? []" mode="evaluation" id="{{ $evaluation->id }}" block="other" blockid="{{ $evaluation->id }}" name="Файлы"
                              class="mt-3 mb-0" box="1"/>
        </div>
    </div>

    <script>
        function save()
        {
            Swal.fire({
                html: `<div class="fs-6 fw-bold">Вы уверены, что хотите сохранить данные?</div>`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",

                cancelButtonColor: "#d33",
                confirmButtonText: "Да",
                cancelButtonText: "Нет",
            }).then((result) => {
                if (result.value) {
                    var block_elem = $("body");
                    block_elem.block(block_default);
                    $.ajax({
                        url: '{{ route('api.evaluation.files_submit', [$evaluation, '_token' => auth()->user()->ajax_token ]) }}',
                        method: "POST",
                        dataType: "json",
                        success: function (response) {
                            block_elem.unblock();
                            if (response.status == 'success') {
                                location.reload();
                            } else {
                                toastr.error("Не получилось загрузить", "Это провал!", {
                                    progressBar: true,
                                    "timeOut": 3000,
                                });
                                block_elem.unblock();
                            }
                        },
                        error: function () {
                            toastr.error("Не получилось загрузить", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                            block_elem.unblock();
                        }
                    });

                }
            });
        }
    </script>

@endsection


@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default btn_type="info" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
