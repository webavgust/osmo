/* Рабочий стол (patch v30, этап A4b): попап настроек виджета и поиск объектов для полей-ссылок */
(function (window, document, $) {
    'use strict';

    var Desk = window.Desk;
    if (!Desk) return;

    /** uid заметки, открытой двойным кликом в режиме просмотра: её настройки сохраняются сразу */
    var instant = null;

    /** ajax_token пользователя */
    function token() {
        return typeof window.csrf_token === 'function' ? window.csrf_token() : $('meta[name="_token"]').attr('content');
    }

    /** Экранировать текст для тостера (он выводит HTML) */
    function esc(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    /** Размер блока: узел GridStack, иначе состояние */
    function blockSize(uid, st) {
        var el = Desk.grid ? Desk.grid.getGridItems().filter(function (item) {
            return item.gridstackNode && item.gridstackNode.id === uid;
        })[0] : null;
        var node = el ? el.gridstackNode : null;

        return {w: (node && node.w) || st.w || 1, h: (node && node.h) || st.h || 1};
    }

    /**
     * Открыть попап настроек блока
     *
     * @param {string} uid
     */
    Desk.openSettings = function (uid) {
        var st = Desk.state[uid];
        if (!st || !Desk.urls.settings_form) return;

        instant = null;

        var size = blockSize(uid, st);
        var url = Desk.urls.settings_form;

        box({
            href: url + (url.indexOf('?') < 0 ? '?' : '&') + '_token=' + encodeURIComponent(token()),
            method: 'POST',
            data: {uid: uid, widget: st.widget, w: size.w, h: size.h, settings: JSON.stringify(st.settings || {})}
        });
    };

    /** select2 с поиском объектов: тип берётся из соседнего списка */
    function entitySelect($select) {
        var $type = $select.closest('.desk-set-field').find('select[data-desk-entity-type]');

        $select.select2({
            width: '100%',
            dropdownParent: $('#staticBackdrop'),
            placeholder: 'начните вводить название или номер',
            allowClear: true,
            language: {
                searching: function () { return 'Поиск…'; },
                noResults: function () { return 'Ничего не найдено'; },
                errorLoading: function () { return 'Не получилось загрузить список'; }
            },
            ajax: {
                url: Desk.urls.search,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {_token: token(), type: $type.val(), q: params.term || ''};
                },
                processResults: function (response) {
                    return {results: response && response.results ? response.results : []};
                }
            }
        });

        // сменился тип объекта — выбранный объект больше не подходит
        $type.on('change', function () {
            $select.val(null).empty().trigger('change');
        });
    }

    /** Включить select2 в попапе (вызывает вьюха pub.desktop.boxes.settings сразу после вставки) */
    window.desk_settings_init = function () {
        var $form = $('#desk_settings_form');
        if (!$form.length) return;

        if ($.fn.select2) {
            var $parent = $('#staticBackdrop');

            $form.find('select.desk-set-select2').select2({width: '100%', dropdownParent: $parent});
            $form.find('select[data-desk-list]').each(function () {
                var tags = this.getAttribute('data-tags') === '1';
                $(this).select2({
                    width: '100%', dropdownParent: $parent, tags: tags, tokenSeparators: tags ? [','] : [],
                    placeholder: tags ? 'введите значение и нажмите Enter' : 'выберите'
                });
            });
            $form.find('select[data-desk-entity]').each(function () { entitySelect($(this)); });
        }

        // Enter в однострочном поле — применить
        $form.on('keydown', 'input.form-control', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            window.desk_settings_apply($form.attr('data-uid'));
        });
    };

    /** Значение поля формы по типу; undefined — не отправлять (сервер возьмёт значение по умолчанию) */
    function fieldValue(field) {
        var key = field.getAttribute('data-key');
        var $input = $(field).find('[name="' + key + '"]');
        var raw = $input.val();

        switch (field.getAttribute('data-type')) {
            case 'bool':
                return $input.prop('checked');
            case 'number':
                return $.trim(raw) === '' || isNaN(Number(raw)) ? undefined : Number(raw);
            case 'entity':
                raw = $.trim(raw || '');
                return raw === '' ? null : {type: $(field).find('[data-desk-entity-type]').val(), id: raw};
            case 'list':
                return raw || [];
            default:
                return raw == null ? '' : String(raw);
        }
    }

    /** Поле пустое (для проверки обязательных) */
    function isEmpty(value) {
        return value === undefined || value === null || (Array.isArray(value) ? !value.length : $.trim(String(value)) === '');
    }

    /**
     * Применить настройки из попапа (кнопка «Применить»): собрать поля, проверить обязательные,
     * отдать в Desk.applySettings. Заметку, открытую двойным кликом в просмотре, сразу сохранить
     *
     * @param {string} uid
     */
    window.desk_settings_apply = function (uid) {
        var $form = $('#desk_settings_form');
        uid = uid || $form.attr('data-uid');
        if (!$form.length || !Desk.state[uid]) {
            box_close();
            return;
        }

        var settings = {};
        var missing = null;

        $form.find('.desk-set-field').each(function () {
            var value = fieldValue(this);

            if (missing === null && this.getAttribute('data-required') === '1' && isEmpty(value)) {
                missing = this.getAttribute('data-label');
            }
            if (value !== undefined) settings[this.getAttribute('data-key')] = value;
        });

        if (missing !== null) {
            toastr.error('Заполните поле «' + esc(missing) + '»', 'Это провал!');
            return;
        }

        var saveNow = instant === uid && !Desk.editing;
        instant = null;

        Desk.applySettings(uid, settings);
        box_close();

        // applySettings включил редактирование (на 32 колонках); save() сохранит стол и выключит его
        if (saveNow && Desk.editing) Desk.save();
    };

    // двойной клик по заметке в режиме просмотра — её настройки (только тому, кто может менять стол)
    $(document).on('dblclick', '[data-desk-note]', function (event) {
        if (Desk.editing || !Desk.summary || !Desk.summary.can_edit) return;

        var el = this.closest('.grid-stack-item');
        var uid = el && el.gridstackNode ? el.gridstackNode.id : null;
        if (!uid || !Desk.state[uid]) return;

        event.preventDefault();
        Desk.openSettings(uid);
        instant = uid;
    });
})(window, document, jQuery);
