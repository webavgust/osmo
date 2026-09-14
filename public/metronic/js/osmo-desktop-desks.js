/*
 * Рабочий стол (patch v30, этап A4c1): столы и пресеты.
 *
 * Desk.desks — действия меню переключателя столов (index.blade.php, секция breadcrumb_right),
 * отправка попапа pub/desktop/boxes/desktop.blade.php (DesktopBoxController::desktop)
 * и обновление личного стола из системного пресета (плашка над сеткой).
 *
 * Запросы — api.desktop.* из Desk.urls: store, copy, update, delete, default, apply_source;
 * попап — Desk.urls.box_desktop?mode=create|rename|copy|system.
 */
(function (window, document, $) {
    'use strict';

    var Desk = window.Desk;
    if (!Desk) return;

    /** Токен AJAX сайта: csrf_token() из app.js */
    function token() {
        return typeof window.csrf_token === 'function' ? window.csrf_token() : $('meta[name="_token"]').attr('content');
    }

    /** Несохранённые изменения стола: спросить, можно ли уйти без сохранения */
    function leaveOk() {
        return !Desk.dirty || confirm('Есть несохранённые изменения. Перейти без сохранения?');
    }

    /**
     * POST к API стола: блокировка страницы, тостер, переход на response.url или перезагрузка
     *
     * @param {string} url
     * @param {Object} data
     * @param {string} failText текст ошибки, если сервер не ответил
     * @param {boolean} [reload] перезагрузить страницу вместо перехода на response.url
     */
    function send(url, data, failText, reload) {
        var $button = $('#desk_box_submit').prop('disabled', true);

        body_block();

        $.ajax({url: url, type: 'POST', dataType: 'json', data: $.extend({_token: token()}, data || {})})
            .done(function (response) {
                if (!response || response.result !== 'success') {
                    body_unblock();
                    $button.prop('disabled', false);
                    toastr.error((response && response.message) || failText, 'Это провал!');
                    return;
                }

                toastr.success(response.message, 'Это успех!');
                if ($button.length && typeof window.box_close === 'function') window.box_close();

                // уходим сами — без вопроса beforeunload о несохранённых изменениях
                Desk.dirty = false;
                if (!reload && response.url) {
                    window.location.href = response.url;
                } else {
                    window.location.reload();
                }
            })
            .fail(function (xhr) {
                body_unblock();
                $button.prop('disabled', false);
                toastr.error(failText + ' (' + xhr.status + ')', 'Это провал!');
            });
    }

    /** Открыть попап стола в режиме mode */
    function openBox(mode) {
        if (!leaveOk()) return;

        box({href: Desk.urls.box_desktop + '?mode=' + encodeURIComponent(mode)});
    }

    Desk.desks = {
        /** Новый стол (пустой или копия текущего) */
        create: function () {
            openBox('create');
        },

        /** Копия текущего стола в личные */
        copy: function () {
            openBox('copy');
        },

        /** Переименовать текущий стол */
        rename: function () {
            openBox('rename');
        },

        /** Сохранить текущий стол как системный пресет (админ) */
        saveAsSystem: function () {
            openBox('system');
        },

        /** Сделать текущий стол основным */
        makeDefault: function () {
            if (!leaveOk()) return;

            send(Desk.urls['default'], {}, 'Не получилось сделать стол основным', true);
        },

        /** Удалить текущий стол */
        remove: function () {
            var name = Desk.summary && Desk.summary.name ? Desk.summary.name : '';
            if (!confirm('Удалить стол «' + name + '»? Виджеты стола будут удалены.')) return;

            send(Desk.urls['delete'], {}, 'Не получилось удалить стол');
        },

        /** Заменить раскладку стола текущей версией системного пресета */
        applySource: function () {
            if (!confirm('Раскладка стола будет заменена раскладкой пресета. Продолжить?')) return;

            send(Desk.urls.apply_source, {}, 'Не получилось обновить стол', true);
        },

        /** «Оставить как есть»: скрыть плашку обновления до перезагрузки */
        dismissSource: function () {
            var notice = document.getElementById('desk_source_notice');
            if (notice) notice.hidden = true;
        },

        /** Отправить попап стола: режим — data-mode формы #desk_box_form */
        submit: function () {
            var $form = $('#desk_box_form');
            var $name = $form.find('[name="name"]');
            var name = $.trim($name.val() || '');
            var mode = String($form.data('mode') || 'create');

            if (!$form.length) return;
            if (!name) {
                toastr.error('Укажите название стола', 'Это провал!');
                $name.trigger('focus');
                return;
            }
            if (name.length > 100) {
                toastr.error('Название — не длиннее 100 символов', 'Это провал!');
                $name.trigger('focus');
                return;
            }

            switch (mode) {
                case 'rename':
                    send(Desk.urls.update, {name: name}, 'Не получилось переименовать стол', true);
                    break;
                case 'copy':
                    send(Desk.urls.copy, {name: name}, 'Не получилось скопировать стол');
                    break;
                case 'system':
                    send(Desk.urls.store, $.extend({name: name, system: 1},
                        $form.data('desktop') ? {copy_from: $form.data('desktop')} : {}), 'Не получилось создать пресет');
                    break;
                default:
                    var copyFrom = $form.find('[name="copy_from"]:checked').val();
                    send(Desk.urls.store, $.extend({name: name}, copyFrom ? {copy_from: copyFrom} : {}), 'Не получилось создать стол');
            }
        }
    };

    // Enter в поле названия отправляет попап
    $(document).on('submit', '#desk_box_form', function (event) {
        event.preventDefault();
        Desk.desks.submit();
    });
})(window, document, jQuery);
