{{--
    Попап константы в админ-панели (patch v28, этап C): создание и редактирование.

    Код задаётся только при создании. У системной константы значение пишет код
    портала — поле только для чтения, сервер его тоже не принимает. JSON-значение
    показывается с отступами, проверяется при сохранении и хранится в одну строку.
--}}
@extends('components.box.box-static-large')

@section('body')
    @php
        $is_new = empty($constant);
        $is_system = !$is_new && $constant->system;
    @endphp

    @if($is_system)
        <div class="alert alert-warning d-flex align-items-center p-4 mb-5 fs-7">
            <i class="fa-light fa-lock fs-3 text-warning me-3"></i>
            Системная константа: значение пишет код портала, поэтому здесь оно только для чтения.
            Название и примечание можно менять.
        </div>
    @endif

    <form id="admin_const_form" onsubmit="return false;" autocomplete="off">
        <div class="row g-4 mb-4">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Код @if($is_new)<span class="text-danger">*</span>@endif</label>
                @if($is_new)
                    <input type="text" name="key" class="form-control font-monospace" maxlength="128" autocomplete="off"
                           placeholder="например, payment_soon_days"/>
                    <div class="form-text">Латиница, цифры и «_». По коду константу читает код портала, после создания он не меняется</div>
                @else
                    <input type="text" class="form-control form-control-solid font-monospace" value="{{ $constant->key }}" readonly/>
                    <div class="form-text">Код не меняется: по нему константу читает код портала</div>
                @endif
            </div>
            <div class="col-md-7">
                <label class="form-label fw-semibold">Название <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" maxlength="128" value="{{ $constant?->name }}"/>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold">
                Значение
                @if($is_json)
                    <span class="badge badge-light-info fs-9 ms-1">JSON</span>
                @endif
            </label>
            <textarea name="value" rows="{{ $is_json ? 10 : 3 }}"
                      class="form-control font-monospace fs-7 @if($is_system) form-control-solid @endif"
                      @if($is_system) readonly @endif>{{ $is_new ? '' : \App\Modules\Admin\Consts\Services\AdminConstService::pretty($constant->value) }}</textarea>
            <div class="form-text">
                @if($is_system)
                    Меняется только кодом портала
                @elseif($is_json)
                    JSON-объект или массив: проверяется при сохранении и хранится в одну строку. Пусто — код возьмёт значение по умолчанию
                @else
                    Пусто — код возьмёт значение по умолчанию. Значение, начинающееся с «{» или «[», сохраняется как JSON
                @endif
            </div>
        </div>

        <div>
            <label class="form-label fw-semibold">Примечание</label>
            <textarea name="note" rows="3" class="form-control" maxlength="2000"
                      placeholder="За что отвечает, где используется, в каких единицах">{{ $constant?->note }}</textarea>
        </div>
    </form>

    <script>
        (function () {
            var isNew = {{ $is_new ? 'true' : 'false' }};
            var isSystem = {{ $is_system ? 'true' : 'false' }};
            var isJson = {{ $is_json ? 'true' : 'false' }};

            /** Сохранить константу */
            window.admin_const_save = function () {
                var $form = $('#admin_const_form');
                var data = {
                    _token: csrf_token(),
                    name: $form.find('[name="name"]').val(),
                    note: $form.find('[name="note"]').val()
                };

                if (isNew) data.key = $.trim($form.find('[name="key"]').val());
                if (!isSystem) data.value = $form.find('[name="value"]').val();

                if (isNew && !data.key) {
                    toastr.error('Задайте код константы', 'Это провал!', {progressBar: true, timeOut: 4000});
                    return;
                }

                if (isNew && !/^[A-Za-z0-9_]+$/.test(data.key)) {
                    toastr.error('Код — только латиница, цифры и знак подчёркивания', 'Это провал!', {progressBar: true, timeOut: 4000});
                    return;
                }

                if (!$.trim(data.name)) {
                    toastr.error('Заполните название', 'Это провал!', {progressBar: true, timeOut: 4000});
                    return;
                }

                // JSON проверяем и здесь, чтобы не гонять запрос с опечаткой; окончательно — на сервере
                var value = $.trim(data.value || '');
                if (!isSystem && value !== '' && (isJson || value[0] === '{' || value[0] === '[')) {
                    try {
                        JSON.parse(value);
                    } catch (e) {
                        toastr.error('Значение — некорректный JSON: ' + e.message, 'Это провал!', {progressBar: true, timeOut: 6000});
                        return;
                    }
                }

                body_block();
                $('#admin_const_save').prop('disabled', true);

                $.ajax({
                    url: @json($is_new ? route('admin.api.consts.store') : route('admin.api.consts.update', $constant->id)),
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        body_unblock();
                        $('#admin_const_save').prop('disabled', false);

                        if (!response || response.result !== 'success') {
                            toastr.error((response && response.message) || 'Не получилось сохранить константу', 'Это провал!', {progressBar: true, timeOut: 8000});
                            return;
                        }

                        toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});
                        box_close();
                        setTimeout(function () { location.reload(); }, 600);
                    },
                    error: function (xhr) {
                        body_unblock();
                        $('#admin_const_save').prop('disabled', false);
                        toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                    }
                });
            };

            // Enter в однострочных полях — сохранить (в textarea Enter — перенос строки)
            $('#admin_const_form input').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.admin_const_save();
                }
            });
        })();
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="admin_const_save" btn_type="success" onclick="javascript:admin_const_save();">
            <i class="fa-light fa-floppy-disk me-2"></i>
            <span>{{ empty($constant) ? 'Создать' : 'Сохранить' }}</span>
        </x-ui.button.default>
    </div>
@endsection
