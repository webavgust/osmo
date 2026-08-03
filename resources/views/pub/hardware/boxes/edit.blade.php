@extends('components.box.box-static-large')

@section('title')
    <div>
        <h3 class="m-0">Редактирование записи</h3>
    </div>
@endsection


@section('body')
    <div class="fs-2 text-secondary px-1 fw-bold">
        <div class="fs-5 fw-bold mb-1">Наименование <span class="required text-danger">*</span></div>
        <textarea id="ta_name">{!! $hardware->name !!}</textarea>

        <div class="fs-5 fw-bold mb-1 mt-3">Количество</div>
        <textarea id="ta_count">{!! $hardware->count !!}</textarea>

        <div class="fs-5 fw-bold mb-1 mt-3">Параметры</div>
        <textarea id="ta_params">{!! $hardware->params !!}</textarea>
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
                url: "{{ route('api.hardware.update', [$hardware, '_token' => _token() ]) }}",
                method: "POST",
                dataType: "json",
                data: {
                    name: CKEDITOR.instances['ta_name'].getData(),
                    count: CKEDITOR.instances['ta_count'].getData(),
                    params: CKEDITOR.instances['ta_params'].getData(),
                },
                success: function (response) {
                    $("#hardware_table[variant='{{ $hardware->proposal_variant->id }}']")[0].outerHTML = response.html;
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

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection


