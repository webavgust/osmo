{{--
    Общие действия раздела «Пользователи» (patch v28): удаление, восстановление
    и сам AJAX-запрос. Подключается в @section('js') списка и карточки.
    Кнопки передают себя: data-url — адрес действия, data-name — имя для подтверждения.
--}}
<script>
    /**
     * Запрос раздела: тостер, по успеху — done(response) или перезагрузка,
     * при ошибке — fail()
     */
    function admin_user_request(url, data, done, fail) {
        body_block();

        $.ajax({
            url: url,
            type: 'POST',
            data: $.extend({_token: csrf_token()}, data || {}),
            dataType: 'json',
            success: function (response) {
                body_unblock();

                if (!response || response.result !== 'success') {
                    toastr.error((response && response.message) || 'Не получилось выполнить действие', 'Это провал!', {progressBar: true, timeOut: 6000});
                    if (typeof fail === 'function') fail(response);
                    return;
                }

                toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});

                if (typeof done === 'function') done(response);
                else setTimeout(function () { location.reload(); }, 600);
            },
            error: function (xhr) {
                body_unblock();
                toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                if (typeof fail === 'function') fail();
            }
        });
    }

    /** Мягко удалить пользователя — с подтверждением */
    function admin_user_delete(el) {
        var name = $(el).data('name');

        if (!confirm('Удалить пользователя «' + name + '»?\n\n'
            + 'Войти он больше не сможет. Данные, права и история останутся на месте — '
            + 'пользователя можно восстановить из списка «Удалённые».')) {
            return;
        }

        admin_user_request($(el).data('url'));
    }

    /** Восстановить удалённого пользователя */
    function admin_user_restore(el) {
        admin_user_request($(el).data('url'));
    }
</script>
