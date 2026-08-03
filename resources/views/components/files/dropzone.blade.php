<div id="dropzone_content_{{ $uid }}" {{ $attributes }}>
    <style>
        [dropzone_type='{{ $uid }}'] .file:hover {
            background: #F9F9F9;
        }
        [dropzone_type='{{ $uid }}'] i[delete] {
            cursor: pointer;
            position: absolute;
            top: 7px;
            right: -18px;
            font-size: 22px;
            display: none;
        }
        [dropzone_type='{{ $uid }}'] .file:hover i[delete] {
            display: inline-block;
        }
    </style>
    <div class="card mb-1" dropzone_type="{{ $uid }}">
        @if(empty($ignore_header))
            <div class="
                    d-flex
                    border-bottom
                    title-part-padding
                    align-items-center
                    justify-content-between
                    p-3
                    flex-column
                    flex-lg-row
              ">
                <h4 class="card-title mb-0">{{ $name }}</h4>
                <div class="mt-2 mt-md-0">
                    @if($preset['count'] > 0)
                        <x-ui.badge.light type="secondary">{{ tools()->num_rus($preset['count'], ['файла', 'файл', 'файлов'], 1) }}</x-ui.badge.light>
                    @else
                        <x-ui.badge.light type="secondary"><i class="fa-regular fa-infinity"></i></x-ui.badge.light>
                    @endif
                    <x-ui.badge.light type="secondary">{{ implode(', ', $preset['extensions']) }}</x-ui.badge.light>
                    <x-ui.badge.light type="secondary">< {{ $preset['filesize'] }}МБ</x-ui.badge.light>
                </div>
            </div>
        @endif
        <div class="card-body p-0" >
            <div class="files">
                <x-files.files-list :files="$files"></x-files.files-list>
            </div>


            <div class="p-2 button_add @unless($can_add) d-none @endunless">
                <form id="{{ $uid }}" class="dropzone_{{$uid}}"></form>
                <x-ui.button.outline btn_type="secondary" class=" w-100" onclick="javascript:$(this).parents('[dropzone_type]').find('form').click();"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Добавить файл</x-ui.button.outline>
            </div>
        </div>
    </div>

    <script>

        const files_refresh_{{$uid}} = function(a) {
            var block_elem = $("#dropzone_content_{{ $uid }}");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
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
                url: "{{ route('files.block_redraw') }}?_token={{ csrf_token() }}",
                type: "GET",
                data: {
                    mode: '{{ $mode }}',
                    id: '{{ $id }}',
                    block: '{{ $block }}',
                    block_id: '{{ $block_id }}'
                },
                dataType: "html",
                success: function (source) {
                    var html = source.split('|#|');
                    if(html[0] == '1') {
                        block_elem.find(".button_add").removeClass('d-none');
                    } else {
                        block_elem.find(".button_add").addClass('d-none');
                    }
                    $("#dropzone_content_{{ $uid }} .files").html(html[1]);
                    files_delete_bind_{{$uid}}();
                    $(block_elem).unblock();

                },
                error: function () {
                    toastr.error("Не получилось обновить файлы", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }

        const files_delete_bind_{{$uid}} = function(file_id) {
            $("[dropzone_type='{{ $uid }}'] i[delete]").on("click", function() {
                if($(this).parents('.file').hasClass('exists')) {
                    if(!confirm('Вы хотите удалить ранее загруженный файл. Это действие приведёт к удалению файла и его нельзя будет отменить. Продолжить?'))
                        return false;
                }

                var file_id = $(this).attr("delete");
                block_elem = $("[dropzone_type='{{ $uid }}']");
                $(block_elem).block({
                    message: '<i class="fas fa-spin fa-sync text-white"></i>',
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
                    url: "{{ route('files.delete_temporary') }}?_token={{ csrf_token() }}",
                    type: "POST",
                    data: {
                        mode: '{{ $mode }}',
                        id: '{{ $id }}',
                        block: '{{ $block }}',
                        block_id: '{{ $block_id }}',
                        file_id: file_id,
                        kind: $(this).parents('.file').hasClass('exists') ? 'exists' : 'new'
                    },
                    dataType: "json",
                    success: function (result) {
                        files_refresh_{{$uid}}();
                        $(block_elem).unblock();
                    },
                    error: function () {
                        toastr.error("Не получилось удалить файл", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $(block_elem).unblock();
                    }
                });
            });

        }
        Dropzone.autoDiscover = false;
        var dz_init{{$uid}} = function() {

            let myDropzone = new Dropzone("#{{$uid}}", {
                url: '{!! route('files.upload_temporary', [
                        'mode' => $mode,
                        'id' => $id,
                        'block' => $block,
                        'block_id' => $block_id ?? null,
                        '_token' => csrf_token()
                    ])  !!}',
                maxFiles: {{ $preset['count'] > 0 ? $preset['count'] : 999 }},
                {{--acceptedFiles: '{{ implode(',', $preset['available_mime'] ?? []) }}',--}}
                disablePreviews: true,
                init: function () {
                    console.log("INIT");
                },
                addedfile: function (file) {
                    console.log(file);
                    // блокируем блок
                },
                complete: function (file) {
                    console.log(file);
                    files_refresh_{{$uid}}();
                    this.removeFile(file);
                }
            });

            files_delete_bind_{{$uid}}();
        }


        @if(empty($box))
            window.addEventListener('load', function () {
                dz_init{{$uid}}();
            });
        @else
            setTimeout(function() {
                dz_init{{$uid}}();
            }, 500);
        @endif
    </script>
</div>
