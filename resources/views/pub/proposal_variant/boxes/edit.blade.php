@extends('components.box.box-static-large')

@section('title')
    <div>
        <h3 class="m-0">Редактирование задачи</h3>
    </div>
@endsection


@section('body')
    <div class="fs-2 text-secondary px-1 fw-bold">
        <div class="fs-5 fw-bold mb-1">Задача <span class="required text-danger">*</span></div>
        <textarea id="ta_task">{!! $variant->task !!}</textarea>

        <div class="form-check fs-2 mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="cb_all" name="cb_all" @checked(!empty($variant->task) && $variant->proposal->variants->pluck('task')->unique()->count() == 1)>
            <label class="form-check-label fs-3" for="cb_all">
                Сохранить во все варианты
            </label>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            CKEDITOR.replace("ta_task", {
                height: 500,
                toolbar: [
                { name: 'document', items: ['Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates'] },
                { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
                { name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt'] },
                { name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'] },
                '/',
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language'] },
                { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
                { name: 'insert', items: ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe'] },
                '/',
                { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
                { name: 'colors', items: ['TextColor', 'BGColor'] },
                { name: 'tools', items: ['Maximize', 'ShowBlocks'] },
                { name: 'about', items: ['About'] }
            ]
            }).on("change", function() {
                if(!stripTags(CKEDITOR.instances['ta_task'].getData())) {
                    $("#box #btn_submit").attr("disabled", "disabled");
                } else {
                    $("#box #btn_submit").removeAttr("disabled");
                }
            });
        });

        function save() {
            if(!stripTags(CKEDITOR.instances['ta_task'].getData())) return;
            if(!confirm("Вы действительно хотите сохранить данные?")) return;

            $("body").block();

            $.ajax({
                url: "{{ route('api.proposal-variant.update', [$variant, '_token' => _token() ]) }}",
                method: "POST",
                dataType: "json",
                data: {
                    cb_all: $("#cb_all").prop("checked") ? 1 : 0,
                    task: CKEDITOR.instances['ta_task'].getData(),
                },
                success: function (response) {
                    if($("#cb_all").prop("checked")) {
                        $("#variant_task[variant]").each(function() {
                            $(this)[0].outerHTML = response.html;
                        });
                    } else {
                        $("#variant_task[variant='{{ $variant->id }}']")[0].outerHTML = response.html;
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

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection


