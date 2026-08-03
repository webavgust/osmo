@extends('components.sidebar.offcanvas-right')

@section('body')
    <form id="user_select_form">

        <div>
            <select name="user" id="user_select" class="select2 w-100">
                <option value="{{ auth()->id() }}">Свой профиль</option>
                @foreach($sub_users as $user)
                    <option value="{{ $user->id }}" @if($user_selected->id == $user->id) selected @endif >{{ $user->full_name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="mt-3">
        <x-ui.button.default btn_type="primary" id="user_select_submit">Сохранить</x-ui.button.default>
    </div>
    <script>
        $("#user_select").select2({
            placeholder: "Выберите пользователей...",
            width: '100%',
        });


        $(document).ready(function() {
           $("#user_select_submit").on("click", function() {
               $.ajax({
                   url: '{{ route('api.dashboard.set_sub_user_mode', ['_token' => _token()]) }}',
                   data: {
                       'user': $("#user_select").val()
                   },
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
