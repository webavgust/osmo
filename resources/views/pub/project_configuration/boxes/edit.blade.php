@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="form">
        <div class="card-body">
            <div class="form-group mb-3 row pb-3">
                <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Тип платформы</label>
                <div class="col-sm-9">
                    <select name="data[platform]" class="form-select">
                        @foreach(\App\Modules\Pub\ProjectConfiguration\Models\ProjectConfigurationPlatform::cases() as $platform)
                            <option @selected($platform->value === $configuration->platform) value="{{ $platform }}" id="{{ $platform->data()['id'] }}">{{ $platform->data()['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group mb-3 row pb-3">
                <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Срок (мес)</label>
                <div class="col-sm-9 d-flex justify-content-start align-items-center">
                    <input name="data[duration]" type="number" value="{{ $configuration->duration }}" min="0" class="form-control text-start" style="width: 85px" required>
                    <mark class="ms-2">0 - бессрочно</mark>
                </div>
            </div>

            <div class="form-group mb-3 row pb-3">
                <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Кол-во потоков</label>
                <div class="col-sm-9">
                    <input name="data[streams]" type="number" value="{{ $configuration->streams }}" min="1" class="form-control text-start" style="width: 85px" required>
                </div>
            </div>

            <div class="form-group mb-3 row pb-3">
                <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Комментарий</label>
                <div class="col-sm-9">
                    <input name="data[comment]" value="{{ $configuration->comment }}" type="text"  class="form-control text-start" id="inp_number" @if($configuration->project->configurations->count() > 1) required @endif >
                </div>
            </div>

            <div class="form-group mb-3 row pb-3">
                <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Номер конфигурации</label>
                <div class="col-sm-9 d-flex justify-content-start align-items-center fw-bold fs-6">
                    <code id="number"></code>
                </div>
            </div>



        </div>
    </form>

    <script>
        function box_check_form() {
            var err = false;

            $("form#form [required]").each(function() {
               if(!$(this).val()) err = true;
            });

            if(err) {
                $("#btn_submit").attr("disabled", "1");
            } else {
                $("#btn_submit").removeAttr("disabled");
            }

            redrawNumber();

            return !err;
        }



        function save() {
            if (!box_check_form()) return;

            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.project_configuration.update', [$configuration, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: $("form#form").serialize(),
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
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

        function redrawNumber() {
            platform = document.querySelector(`[name="data[platform]"] option[value='${document.querySelector('[name="data[platform]"]').value}']`).getAttribute('id');

            const ar = {
                prefix: '{{ $configuration->project->prefix }}',
                platform: platform,
                streams: document.querySelector('[name="data[streams]"]').value,
                duration: document.querySelector('[name="data[duration]"]').value,
                num: {{ $num }},
            }

            if(ar.platform && ar.streams && ar.duration) {
                const number = `${ar.prefix}<span class='number_dot'>.</span>${ar.platform}<span class='number_dot'>.</span>${ar.duration}<span class='number_dot'>.</span>${ar.streams}<span class='number_dot'>.</span>${ar.num}`;
                document.getElementById('number').innerHTML = number;
            } else {
                document.getElementById('number').innerHTML = 'Неизвестно';
            }
        }

        $(document).ready(function() {
            $("form#form input, form#form select").on("keyup change", function() {
                box_check_form();
            });

            box_check_form();
        });
    </script>

    <style>
        .number_dot {
            padding: 0 5px;
        }
    </style>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
