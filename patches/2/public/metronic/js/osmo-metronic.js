/*
 |--------------------------------------------------------------------------
 | OSMO × Metronic — мост совместимости
 |--------------------------------------------------------------------------
 |
 | Грузится ПОСЛЕ бандлов Metronic и ПЕРЕД /js/app.js и /js/pages.js.
 | Задача: не дать старому фронту сломаться в новом каркасе и подружить
 | плагины (select2, toastr, tooltip) с оформлением Metronic.
 |
 */
(function () {
    'use strict';

    if (typeof window.jQuery === 'undefined') {
        console.warn('[osmo-metronic] jQuery не найден — старые скрипты работать не будут');
        return;
    }

    var $ = window.jQuery;

    /* ------------------------------------------------------------------
     | 1. Заглушки MaterialPro
     |    /js/app.js вызывает $("#main-wrapper").AdminSettings(...) —
     |    в новой теме этого узла нет. Пустая заглушка гасит ошибку.
     * ---------------------------------------------------------------- */
    if (typeof $.fn.AdminSettings === 'undefined') {
        $.fn.AdminSettings = function () { return this; };
    }
    if (typeof $.fn.waves === 'undefined') {
        $.fn.waves = function () { return this; };
    }

    /* ------------------------------------------------------------------
     | 2. select2 в оформлении Metronic
     |    Страницы вызывают .select2() напрямую, без data-control,
     |    поэтому тему задаём глобально.
     * ---------------------------------------------------------------- */
    if ($.fn.select2 && $.fn.select2.defaults) {
        try {
            $.fn.select2.defaults.set('theme', 'bootstrap5');
            $.fn.select2.defaults.set('width', 'resolve');
            $.fn.select2.defaults.set('language', {
                noResults: function () { return 'Ничего не найдено'; },
                searching: function () { return 'Поиск…'; },
                inputTooShort: function (a) {
                    return 'Введите ещё ' + (a.minimum - a.input.length) + ' симв.';
                }
            });
        } catch (e) {
            console.warn('[osmo-metronic] не удалось задать тему select2', e);
        }
    }

    /* ------------------------------------------------------------------
     | 3. toastr — позиция и оформление
     * ---------------------------------------------------------------- */
    if (typeof window.toastr !== 'undefined') {
        window.toastr.options = $.extend({}, window.toastr.options, {
            positionClass: 'toast-bottom-right',
            progressBar: true,
            newestOnTop: true,
            preventDuplicates: true,
            timeOut: 4000
        });
    }

    /* ------------------------------------------------------------------
     | 4. Левое меню: помним свёрнутое состояние на устройстве
     |    (KTToggle переключает атрибут data-kt-app-sidebar-minimize)
     * ---------------------------------------------------------------- */
    function watchSidebarToggle() {
        var toggle = document.getElementById('kt_app_sidebar_toggle');
        if (!toggle) return;

        toggle.addEventListener('click', function () {
            // состояние атрибута обновляется после обработчика KTToggle
            setTimeout(function () {
                var state = document.body.getAttribute('data-kt-app-sidebar-minimize') === 'on' ? 'on' : 'off';
                try { localStorage.setItem('osmo_sidebar_minimize', state); } catch (e) { /* no-op */ }
            }, 50);
        });
    }

    /* ------------------------------------------------------------------
     | 5. Инициализация компонентов Metronic после ajax-вставок
     |    box(), sidebar() и частичные перерисовки подгружают HTML
     |    с data-kt-* и data-bs-* — их нужно проинициализировать заново.
     * ---------------------------------------------------------------- */
    function initComponents(root) {
        var scope = root || document;

        // тултипы Bootstrap
        if (window.bootstrap && window.bootstrap.Tooltip) {
            scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                if (!window.bootstrap.Tooltip.getInstance(el)) {
                    new window.bootstrap.Tooltip(el);
                }
            });
        }

        // меню, дропдауны, скроллы и прочее Metronic
        if (window.KTComponents && typeof window.KTComponents.init === 'function') {
            window.KTComponents.init();
        } else if (window.KTMenu && typeof window.KTMenu.createInstances === 'function') {
            window.KTMenu.createInstances();
        }
    }

    window.osmoMetronicInit = initComponents;

    $(document).ready(function () {
        initComponents(document);
        watchSidebarToggle();

        $(document).ajaxComplete(function () {
            // даём вставленному html попасть в DOM
            setTimeout(function () { initComponents(document); }, 0);
        });
    });

    /* ------------------------------------------------------------------
     | 6. Тёмная тема: перерисовать плагины, которые кэшируют цвета
     * ---------------------------------------------------------------- */
    document.addEventListener('kt.thememode.change', function () {
        if (window.KTComponents && typeof window.KTComponents.init === 'function') {
            window.KTComponents.init();
        }
    });
})();
