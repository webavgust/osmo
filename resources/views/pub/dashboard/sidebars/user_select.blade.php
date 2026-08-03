@extends('components.sidebar.offcanvas-right')

@section('body')
    <form id="user_select_form">
        <div class="card sidebar-user_select">
            <div class="card-body p-0">
                @foreach(\App\Modules\Pub\Dashboard\Models\Dashboard::MODES as $mode => $ar)
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="mode" value="{{ $mode }}" id="mode_{{ $mode }}" value="{{ $mode }}" @if($mode == $user_mode['mode']) checked="" @endif>
                        <label class="form-check-label fs-4" for="mode_{{ $mode }}">
                            {{ $ar['name'] }}
                        </label>
                    </div>

                    @if($mode == 'select')
                        <div>
                            <select name="users[]" id="user_select" class="select2 w-100" multiple="multiple"></select>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </form>

    <div>
        <x-ui.button.default btn_type="primary" id="user_select_submit">Сохранить</x-ui.button.default>
    </div>
    <script>
        var SELECT_USERS = @json($users_grouped_json);
        $("#user_select").select2({
            multiple: true,
            data: SELECT_USERS,
            placeholder: "Выберите пользователей...",
            allowClear: true,
            width: '100%',
        }).on('select2:open', function (e) {
            $('#select2-user_select-results').on('click', function (event) {
                event.stopPropagation();

                var data = $(event.target).html();
                var selectedOptionGroup = data.toString().trim();

                var groupchildren = [];

                for (var i = 0; i < SELECT_USERS.length; i++) {
                    if (selectedOptionGroup.toString() === SELECT_USERS[i].text.toString()) {
                        for (var j = 0; j < SELECT_USERS[i].children.length; j++) {
                            groupchildren.push(SELECT_USERS[i].children[j].id);
                        }
                    }
                }

                var options = [];
                options = $('#user_select').val();
                if (options === null || options === '') {
                    options = [];
                }

                for (var i = 0; i < groupchildren.length; i++) {
                    var count = 0;
                    for (var j = 0; j < options.length; j++) {
                        if (options[j].toString() === groupchildren[i].toString()) {
                            count++;
                            break;
                        }
                    }

                    if (count === 0) {
                        options.push(groupchildren[i].toString());
                    }
                }

                $('#user_select').val(options);
                $('#user_select').trigger('change'); // Notify any JS components that the value changed
                $('#user_select').select2('close');
                check_form();

            });
        }).on('change', function(event, not_change) {
            $("#mode_select").prop("checked", true);
            check_form();
        });

        function check_form() {
            var result = true;
            var mode = $("#user_select_form input[type='radio']:checked").val();

            if(mode == 'select' && !$("#user_select").val().length) result = false

            if(!result) {
                $("#user_select_submit").addClass("disabled").removeAttr("disabled");
            } else {
                $("#user_select_submit").removeClass("disabled").attr("disabled");
            }
            return result;
        }
        $(document).ready(function() {
            $("#user_select_form [name='mode']").on("change", function() {
                check_form();
            });
           $("#user_select_submit").on("click", function() {
               $.ajax({
                   url: '{{ route('api.dashboard.set_user_mode', ['_token' => _token()]) }}',
                   data: $("#user_select_form").serialize(),
                   method: 'POST',
                   dataType: 'json',
                   success: function (response) {
                       location.reload();
                   }
               });
           });
        });
    </script>
@endsection
