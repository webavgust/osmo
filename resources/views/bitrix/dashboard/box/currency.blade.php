@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="currency" >
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <table class="table table-bordered">
                        @foreach($currencies as $currency)
                            <tr>
                                <td width="1">
                                    <div class="form-check">
                                        <input for="cur_{{ $currency->slug }}" class="form-check-input" type="radio" name="currency" value="{{ $currency->slug }}" @checked($currency->slug == $currency_slug)>
                                        <label id="cur_{{ $currency->slug }}">{{ $currency->slug }}</label>
                                    </div>
                                </td>
                                <td>{{ $currency->name }}</td>
                                <td>{{ $currency->symbol }}</td>
                                <td>
                                    @if($currency->slug !== 'RUB')
                                        {{ $rates[$currency->slug]->amount }} ₽
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                    </table>
                </div>
            </div>
        </div>
    </form>

    <script>
        function save() {
            $("body").block(block_default);

            $.ajax({
                url: "{{ route('api.bitrix.dashboard.set_currency', ['_token' => _token() ]) }}",
                type: "POST",
                data: $("form#currency").serialize(),
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {
                       location.reload();
                    } else {
                        toastr.error(response.answer, "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }
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
        $(document).ready(function() {
            $("textarea#data").on("keyup change", function() {
                recalc();
            });
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_save" btn_type="success" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
