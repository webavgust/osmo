@extends('components.box.box-static-large')

@section('title')
    <div>
        <h3 class="m-0">Добавление записи</h3>
    </div>
@endsection


@section('body')
    <div class="fs-2 text-secondary px-1 fw-bold">
        <textarea id="fast_message"></textarea>
    </div>

    <script>
        $(document).ready(function() {
            CKEDITOR.replace("fast_message", {
                height: 150,
                toolbar: [
                    {
                        name: 'basicstyles',
                        items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']
                    },
                    {
                        name: 'paragraph',
                        items: ['NumberedList', 'BulletedList']
                    },
                    {
                        name: 'styles',
                        items: ['Styles']
                    }
                ]
            });
        });

        function save() {
            if(!confirm("Вы действительно хотите сохранить это сообщение?")) return;

            $("body").block();
            var instance = CKEDITOR.instances['fast_message'];

            $.ajax({
                url: "{{ route('api.log.fast', ['_token' => _token() ]) }}",
                method: "POST",
                dataType: "json",
                data: {
                    group: '{{ $group }}',
                    text: instance.getData()
                },
                success: function (response) {
                    $("#proposal_table")[0].outerHTML = response.html;
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


