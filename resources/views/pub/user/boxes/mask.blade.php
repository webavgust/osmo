@extends('components.box.box-static-large')

@section('body')
    <style>
        .select2-container { width: 100%!important; }
    </style>
    <div class="row mask">
        <div class="col-12">
            @if(!empty($user))
                <x-ui.select.single :items="$users" value-name="fullName" id="id" class="select2 mask" value="{{ $user->id }}"></x-ui.select.single>
            @else
                <x-ui.select.single :items="$users" value-name="fullName" id="id" class="select2 mask"></x-ui.select.single>
            @endif

        </div>
        <div class="col-12 pt-2 @if(empty($user)) invisible @endif" id="mask_info">
            <mark>
                <code><a href="{{ $urls[$user?->id] ?? '' }}">{{ $urls[$user?->id] ?? '' }}</a></code>
            </mark>
            <a href="javascript:copy_url();"><x-ui.icon.regular icon="fa-copy" class="text-info ms-2"></x-ui.icon.regular></a>
            <textarea id="copy" style="opacity: 0; width: 1px; height: 1px" rows="1"></textarea>
        </div>
    </div>
    <script>
        urls = @json($urls);
        $(document).ready(function() {
            $(".select2.mask").select2({
                dropdownParent: $(".modal-body .row.mask"),
                placeholder: 'Выберите пользователя',
                select: function() {

                }
            });

            $(".select2.mask").on("select2:select", function (e) {
                var select_val = $(e.currentTarget).val();
                var url = urls[select_val];
                window.mask_url = url;
                $("#mask_info code a").attr('href', url).html(url);
                $("textarea#copy").val(url);
                $("#mask_info").removeClass('invisible');
                $("#sbm_button").removeClass("disabled");
            });
        });

        function copy_url() {

            $("textarea#copy").select();
            document.execCommand("copy");

            toastr.success("Ссылка скопирована!", "Это успех!", {
                progressBar: true,
                "timeOut": 3000,
            });
        }

        function mask_submit() {
            location.replace($("#mask_info code a").attr('href'));
        }
    </script>
@endsection

@section('footer')
    <div class="w-100 d-flex justify-content-between">
        <button type="button" class="
                btn btn-light-danger
                text-danger
                font-weight-medium
                waves-effect
                text-start
              " data-bs-dismiss="modal">
            Закрыть
        </button>


        <div>
            <x-ui.a.default btn_type="info" onclick="javascript:mask_submit()" class="disabled" id="sbm_button">
                <x-ui.icon.regular icon="fa-masks-theater" class="me-2"></x-ui.icon.regular>
                Зайти под пользователем
            </x-ui.a.default>
        </div>
    </div>
@endsection

