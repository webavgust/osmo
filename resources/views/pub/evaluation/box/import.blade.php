@extends('components.box.box-static-large')

@section('body')
    <div class="row">
        <div class="col-12">
            <x-files.dropzone_trash block="evaluation_import" name="Выберите файл" class="mt-3 mb-0" :box="true" callback="dropzone_callback"></x-files.dropzone_trash>
        </div>
        @if($files->isNotEmpty())
            <div class="table-responsive mt-4">
                <table class="table">
                    @foreach($files as $block => $ar)
                        <thead class="bg-light-secondary text-dark">
                            <tr>
                                <th colspan="2" class="p-2">
                                    {{ \App\Modules\Pub\Files\Models\File::PRESETS[\App\Modules\Pub\Evaluation\Models\Evaluation::class][$block]['name'] }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ar as $file)
                                <tr>
                                    <td>{{ $file->filename }}</td>
                                    <td align="right">
                                        <x-ui.a.outline btn_type="secondary" href="{{ $file->url }}" target="_blank">
                                            <x-ui.icon.duotone icon="fa-download" class="me-1"></x-ui.icon.duotone>
                                            Скачать
                                        </x-ui.a.outline>
                                        <x-ui.button.outline btn_type="primary" onclick="javascript:convert('{{ $file->path }}', 'massive')" >
                                            <x-ui.icon.duotone icon="fa-eye"></x-ui.icon.duotone>
                                            Быстрый просмотр
                                        </x-ui.button.outline>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    <script>
        function convert(path, disk) {
            $.ajax({
                url: '{{ route('api.files.generate_pdf_from_file') }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    path: path,
                    disk: disk
                },
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    if(response.status == 'success') {
                        // перейдём в режим просмотра PDF
                        doc_show(response.path);
                        box_close();
                    } else {
                        toastr.error("Ошибка при обработке файла", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                },
                error: function(response) {
                    toastr.error("Ошибка при открытии файла", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                }
            });
        }
        function dropzone_callback(answer) {
            var json = jQuery.parseJSON(answer.xhr.response);
            if(json.result == 'success') {
                toastr.success("Файл загружен и обрабатывается", "[1 / 2]", {
                    progressBar: true,
                    "timeOut": 1000,
                });

                convert(json.filename);

            } else {
                toastr.error("Неправильный файл", "Это провал!", {
                    progressBar: true,
                    "timeOut": 3000,
                });
            }
        }
    </script>
@endsection

@section('footer')
@endsection

