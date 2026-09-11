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
     | 2a. select2 внутри попапов (patch v20)
     |
     |     Попапы приезжают ajax'ом в #box и показываются как обычная
     |     модалка Bootstrap. Из-за этого select2 ломался в трёх местах:
     |
     |     1) выпадающий список уезжал в body и оказывался под backdrop'ом,
     |        а часть попапов вообще не передавала dropdownParent;
     |     2) модалка с tabindex="-1" перехватывает фокус, и в поле поиска
     |        select2 нельзя было печатать;
     |     3) select2 считает ширину при инициализации: пока модалка скрыта,
     |        поле получало ширину 0.
     |
     |     Поэтому оборачиваем .select2(): подставляем dropdownParent на
     |     ближайшую модалку и ширину 100%, если вызывающий код их не задал.
     |     Явно переданные параметры не трогаем — старые попапы работают
     |     как раньше.
     * ---------------------------------------------------------------- */
    if ($.fn.select2) {
        var nativeSelect2 = $.fn.select2;

        $.fn.select2 = function (options) {
            // строковый вызов — это команда API ('destroy', 'open', 'val'…)
            if (typeof options === 'string') return nativeSelect2.apply(this, arguments);

            var base = $.extend({}, options);

            return this.each(function () {
                var $el = $(this);
                var opts = $.extend({}, base);

                if (!opts.dropdownParent) {
                    var $modal = $el.closest('.modal-content');
                    if (!$modal.length) $modal = $el.closest('.modal');
                    if ($modal.length) opts.dropdownParent = $modal;
                }
                if (!opts.width) opts.width = '100%';

                nativeSelect2.call($el, opts);

                // Тема bootstrap5 копирует классы <select> на «видимое» поле.
                // Если среди них был class="select2" (им во многих местах
                // помечают поля под инициализацию), поле само начинает
                // совпадать с селектором .select2 — а по нему select2 ищет
                // свой контейнер в обработчике «клик мимо списка». Тогда при
                // клике по полю select2 не узнаёт собственный контейнер и
                // закрывает только что открытый список: со стороны выглядит
                // так, будто выпадашка не открывается вовсе (patch v25).
                // Контейнер свой класс select2 сохраняет — его ставит сам
                // плагин, и обработчик по-прежнему находит открытые списки.
                $el.next('.select2-container').find('.select2-selection').removeClass('select2');
            });
        };

        $.fn.select2.defaults = nativeSelect2.defaults;
        $.fn.select2.amd = nativeSelect2.amd;

        // фокус в поле поиска: модалка Bootstrap возвращает его себе
        $(document).on('select2:open', function () {
            var field = document.querySelector('.select2-container--open .select2-search__field');
            if (field) field.focus();
        });

        // выпадающий список должен быть выше backdrop'а модалки
        var style = document.createElement('style');
        style.textContent = '.select2-container--open { z-index: 1060 }'
            + '.modal .select2-container { max-width: 100% }';
        document.head.appendChild(style);

        // модалка показана — пересчитать ширину селектов, собранных в скрытом окне
        $(document).on('shown.bs.modal', '.modal', function () {
            $(this).find('select.select2-hidden-accessible').each(function () {
                var $container = $(this).next('.select2-container');
                if ($container.length) $container.css('width', '100%');
            });
        });
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
