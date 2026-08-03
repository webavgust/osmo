
<div id="dropzone_content_loader_{{ $uid }}" class="d-none">
    <div class="d-flex justify-content-center">
        <div class="spinner-border" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>
</div>
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
                <x-files.files-list :files="[]"></x-files.files-list>
            </div>


            <div class="p-2 button_add">
                <form id="{{ $uid }}" class="dropzone_{{$uid}}"></form>
                <x-ui.button.outline btn_type="secondary" class=" w-100" onclick="javascript:$(this).parents('[dropzone_type]').find('form').click();"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Добавить файл</x-ui.button.outline>
            </div>
        </div>
    </div>

    <script>

        Dropzone.autoDiscover = false;
        var dz_init{{$uid}} = function() {
            let myDropzone = new Dropzone("#{{$uid}}", {
                url: '{!! route('files.upload_trash', [
                        'block' => $block,
                        '_token' => csrf_token()
                    ])  !!}',
                maxFiles: 1,
                {{--acceptedFiles: '{{ implode(',', $preset['available_mime'] ?? []) }}',--}}
                disablePreviews: true,
                init: function () {
                },
                addedfile: function (file) {
                    // блокируем блок
                    $("#dropzone_content_{{ $uid }}").addClass("d-none");
                    $("#dropzone_content_loader_{{ $uid }}").removeClass("d-none");
                },
                complete: function (file) {
                    @if(!empty($callback))
                    {{ $callback }}(file);
                    @endif
                    {{--files_refresh_{{$uid}}();--}}
                    this.removeFile(file);
                }
            });
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
