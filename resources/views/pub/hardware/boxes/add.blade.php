@extends('components.box.box-static-large')

@section('title')
    <div>
        <h3 class="m-0">Добавление записи</h3>
    </div>
@endsection


@section('body')
    <div class="fs-2 text-secondary px-1 fw-bold">
        <div class="fs-5 fw-bold mb-1">Наименование <span class="required text-danger">*</span></div>
        <textarea id="ta_name"></textarea>

        <div class="fs-5 fw-bold mb-1 mt-3">Количество</div>
        <textarea id="ta_count"></textarea>

        <div class="fs-5 fw-bold mb-1 mt-3">Параметры</div>
        <textarea id="ta_params"></textarea>

        <div class="form-check fs-2 mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="cb_all" name="cb_all" @checked(!empty($variant->task) && $variant->proposal->variants->pluck('task')->unique()->count() == 1)>
            <label class="form-check-label fs-3" for="cb_all">
                Сохранить во все варианты
            </label>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            CKEDITOR.replace("ta_name", {
                height: 100,
                toolbar: [{name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']}, {name: 'paragraph', items: ['NumberedList', 'BulletedList']}, {name: 'styles', items: ['Styles']}]
            }).on("change", function() {
                if(!stripTags(CKEDITOR.instances['ta_name'].getData())) {
                    $("#box #btn_submit").attr("disabled", "disabled");
                } else {
                    $("#box #btn_submit").removeAttr("disabled");
                }
            });
            CKEDITOR.replace("ta_count", {
                height: 100,
                toolbar: [{name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']}, {name: 'paragraph', items: ['NumberedList', 'BulletedList']}, {name: 'styles', items: ['Styles']}]
            });
            CKEDITOR.replace("ta_params", {
                height: 100,
                toolbar: [{name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']}, {name: 'paragraph', items: ['NumberedList', 'BulletedList']}, {name: 'styles', items: ['Styles']}]
            });

        });

        function save() {
            if(!stripTags(CKEDITOR.instances['ta_name'].getData())) return;
            if(!confirm("Вы действительно хотите сохранить данный?")) return;

            $("body").block();

            $.ajax({
                url: "{{ route('api.hardware.store', ['_token' => _token() ]) }}",
                method: "PUT",
                dataType: "json",
                data: {
                    id: '{{ $variant->id }}',
                    name: CKEDITOR.instances['ta_name'].getData(),
                    count: CKEDITOR.instances['ta_count'].getData(),
                    params: CKEDITOR.instances['ta_params'].getData(),
                    cb_all: $("#cb_all").prop("checked") ? 1 : 0,
                },
                success: function (response) {
                    if($("#cb_all").prop("checked")) {
                        $.each(response.html, function(variant, html) {
                            $(`#hardware_table[variant='${variant}']`)[0].outerHTML = html;
                        })

                    } else {
                        $("#hardware_table[variant='{{ $variant->id }}']")[0].outerHTML = response.html;
                    }



                    $("body").unblock();
                    box_close();
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

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" disabled>
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection


