@extends('components.sidebar.offcanvas-right')

@section('body')
<div class="card">
    <div class="card-body">
        <div class="card-table">
            <div class="tr">
                <span class="th">Текущий</span>
                <span class="td">
                    {!! _docnumber($row->number) !!}
                </span>
            </div>
            <div class="tr">
                <span class="th">Тип документа</span>
                <span class="td">
                    {{ $params['module_name'] }}
                </span>
            </div>
            <div class="tr invisible" id="document_new_number">
                <span class="th">Новый</span>
                <span class="td">
                    <strong>12345678</strong>
                </span>
            </div>
            <h4 class="mt-3">Новый номер</h4>
            <div class="input-group justify-content-start">
                <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                <input id="document_dp" @if(0 && !empty($params['days_from']))data-date-start-date="-{{$params['days_from']}}d" @endif @if(0 && !empty($params['days_to']))data-date-end-date="+{{$params['days_to']}}d" @endif readonly style="width: 80px" type="text" class="form-control flex-grow-0 text-center" value="{{ \Illuminate\Support\Str::substr($row->number, 0, 6) }}">
                <span class="input-group-text"><i class="fa-regular fa-hashtag"></i></span>
                <input id="document_num" style="width: 70px" type="number" class="form-control flex-grow-0" value="{{ \Illuminate\Support\Str::substr($row->number, 6) }}" maxlength="3">
                <div class="document_loader align-items-center ms-2 d-flex align-items-center">
                    <span id="error" class="d-none"><i class="fa-solid fa-xmark text-danger"></i></span>
                    <span id="success" class="d-none"><i class="fa-solid fa-check text-success"></i></span>
                    <span id="spinner" class="d-none spinner-border spinner-border-sm" role="status">
                        <span class="sr-only"></span>
                    </span>
                </div>
            </div>

            <button class="mt-3 btn btn-primary d-none" type="button" id="document_confirm">
                <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Сохранить
            </button>

        </div>
    </div>


    <script>
        $("#document_dp").datepicker({
            format: "yymmdd",
            startView: "days",
            minViewMode: "days",
            orientation: 'auto bottom',
            language: 'ru',
            autoclose: true
        }).on('change', function (e) {
            document_number_check();
        });

        $("#document_num").on("keyup change", function() {
            document_number_check();
        });

        function document_number_check() {
            $(".document_loader #spinner").removeClass("d-none");
            if($("#document_num").val() < 1) $("#document_num").val(1);
            if($("#document_num").val() > 99999) $("#document_num").val(99999);

            $.ajax({
                url: "{{ route('api.document_number.check', [$row, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                dataType: "json",
                data: {
                    'date': $("#document_dp").val(),
                    'number': $("#document_num").val(),
                },
                success: function (result) {
                    $(".document_loader > *").addClass("d-none");
                    if(result.status == 'success') {
                        $(".document_loader > #success").removeClass("d-none");
                        $("#document_confirm").removeClass("d-none");
                        $("#document_new_number").removeClass("invisible").find("strong").html(result.number);
                    } else {
                        if(result.status != 'same') $(".document_loader > #error").removeClass("d-none");
                        $("#document_confirm").addClass("d-none");
                        $("#document_new_number").addClass("invisible");
                    }
                },
                error: function () {
                    $(".document_loader > *").addClass("d-none");
                }
            });
        }

        function document_number_submit()
        {
            var block_elem = $("body");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
                baseZ: 100000,
                overlayCSS: {
                    backgroundColor: "#000",
                    opacity: 0.5,
                    cursor: "wait",
                },
                css: {
                    border: 0,
                    padding: 0,
                    backgroundColor: "transparent",
                },
            });
            $.ajax({
                url: "{{ route('api.document_number.set', [$row, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                dataType: "json",
                data: {
                    'date': $("#document_dp").val(),
                    'number': $("#document_num").val(),
                },
                success: function (result) {
                    toastr.success("Номер документа успешно изменён", "Это успех!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });

                    $("[doc-number='{{$row->number}}']").attr('doc-number', result.number).html(result.number_decorate).parents("a").attr("onclick", result.route_save);
                    $("#offcanvas > div").offcanvas('hide');

                    $(block_elem).unblock();
                },
                error: function () {
                    toastr.error("Не получилось изменить номер документа", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }

        $("#document_confirm").on("click", function() {

            Swal.fire({
                title: "Вы уверены?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Да",
                cancelButtonText: "Нет",
            }).then((result) => {
                if (result.value) {
                    document_number_submit();
                }
            });


        });

    </script>
</div>
@endsection

