/*
 * Рабочий стол (patch v30): библиотека виджетов.
 *
 * Панель снизу поверх сетки, высотой не меньше 30 % окна (тянется за верхний край,
 * высота запоминается в браузере): разметка приходит с сервера (Desk.urls.library,
 * pub/desktop/library.blade.php) один раз и дальше живёт в DOM. Поиск, вкладки
 * категорий, «Добавить» (размер по умолчанию или выбранный чипом размера) и
 * перетаскивание карточки на сетку. Пока карточку тянут над сеткой, в заглушку
 * GridStack кладётся полупрозрачная копия превью — видно, где встанет блок.
 *
 * Панель открыта только в режиме редактирования: выход из него закрывает её.
 */
(function (window, document, $) {
    'use strict';

    if (!window.Desk) return;

    var Desk = window.Desk;
    var panel = null;       // корневой элемент панели (#desk_lib)
    var loading = null;     // запрос разметки
    var dragItem = null;    // карточка, которую тянут на сетку
    var height = 0;         // высота панели, px

    var HEIGHT_KEY = 'desk_lib_height';
    var MIN_SHARE = .3;     // панель не ниже этой доли окна
    var TOP_GAP = 80;       // и не выше окна без этого запаса сверху

    /*** ОТКРЫТИЕ И ЗАКРЫТИЕ ***/

    /** Открыть или закрыть библиотеку; при открытии включает редактирование */
    Desk.openLibrary = function () {
        if (panel && panel.classList.contains('is-open')) {
            close();
            return;
        }

        if (!Desk.editing && !Desk.edit(true)) return;

        if (panel) {
            open();
            return;
        }

        if (loading) return;

        body_block();
        loading = $.get(Desk.urls.library)
            .done(function (html) {
                var holder = document.createElement('div');
                holder.innerHTML = html;
                panel = holder.querySelector('#desk_lib');

                if (!panel) {
                    toastr.error('Не удалось загрузить библиотеку', 'Это провал!');
                    return;
                }

                document.body.appendChild(panel);
                bindPanel();
                if (window.DeskFit) window.DeskFit.render(panel);
                open();
            })
            .fail(function () {
                toastr.error('Не удалось загрузить библиотеку', 'Это провал!');
            })
            .always(function () {
                loading = null;
                body_unblock();
            });
    };

    function open() {
        placePanel();
        panel.classList.add('is-open');
        document.body.classList.add('desk-lib-open');

        var search = panel.querySelector('.desk-lib-search');
        if (search) setTimeout(function () { search.focus(); }, 50);
    }

    function close() {
        if (!panel) return;

        panel.classList.remove('is-open');
        document.body.classList.remove('desk-lib-open');
    }

    Desk.closeLibrary = close;

    /** Панель во всю ширину области контента: от края бокового меню до правого края окна */
    function placePanel() {
        if (!panel) return;

        var main = document.getElementById('kt_app_main') || Desk.root;
        var left = main ? Math.max(0, Math.round(main.getBoundingClientRect().left)) : 0;
        document.body.style.setProperty('--desk-lib-left', left + 'px');

        setHeight(height || readHeight());
    }

    /*** ВЫСОТА ***/

    /**
     * Поставить высоту панели в пределах: не ниже 30 % окна и не выше окна без запаса сверху.
     * Переменная на body нужна и панели, и отступу стола снизу
     *
     * @param {number} px
     */
    function setHeight(px) {
        var min = Math.round(window.innerHeight * MIN_SHARE);
        var max = Math.max(min, window.innerHeight - TOP_GAP);

        height = Math.min(max, Math.max(min, Math.round(px || 0)));
        document.body.style.setProperty('--desk-lib-height', height + 'px');
    }

    function readHeight() {
        try {
            return parseInt(window.localStorage.getItem(HEIGHT_KEY), 10) || 0;
        } catch (e) {
            return 0;
        }
    }

    function saveHeight() {
        try {
            window.localStorage.setItem(HEIGHT_KEY, String(height));
        } catch (e) { /* хранилище недоступно — высота живёт до перезагрузки */ }
    }

    /** Верхний край панели: тянуть вверх — выше, вниз — ниже */
    function bindGrip() {
        var grip = panel.querySelector('.desk-lib-grip');
        if (!grip) return;

        grip.addEventListener('pointerdown', function (event) {
            if (event.button !== 0) return;
            event.preventDefault();

            var startY = event.clientY;
            var start = panel.getBoundingClientRect().height;

            grip.setPointerCapture(event.pointerId);
            document.body.classList.add('desk-lib-sizing');
            panel.style.transition = 'none';

            function move(e) {
                setHeight(start + startY - e.clientY);
            }

            function up(e) {
                if (grip.hasPointerCapture(e.pointerId)) grip.releasePointerCapture(e.pointerId);
                grip.removeEventListener('pointermove', move);
                grip.removeEventListener('pointerup', up);
                grip.removeEventListener('pointercancel', up);
                document.body.classList.remove('desk-lib-sizing');
                panel.style.transition = '';
                saveHeight();
            }

            grip.addEventListener('pointermove', move);
            grip.addEventListener('pointerup', up);
            grip.addEventListener('pointercancel', up);
        });
    }

    // выход из редактирования (Сохранить, Отмена, узкий экран) закрывает панель
    var baseEdit = Desk.edit;
    Desk.edit = function (on) {
        var result = baseEdit.apply(Desk, arguments);
        if (!Desk.editing) close();

        return result;
    };

    /*** ПОИСК И ВКЛАДКИ ***/

    function bindPanel() {
        var search = panel.querySelector('.desk-lib-search');
        var timer = null;

        panel.querySelector('.desk-lib-close').addEventListener('click', close);
        bindGrip();

        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(filter, 150);
        });

        panel.querySelectorAll('.desk-lib-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                panel.querySelectorAll('.desk-lib-tab').forEach(function (other) {
                    other.classList.toggle('active', other === tab);
                });
                filter();
                panel.querySelector('.desk-lib-body').scrollTop = 0;
            });
        });

        // «Добавить» и чипы размеров — делегированно
        panel.addEventListener('click', function (event) {
            var add = event.target.closest('.desk-lib-add');
            var size = event.target.closest('.desk-lib-size');

            if (add) {
                Desk.addWidget(add.getAttribute('data-widget'));
            } else if (size) {
                Desk.addWidget(size.getAttribute('data-widget'), size.getAttribute('data-size'));
            }
        });

        setupDragIn();
    }

    /** Показать карточки по поиску и выбранной вкладке; пустые категории скрыть */
    function filter() {
        var query = (panel.querySelector('.desk-lib-search').value || '').trim().toLowerCase();
        var tab = panel.querySelector('.desk-lib-tab.active');
        var category = tab ? tab.getAttribute('data-cat') : 'all';
        var found = 0;

        panel.querySelectorAll('.desk-lib-cat').forEach(function (section) {
            var visible = 0;
            var inCategory = category === 'all' || section.getAttribute('data-cat') === category;

            section.querySelectorAll('.desk-lib-item').forEach(function (item) {
                var match = inCategory && (!query || item.getAttribute('data-search').indexOf(query) !== -1);
                item.classList.toggle('d-none', !match);
                if (match) visible++;
            });

            section.classList.toggle('d-none', visible === 0);
            found += visible;
        });

        panel.querySelector('.desk-lib-empty').classList.toggle('d-none', found > 0);
    }

    /*** ПЕРЕТАСКИВАНИЕ НА СЕТКУ ***/

    function setupDragIn() {
        if (!window.GridStack || !GridStack.setupDragIn) return;

        GridStack.setupDragIn(Array.prototype.slice.call(panel.querySelectorAll('.desk-lib-item')), {
            appendTo: 'body',
            handle: '.desk-lib-film',
            scroll: false,
            helper: helper,
            start: function (event) {
                dragItem = event && event.target ? event.target.closest('.desk-lib-item') : null;
                document.body.classList.add('desk-lib-dragging');
            },
            drag: function () {
                fillGhost();
            },
            stop: function () {
                dragItem = null;
                document.body.classList.remove('desk-lib-dragging');
                clearGhost();
            }
        });
    }

    /**
     * Помощник, который идёт за курсором: только превью карточки. Атрибуты data-widget,
     * gs-w и gs-h нужны GridStack (размер) и обработчику dropped в osmo-desktop.js
     */
    function helper(el) {
        var item = el.closest('.desk-lib-item') || el;
        var node = document.createElement('div');

        node.className = 'desk-lib-item desk-lib-helper';
        ['data-widget', 'gs-w', 'gs-h'].forEach(function (name) {
            node.setAttribute(name, item.getAttribute(name));
        });

        var preview = item.querySelector('.desk-lib-preview');
        if (preview) node.appendChild(preview.cloneNode(true));

        dragItem = item;

        return node;
    }

    /** Заглушка GridStack над сеткой — положить в неё полупрозрачную копию превью */
    function fillGhost() {
        if (!dragItem || !Desk.grid) return;

        var placeholder = Desk.grid.placeholder;
        if (!placeholder || !placeholder.parentElement) return;

        var content = placeholder.querySelector('.placeholder-content');
        if (!content || content.querySelector('.desk-ghost[data-lib="' + dragItem.getAttribute('data-widget') + '"]')) return;

        var widget = dragItem.querySelector('.desk-lib-preview .desk-widget');
        if (!widget) return;

        var ghost = document.createElement('div');
        ghost.className = 'desk-ghost';
        ghost.setAttribute('aria-hidden', 'true');
        ghost.setAttribute('data-lib', dragItem.getAttribute('data-widget'));
        ghost.appendChild(widget.cloneNode(true));

        ghost.querySelectorAll('[id]').forEach(function (node) { node.removeAttribute('id'); });
        ghost.querySelectorAll('[data-desk-context]').forEach(function (node) { node.removeAttribute('data-desk-context'); });

        content.innerHTML = '';
        content.appendChild(ghost);
    }

    function clearGhost() {
        var placeholder = Desk.grid && Desk.grid.placeholder;
        var content = placeholder && placeholder.querySelector ? placeholder.querySelector('.placeholder-content') : null;
        if (content) content.innerHTML = '';
    }

    /*** КЛАВИАТУРА И РАЗМЕР ОКНА ***/

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel && panel.classList.contains('is-open') && !document.querySelector('.modal.show')) {
            close();
        }
    });

    // окно или область контента (свернули боковое меню) сменили размер — панель следом
    function replace() {
        if (panel && panel.classList.contains('is-open')) placePanel();
    }

    window.addEventListener('resize', replace);
    $(function () {
        var root = document.getElementById('desk_root');
        if (root && 'ResizeObserver' in window) new ResizeObserver(replace).observe(root);
    });
})(window, document, jQuery);
