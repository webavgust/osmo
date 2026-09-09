@extends('components.box.box-static-large')

@section('body')
    <form id="form_import" onsubmit="return false;">
        <div class="fs-7 text-muted mb-4">
            Пока API закрыт проверкой cookie AI Studio, откройте в браузере
            <code>{{ rtrim((string) config('services.osmoview_cp.base_url'), '/') }}/api/cp/detail/{id}?api_key=…</code>
            (или <code>/api/cp/list</code>), скопируйте ответ целиком и вставьте сюда.
            Принимаются ответ <code>detail</code> (<code>{"success":true,"data":{…}}</code>), голый <code>data</code> и ответ списка.
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold">JSON</label>
            <textarea name="json" id="import_json" class="form-control form-control-solid font-monospace fs-8" rows="12"
                      placeholder='{"success":true,"data":{"id":"AK528", ...}}'></textarea>
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold">…или файл</label>
                <input type="file" name="file" id="import_file" class="form-control form-control-solid" accept=".json,application/json">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    doc-id записи
                    <span class="text-muted fw-normal fs-8">(для detail, если известен — из адреса <code>/detail/{id}</code>)</span>
                </label>
                <input type="text" name="external_id" id="import_external_id" class="form-control form-control-solid" placeholder="YjgSSQwbqAqw6lTs2gC7">
            </div>
        </div>

        <div class="fs-8 text-muted mt-3">
            Без doc-id detail привязывается к строке списка по номеру КП либо по дате изменения и названию;
            если такой строки нет, запись создаётся с номером вместо doc-id и подхватится при следующей синхронизации.
        </div>
    </form>

    <script>
        function import_save() {
            var form = new FormData(document.getElementById('form_import'));
            form.append('_token', csrf_token());

            var file = $('#import_file')[0].files[0];
            if (!file && !$('#import_json').val().trim()) {
                toastr.error('Вставьте JSON или выберите файл', 'Это провал!', { progressBar: true, timeOut: 3000 });
                return;
            }

            body_block();

            $.ajax({
                url: '{{ route('api.external_proposal.import') }}',
                type: 'POST',
                data: form,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    body_unblock();

                    if (response.result !== 'success') {
                        toastr.error(response.message ?? 'Не получилось импортировать', 'Это провал!', { progressBar: true, timeOut: 8000 });
                        return;
                    }

                    toastr.success(response.message, 'Это успех!', { progressBar: true, timeOut: 4000 });
                    box_close();
                    if (typeof external_table_refresh === 'function') external_table_refresh();
                },
                error: function (xhr) {
                    body_unblock();
                    toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', { progressBar: true, timeOut: 5000 });
                }
            });
        }
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default btn_type="primary" onclick="javascript:import_save();">
            <i class="fa-light fa-file-import me-2"></i>
            <span>Импортировать</span>
        </x-ui.button.default>
    </div>
@endsection
