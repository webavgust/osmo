{{--
    Попап пользователя в админ-панели (patch v28, этап B): создание и редактирование.

    Пароль при создании обязателен, при редактировании пустое поле — не менять.
    Себя нельзя отключить и лишить доступа в админ-панель: эти переключатели
    заблокированы, а сервер проверяет то же самое.
--}}
@extends('components.box.box-static-large')

@section('body')
    @php $is_new = empty($user); @endphp

    @if(!$is_new && $user->trashed())
        <div class="alert alert-danger d-flex align-items-center p-4 mb-5 fs-7">
            <i class="fa-light fa-trash-can fs-3 text-danger me-3"></i>
            Пользователь удалён {{ $user->deleted_at?->format('d.m.Y H:i') }}: изменения сохранятся,
            но войти он сможет только после восстановления.
        </div>
    @endif

    <form id="admin_user_form" onsubmit="return false;" autocomplete="off">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Фамилия</label>
                <input type="text" name="last_name" class="form-control" maxlength="64" value="{{ $user?->last_name }}"/>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Имя <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" maxlength="50" value="{{ $user?->name }}"/>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Отчество</label>
                <input type="text" name="second_name" class="form-control" maxlength="64" value="{{ $user?->second_name }}"/>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Логин <span class="text-danger">*</span></label>
                <input type="text" name="login" class="form-control" maxlength="64" autocomplete="off" value="{{ $user?->login }}"/>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" maxlength="255" autocomplete="off" value="{{ trim((string) $user?->email) }}"/>
                <div class="form-text">Войти можно и по email</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Пароль @if($is_new)<span class="text-danger">*</span>@endif</label>
                <input type="password" name="password" class="form-control" maxlength="100" autocomplete="new-password"
                       placeholder="{{ $is_new ? 'не короче 8 символов' : 'не менять' }}"/>
                <div class="form-text">
                    @if($is_new)
                        Не короче 8 символов
                    @else
                        Пусто — пароль не меняется
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Должность</label>
                <input type="text" name="work_position" class="form-control" maxlength="100" value="{{ $user?->work_position }}"/>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Подразделение</label>
                <input type="text" name="work_department" class="form-control" maxlength="100" value="{{ $user?->work_department }}"/>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Телефон</label>
                <input type="text" name="personal_mobile" class="form-control" maxlength="64" value="{{ $user?->personal_mobile }}"/>
            </div>
        </div>

        <div class="separator separator-dashed mb-5"></div>

        <div class="d-flex flex-column gap-5">
            <div>
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="active" value="1"
                           @checked($is_new || $user->active) @disabled($is_self)/>
                    <span class="form-check-label fw-semibold text-gray-800">Активен</span>
                </label>
                @if($is_self)
                    <div class="form-text ms-14">Себя отключить нельзя</div>
                @endif
            </div>

            <div>
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_admin" value="1"
                           @checked(!$is_new && $user->is_admin) @disabled($is_self)/>
                    <span class="form-check-label fw-semibold text-gray-800">Доступ в админ-панель</span>
                </label>
                <div class="form-text ms-14">
                    @if($is_self)
                        С себя доступ снять нельзя
                    @else
                        Управление пользователями и настройками портала
                    @endif
                </div>
            </div>

            <div>
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="ui_theme_switch" value="1"
                           @checked($is_new || $user->ui_theme_switch)/>
                    <span class="form-check-label fw-semibold text-gray-800">Может переключать тему</span>
                </label>
                <div class="form-text ms-14">Если выключить, пользователь всегда работает в Metronic</div>
            </div>

            {{-- patch v29: доступ к журналу изменений сущностей --}}
            <div>
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="log_view" value="1"
                           @checked(!$is_new && $user->log_view)/>
                    <span class="form-check-label fw-semibold text-gray-800">Видит журнал изменений</span>
                </label>
                <div class="form-text ms-14">
                    Кнопка журнала на карточках КП, партнёра и компании и просмотр состояния на дату.
                    Админу доступно всегда
                </div>
            </div>
        </div>

        @if($is_new)
            <div class="notice bg-light-primary rounded border-primary border border-dashed p-4 mt-6 fs-7">
                Новому пользователю сразу выдаются права публичной части: общий доступ,
                платёжный календарь и карточка сделки.
            </div>
        @endif
    </form>

    <script>
        (function () {
            var isNew = {{ $is_new ? 'true' : 'false' }};

            /**
             * Сохранить пользователя. Поля собираем сами: заблокированные
             * переключатели serialize() пропустил бы
             */
            window.admin_user_save = function () {
                var data = {_token: csrf_token()};

                $('#admin_user_form').find('input[name]').each(function () {
                    data[this.name] = this.type === 'checkbox' ? (this.checked ? 1 : 0) : this.value;
                });

                if (!$.trim(data.name) || !$.trim(data.login)) {
                    toastr.error('Заполните имя и логин', 'Это провал!', {progressBar: true, timeOut: 4000});
                    return;
                }

                if (isNew && !data.password) {
                    toastr.error('Задайте пароль', 'Это провал!', {progressBar: true, timeOut: 4000});
                    return;
                }

                body_block();
                $('#admin_user_save').prop('disabled', true);

                $.ajax({
                    url: @json($is_new ? route('admin.api.users.store') : route('admin.api.users.update', $user->id)),
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        body_unblock();
                        $('#admin_user_save').prop('disabled', false);

                        if (!response || response.result !== 'success') {
                            toastr.error((response && response.message) || 'Не получилось сохранить пользователя', 'Это провал!', {progressBar: true, timeOut: 8000});
                            return;
                        }

                        toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});
                        box_close();
                        setTimeout(function () { location.reload(); }, 600);
                    },
                    error: function (xhr) {
                        body_unblock();
                        $('#admin_user_save').prop('disabled', false);
                        toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                    }
                });
            };

            // Enter в любом поле — сохранить
            $('#admin_user_form input').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.admin_user_save();
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

        <x-ui.button.default id="admin_user_save" btn_type="success" onclick="javascript:admin_user_save();">
            <i class="fa-light fa-floppy-disk me-2"></i>
            <span>{{ empty($user) ? 'Создать' : 'Сохранить' }}</span>
        </x-ui.button.default>
    </div>
@endsection
