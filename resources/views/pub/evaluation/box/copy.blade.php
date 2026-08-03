@extends('components.box.box-static-large')

@section('body')
    <div class="row">
        <div class="col-12">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="inp_copy" placeholder="" autocomplete="off">
                <label for="tb-fname">Поиск приложения откуда будет скопированы данные</label>
            </div>
        </div>

        <div class="col-12">
            <div id="ajax_result"></div>
        </div>
    </div>

    <script>

        function copy_submit(source)
        {
            Swal.fire({
                html: `<div class="fs-6 fw-bold">Вы уверены, что хотите скопировать приложение?</div>`
                    + `<div class="fs-3 text-danger">Вы потеряете текущие данные. Это действие необратимо</div>`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Да",
                cancelButtonText: "Нет",
            }).then((result) => {
                if (result.value) {
                    var block_elem = $("body");
                    block_elem.block(block_default);
                    $.ajax({
                        url: '{{ route('api.evaluation.copy', [$evaluation, '_token' => auth()->user()->ajax_token ]) }}',
                        method: "POST",
                        data: {
                            source: source
                        },
                        dataType: "json",
                        success: function (response) {
                            block_elem.unblock();
                            if (response.status == 'success') {
                                location.replace('{{ route('evaluation.edit', $evaluation) }}');
                            } else {
                                toastr.error("Не получилось скопировать", "Это провал!", {
                                    progressBar: true,
                                    "timeOut": 3000,
                                });
                                block_elem.unblock();
                            }
                        },
                        error: function () {
                            toastr.error("Не получилось отправить на проверку", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                            block_elem.unblock();
                        }
                    });

                }
            });
        }
        function copy_search(query)
        {
            $.ajax({
                url: "{{ route('api.evaluation.search', [$evaluation, '_token' => auth()->user()->ajax_token]) }}",
                type: "GET",
                data: {
                    query: query,
                },
                dataType: "json",
                success: function (response) {
                    $("#ajax_result").html(response.html);
                },
            });
        }

        $(document).ready(function() {
            $("#inp_copy").on("keyup change", function() {
                clearTimeout(window.copy_to);
                window.copy_to = setTimeout(() => {
                    copy_search($("#inp_copy").val());
                }, 800);
            });
        });
    </script>

@endsection

