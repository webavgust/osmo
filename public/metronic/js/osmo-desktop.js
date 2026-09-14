/*
 * Рабочий стол (patch v30): страница стола — сетка GridStack 11 (MIT).
 *
 * Desk.init(options) вызывает resources/views/themes/metronic/pub/desktop/index.blade.php:
 * desktop (DesktopService::summary), items (gridItems), meta (Widget::meta по id),
 * context (валюта и период), config (колонки и пороги), urls (api.desktop.*).
 *
 * Сетка 32 колонки с квадратной ячейкой; сетка уже config.narrow_width — 16 колонок
 * масштабом ½, уже config.list_width — лента в одну колонку. Редактирование и
 * сохранение — только на 32 колонках: раскладка хранится в координатах 32 колонок.
 *
 * Перетаскивание и ресайз: в заглушку GridStack (.grid-stack-placeholder > .placeholder-content)
 * клонируется содержимое блока — полупрозрачная копия стоит там, куда блок встанет.
 *
 * Стили — public/metronic/css/osmo-desktop-grid.css (сетка) и osmo-desktop.css (виджеты).
 */
(function (window, document, $) {
    'use strict';

    /** Колонок в полной сетке (config.columns) */
    var COLUMNS = 32;

    /** Высота строки ленты на телефоне, px */
    var LIST_CELL = 40;

    /** Максимальная высота блока в ячейках при свободном размере (DesktopService::MAX_ROWS) */
    var MAX_ROWS = 64;

    /** Нижний предел высоты строки, px (config.min_cell): на узкой сетке ячейка перестаёт
     *  быть квадратной, иначе в блоке высотой 2 ячейки обрезается содержимое */
    var MIN_CELL = 46;

    var Desk = {
        grid: null,         // экземпляр GridStack
        root: null,         // #desk_root
        gridEl: null,       // #desk_grid
        summary: {},        // сводка стола (id, name, is_system, can_edit, …)
        meta: {},           // описания доступных пользователю виджетов: id => meta
        context: {},        // валюта и период стола
        config: {},         // columns, narrow_columns, narrow_width, list_width
        urls: {},           // render, save, context, store, copy
        state: {},          // uid => {widget, settings, w, h, free_size, meta, available, requested, loaded, xhr}
        editing: false,
        dirty: false,
        loading: false,     // первичная раскладка: события сетки не считаются правкой
        cellMode: 'auto',   // auto (квадратная ячейка) | fixed (строка по MIN_CELL) | list (лента)
        column: 0,          // число колонок при последнем пересчёте ячейки
        width: 0,           // ширина сетки при последнем пересчёте
        drag: null,         // текущее перетаскивание: {uid, el, type, filled}
        resize: null,       // текущий ресайз своей ручкой: {el, uid, meta, w, h}
        observer: null      // IntersectionObserver ленивой загрузки
    };

    /*** ПОМОЩНИКИ ***/

    /** Экранировать текст для HTML */
    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[ch];
        });
    }

    /** Размер «4x2» → [4, 2] */
    function parseSize(size) {
        var parts = String(size || '').toLowerCase().split('x');

        return [parseInt(parts[0], 10) || 0, parseInt(parts[1], 10) || 0];
    }

    /** Разрешённые размеры виджета: [[w, h], …] */
    function sizeList(meta) {
        return ((meta && meta.sizes) || []).map(parseSize).filter(function (size) {
            return size[0] > 0 && size[1] > 0;
        });
    }

    /** Размер разрешён виджету */
    function allowsSize(meta, w, h) {
        return sizeList(meta).some(function (size) {
            return size[0] === w && size[1] === h;
        });
    }

    /** Ближайший разрешённый размер — порт Widget::nearestSize (минимум |sw-w| + |sh-h|) */
    function nearestSize(meta, w, h) {
        var best = parseSize(meta && meta.default_size);
        var distance = Infinity;

        sizeList(meta).forEach(function (size) {
            var d = Math.abs(size[0] - w) + Math.abs(size[1] - h);
            if (d < distance) {
                distance = d;
                best = size;
            }
        });

        return best;
    }

    /** Границы ресайза GridStack по размерам виджета */
    function sizeLimits(meta, w, h) {
        var sizes = sizeList(meta);
        if (!sizes.length) {
            return {minW: w, maxW: w, minH: h, maxH: h, noResize: true};
        }

        var ws = sizes.map(function (size) { return size[0]; });
        var hs = sizes.map(function (size) { return size[1]; });

        return {
            minW: Math.min.apply(null, ws),
            maxW: Math.max.apply(null, ws),
            minH: Math.min.apply(null, hs),
            maxH: Math.max.apply(null, hs),
            noResize: sizes.length < 2
        };
    }

    /** Новый uid блока */
    function newUid() {
        return 'w' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
    }

    /** Токен AJAX сайта: csrf_token() из app.js (ajax_token пользователя) */
    function token() {
        return typeof window.csrf_token === 'function' ? window.csrf_token() : $('meta[name="_token"]').attr('content');
    }

    /** POST к API стола; ответ {result: 'success'|'error', message, …} */
    function post(url, data) {
        return $.ajax({url: url, type: 'POST', dataType: 'json', data: $.extend({_token: token()}, data || {})});
    }

    /** Текст ошибки из ответа API (сервер его уже экранировал) */
    function errorText(response) {
        return (response && response.message) || 'Не получилось выполнить действие';
    }

    /** Отложить вызов до паузы в событиях */
    function debounce(fn, ms) {
        var timer = null;

        return function () {
            clearTimeout(timer);
            timer = setTimeout(fn, ms);
        };
    }

    /** Элемент блока .grid-stack-item по uid */
    function itemEl(uid) {
        if (!Desk.grid) return null;

        return Desk.grid.getGridItems().filter(function (el) {
            return el.gridstackNode && el.gridstackNode.id === uid;
        })[0] || null;
    }

    /** uid блока, в котором лежит элемент */
    function uidOf(node) {
        var el = node && node.closest ? node.closest('.grid-stack-item') : null;

        return el && el.gridstackNode ? el.gridstackNode.id : null;
    }

    /*** ИНИЦИАЛИЗАЦИЯ ***/

    /**
     * Запустить стол
     *
     * @param {Object} options {desktop, items, meta, context, config, urls}
     */
    Desk.init = function (options) {
        options = options || {};
        Desk.summary = options.desktop || {};
        Desk.meta = options.meta || {};
        Desk.context = options.context || {};
        Desk.config = options.config || {};
        Desk.urls = options.urls || {};
        Desk.root = document.getElementById('desk_root');
        Desk.gridEl = document.getElementById('desk_grid');

        if (!Desk.root || !Desk.gridEl || typeof window.GridStack === 'undefined') {
            toastr.error('Не удалось загрузить сетку рабочего стола', 'Это провал!');
            return;
        }

        COLUMNS = parseInt(Desk.config.columns, 10) || COLUMNS;
        MIN_CELL = parseInt(Desk.config.min_cell, 10) || MIN_CELL;

        Desk.grid = window.GridStack.init({
            column: COLUMNS,
            cellHeight: 'auto',
            margin: 8,
            float: false,
            animate: true,
            staticGrid: true,
            acceptWidgets: '.desk-lib-item',
            // ресайз GridStack выключен: размер меняем сами шагами по разрешённым размерам
            resizable: {handles: ''},
            draggable: {cancel: 'input,textarea,select,button,a,.desk-item-tools,.desk-resize'},
            columnOpts: {
                columnMax: COLUMNS,
                // w — ширина сетки; от широкого порога к узкому
                breakpoints: [
                    {w: parseInt(Desk.config.narrow_width, 10) || 1200, c: parseInt(Desk.config.narrow_columns, 10) || 16, layout: 'scale'},
                    {w: parseInt(Desk.config.list_width, 10) || 768, c: 1, layout: 'list'}
                ]
            }
        }, Desk.gridEl);

        Desk.grid.enableResize(false);

        bindGrid();
        loadItems(options.items || []);
        bindPage();
        bindResize();
        layoutCells(true);
        markShortItems();
        updateEmpty();
        setInterval(tickClocks, 1000);
        setInterval(tickReload, 15000);
    };

    /*** БЛОКИ ***/

    /**
     * Первичная раскладка. Координаты — в 32 колонках, даже если сетка уже узкая:
     * грузим на 32 колонках, затем GridStack сам масштабирует и запоминает исходную раскладку
     */
    function loadItems(items) {
        var grid = Desk.grid;
        var start = grid.getColumn();
        var nodes = [];

        Desk.loading = true;

        items.forEach(function (item) {
            nodes.push(registerItem(item));
        });

        if (start !== COLUMNS) grid.column(COLUMNS, 'none');
        grid.load(nodes);
        grid.getGridItems().forEach(mountItem);
        if (start !== COLUMNS) grid.checkDynamicColumn();

        Desk.loading = false;
    }

    /**
     * Завести состояние блока и описание узла GridStack
     *
     * @param {Object} item {uid, widget, x, y, w, h, free_size, settings, available}
     * @returns {Object} узел для grid.load / grid.addWidget
     */
    function registerItem(item) {
        var uid = String(item.uid || newUid());
        var meta = Desk.meta[item.widget] || null;
        var w = parseInt(item.w, 10) || 1;
        var h = parseInt(item.h, 10) || 1;
        var available = meta !== null && item.available !== false;
        var settings = item.settings && typeof item.settings === 'object' && !Array.isArray(item.settings) ? item.settings : {};

        Desk.state[uid] = {
            widget: String(item.widget || ''),
            settings: settings,
            w: w,
            h: h,
            // отжат замок: размер задан вручную и к размерам виджета не приводится
            free_size: !!item.free_size,
            meta: meta || item.meta || null,
            available: available,
            requested: false,
            loaded: false,
            xhr: null
        };

        return $.extend({id: uid, x: parseInt(item.x, 10) || 0, y: parseInt(item.y, 10) || 0, w: w, h: h},
            !available ? {noResize: true}
                : (item.free_size ? {minW: 1, maxW: COLUMNS, minH: 1, maxH: MAX_ROWS} : sizeLimits(meta, w, h)));
    }

    /** Наполнить элемент блока: тело со скелетоном и инструменты; поставить в очередь загрузки */
    function mountItem(el) {
        var uid = el.gridstackNode ? el.gridstackNode.id : null;
        var st = Desk.state[uid];
        var content = el.querySelector('.grid-stack-item-content');
        if (!st || !content) return;

        el.classList.add('desk-gs-item');
        el.setAttribute('data-widget', st.widget);
        content.innerHTML = itemHtml(uid, st);

        if (st.available) observe(el);
    }

    /** Разметка блока */
    function itemHtml(uid, st) {
        var tools = '';

        if (st.available) {
            tools += '<button type="button" class="btn btn-icon btn-sm desk-tool-view" data-desk-tool="refresh" title="Обновить">'
                + '<i class="fa-light fa-arrows-rotate"></i></button>';
        }
        if (Desk.summary.can_edit) {
            if (st.available) {
                tools += '<button type="button" class="btn btn-icon btn-sm desk-tool-edit" data-desk-tool="settings" title="Настройки">'
                    + '<i class="fa-light fa-gear"></i></button>';
            }
            tools += '<button type="button" class="btn btn-icon btn-sm desk-tool-edit desk-tool-remove" data-desk-tool="remove" title="Удалить">'
                + '<i class="fa-light fa-xmark"></i></button>';
        }

        // ресайз ведём сами (GridStack-овский отключён): размер меняется шагами по
        // размерам виджета, а панель размеров даёт выбрать размер или отжать замок
        var resize = Desk.summary.can_edit && st.available && st.meta
            ? '<span class="desk-resize" title="Изменить размер"></span>'
            : '';

        return '<div class="desk-item" data-uid="' + esc(uid) + '">'
            + '<div class="desk-item-body">' + (st.available ? '<div class="desk-skeleton"></div>' : unavailableHtml(st)) + '</div>'
            + '<div class="desk-item-tools">' + tools + '</div>'
            + resize
            + '</div>';
    }

    /** Плашка вместо виджета, который пользователю недоступен или удалён из портала */
    function unavailableHtml(st) {
        var name = st.meta && st.meta.name ? '«' + esc(st.meta.name) + '»' : '';

        return '<div class="desk-widget desk-unavailable"><div class="desk-widget-body"><div class="desk-empty">'
            + '<i class="fa-light fa-lock fs-3"></i><span>Виджет ' + name + ' недоступен</span>'
            + '</div></div></div>';
    }

    /** Плашка ошибки загрузки с кнопкой «Повторить» */
    function errorHtml() {
        return '<div class="desk-widget desk-load-error"><div class="desk-widget-body"><div class="desk-error">'
            + '<span><i class="fa-light fa-triangle-exclamation me-1"></i>Не удалось загрузить виджет</span>'
            + '<button type="button" class="btn btn-sm btn-light" data-desk-tool="retry">Повторить</button>'
            + '</div></div></div>';
    }

    /**
     * Отметить блоки высотой в одну ячейку: в них инструменты налезают на уголок ресайза,
     * поэтому по классу desk-item-short они уходят левее (стили — osmo-desktop-grid.css)
     */
    function markShortItems() {
        if (!Desk.grid) return;

        Desk.grid.getGridItems().forEach(function (el) {
            var node = el.gridstackNode;
            el.classList.toggle('desk-item-short', !!node && node.h <= 1);
        });
    }

    /** Показать пустое состояние, если блоков нет */
    function updateEmpty() {
        var empty = document.getElementById('desk_empty');
        if (empty && Desk.grid) empty.hidden = Desk.grid.getGridItems().length > 0;
    }

    /*** ЛЕНИВАЯ ЗАГРУЗКА ***/

    /** Отрисовать блок, когда он подойдёт к экрану */
    function observe(el) {
        var item = el.querySelector('.desk-item');
        if (!item) return;

        if (!('IntersectionObserver' in window)) {
            Desk.refresh(el.gridstackNode.id, false);
            return;
        }

        if (!Desk.observer) {
            Desk.observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;

                    Desk.observer.unobserve(entry.target);
                    var uid = uidOf(entry.target);
                    if (uid) Desk.refresh(uid, false);
                });
            }, {rootMargin: '200px 0px'});
        }

        Desk.observer.observe(item);
    }

    /** Пауза, за которую блоки копятся в очереди отрисовки, мс */
    var BATCH_DELAY = 50;

    /** Блоков в одном запросе render_batch (сервер принимает до 30) */
    var BATCH_SIZE = 12;

    /** Очередь отрисовки uid => fresh, таймер её отправки и счётчик запросов блоков */
    var queue = {};
    var queueTimer = null;
    var requestSeq = 0;

    /**
     * Перерисовать виджет с сервера (по исходным w/h 32 колонок). Блоки копятся
     * BATCH_DELAY мс и уходят пакетом в render_batch: первичная загрузка и смена
     * контекста дают один-два запроса вместо запроса на каждый блок
     *
     * @param {string} uid
     * @param {boolean} fresh сбросить кэш данных виджета
     */
    Desk.refresh = function (uid, fresh) {
        var st = Desk.state[uid];
        var el = itemEl(uid);
        if (!st || !el || !st.available) return;

        var item = el.querySelector('.desk-item');
        if (Desk.observer && item) Desk.observer.unobserve(item);

        st.requested = true;
        if (st.loaded) el.classList.add('desk-item-loading');

        queue[uid] = !!(queue[uid] || fresh);
        if (!queueTimer) queueTimer = setTimeout(flushQueue, BATCH_DELAY);
    };

    /** Отправить очередь: блоки со сбросом кэша и без — отдельными пачками по BATCH_SIZE */
    function flushQueue() {
        var plain = [];
        var fresh = [];
        var i;

        Object.keys(queue).forEach(function (uid) {
            (queue[uid] ? fresh : plain).push(uid);
        });
        queue = {};
        queueTimer = null;

        for (i = 0; i < plain.length; i += BATCH_SIZE) renderBatch(plain.slice(i, i + BATCH_SIZE), false);
        for (i = 0; i < fresh.length; i += BATCH_SIZE) renderBatch(fresh.slice(i, i + BATCH_SIZE), true);
    }

    /**
     * Запросить HTML пачки блоков. Ответ блоку применяется, только если блок с тех пор
     * не ушёл в новый запрос (st.seq) и не удалён со стола
     *
     * @param {string[]} uids
     * @param {boolean} fresh
     */
    function renderBatch(uids, fresh) {
        var items = [];
        var seqs = {};

        uids.forEach(function (uid) {
            var st = Desk.state[uid];
            if (!st || !st.available) return;

            st.seq = ++requestSeq;
            seqs[uid] = st.seq;
            items.push({uid: uid, widget: st.widget, w: st.w, h: st.h, free_size: st.free_size ? 1 : 0, settings: st.settings || {}});
        });
        if (!items.length) return;

        post(Desk.urls.render_batch, {items: JSON.stringify(items), fresh: fresh ? 1 : 0})
            .done(function (response) {
                var html = response && response.result === 'success' && response.html && typeof response.html === 'object'
                    ? response.html : {};

                items.forEach(function (it) {
                    applyHtml(it.uid, seqs[it.uid], html[it.uid]);
                });
            })
            .fail(function () {
                items.forEach(function (it) {
                    applyHtml(it.uid, seqs[it.uid], null);
                });
            });
    }

    /**
     * Вставить HTML в блок; не строка — плашка «Не удалось загрузить виджет» с «Повторить»
     *
     * @param {string} uid
     * @param {number} seq номер запроса, в котором блок ушёл на сервер
     * @param {string|null|undefined} html
     */
    function applyHtml(uid, seq, html) {
        var st = Desk.state[uid];
        var el = itemEl(uid);
        if (!st || st.seq !== seq || !el) return;

        var body = el.querySelector('.desk-item-body');
        if (body) {
            // старый график снимаем до замены разметки, иначе ApexCharts оставит обработчики
            if (window.DeskChart) window.DeskChart.clear(body);
            if (window.DeskFit) window.DeskFit.clear(body);

            if (typeof html === 'string') {
                body.innerHTML = html;
                st.loaded = true;
                if (window.DeskChart) window.DeskChart.render(body);
                if (window.DeskFit) window.DeskFit.render(body);
            } else {
                body.innerHTML = errorHtml();
            }
        }

        el.classList.remove('desk-item-loading');
    }

    /*** ЯЧЕЙКА И АДАПТИВ ***/

    /**
     * Пересчитать сторону ячейки: --desk-cell в px 32-колоночной сетки (шрифты виджетов
     * от неё), на 16 колонках строка — половина колонки (блоки масштабом ½ без искажения),
     * в ленте — строка 40 px.
     *
     * Высота строки не опускается ниже MIN_CELL: на узкой сетке колонка ~30 px давала
     * блок высотой 60 px, в котором обрезалось содержимое. Пока колонка шире предела,
     * ячейка квадратная; уже — строка выше колонки, ширина блоков не меняется
     *
     * @param {boolean} force пересчитать, даже если ширина сетки не менялась
     */
    function layoutCells(force) {
        var grid = Desk.grid;
        if (!grid) return;

        var width = Desk.gridEl.clientWidth;
        var cols = grid.getColumn();
        if (!force && width === Desk.width && cols === Desk.column) return;
        Desk.width = width;

        var cell;
        var row;
        if (cols === 1) {
            if (Desk.cellMode !== 'list') grid.cellHeight(LIST_CELL);
            Desk.cellMode = 'list';
            cell = LIST_CELL;
        } else {
            // сторона ячейки в единицах полной сетки: на 16 колонках колонка вдвое шире
            cell = Math.round(grid.cellWidth() * cols / COLUMNS * 100) / 100;
            row = Math.max(cell, MIN_CELL);

            if (cols === COLUMNS && row === cell) {
                if (Desk.cellMode !== 'auto') grid.cellHeight('auto');
                Desk.cellMode = 'auto';
            } else {
                grid.cellHeight(row);
                Desk.cellMode = 'fixed';
            }
        }

        Desk.gridEl.style.setProperty('--desk-cell', (Math.round(cell * 100) / 100) + 'px');

        if (cols !== Desk.column) {
            var first = Desk.column === 0;
            Desk.column = cols;
            if (!first) columnChanged(cols);
        }
    }

    /*** СОБЫТИЯ СТРАНИЦЫ ***/

    function bindPage() {
        var $grid = $(Desk.gridEl);

        // инструменты блоков и «Повторить»
        $grid.on('click', '[data-desk-tool]', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var uid = uidOf(this);
            if (!uid) return;

            switch (this.getAttribute('data-desk-tool')) {
                case 'refresh': Desk.refresh(uid, true); break;
                case 'retry': Desk.refresh(uid, false); break;
                case 'settings': Desk.openSettings(uid); break;
                case 'remove': Desk.removeWidget(uid); break;
            }
        });

        // кнопка виджета «Действие» с подтверждением
        $grid.on('click', '[data-desk-confirm]', function (event) {
            if (!confirm(this.getAttribute('data-desk-confirm'))) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        });

        // виджеты «Валюта» и «Период» меняют контекст всего стола
        $grid.on('change', '[data-desk-context]', function () {
            Desk.setContext(this.getAttribute('data-desk-context'), this.value);
        });

        // ячейка — после того как GridStack пересчитает колонки (его троттлинг 100 мс)
        var relayout = debounce(function () { layoutCells(false); }, 150);
        window.addEventListener('resize', relayout);
        if ('ResizeObserver' in window) {
            new ResizeObserver(relayout).observe(Desk.root);
        }

        window.addEventListener('beforeunload', function (event) {
            if (!Desk.dirty) return undefined;

            event.preventDefault();
            event.returnValue = '';

            return '';
        });
    }

    /*** РЕЖИМ РЕДАКТИРОВАНИЯ ***/

    /**
     * Включить / выключить редактирование (только владелец стола и только на 32 колонках)
     *
     * @param {boolean} on
     * @returns {boolean} режим редактирования включён
     */
    Desk.edit = function (on) {
        on = on !== false;
        if (!Desk.grid) return false;

        if (on) {
            if (!Desk.summary.can_edit) return false;
            if (Desk.grid.getColumn() !== COLUMNS) {
                toastr.info('Редактирование доступно на широком экране', 'Рабочий стол');
                return false;
            }
        }

        Desk.editing = on;
        if (!on) closeSizePanel();
        Desk.grid.setStatic(!on, true);
        // setStatic включает и ресайз GridStack — он нам не нужен, размер меняет своя ручка
        Desk.grid.enableResize(false);
        Desk.root.classList.toggle('desk-editing', on);
        document.body.classList.toggle('desk-edit-mode', on);
        updateSaveButton();
        updateEmpty();

        return on;
    };

    /** Отметить несохранённые изменения */
    Desk.setDirty = function (flag) {
        Desk.dirty = !!flag;
        updateSaveButton();
    };

    /** «Сохранить» активна, когда есть изменения и сетка в 32 колонки */
    function updateSaveButton() {
        var button = document.getElementById('desk_save');
        if (button) button.disabled = !(Desk.dirty && Desk.grid && Desk.grid.getColumn() === COLUMNS);
    }

    /** Правки сетки учитываются только в редактировании на 32 колонках и не при загрузке */
    function trackable() {
        return !Desk.loading && Desk.editing && Desk.grid.getColumn() === COLUMNS;
    }

    /** Колонки сменились при изменении ширины: на узкой сетке редактирование на паузе */
    function columnChanged(cols) {
        if (Desk.editing) {
            Desk.grid.setStatic(cols !== COLUMNS, true);
            if (cols !== COLUMNS) {
                closeSizePanel();
                toastr.info('Редактирование доступно на широком экране', 'Рабочий стол');
            }
        }
        updateSaveButton();
    }

    /** Уплотнить: поднять блоки вверх, убрать пустые места */
    Desk.compact = function () {
        if (!Desk.editing) return;

        Desk.grid.compact();
        Desk.setDirty(true);
    };

    /**
     * Удалить блок со стола (после подтверждения)
     *
     * @param {string} uid
     */
    Desk.removeWidget = function (uid) {
        var el = itemEl(uid);
        var st = Desk.state[uid];
        if (!el || !Desk.editing) return;

        var name = st && st.meta && st.meta.name ? '«' + st.meta.name + '»' : 'виджет';
        if (!confirm('Удалить ' + name + ' со стола?')) return;

        var item = el.querySelector('.desk-item');
        if (Desk.observer && item) Desk.observer.unobserve(item);
        if (st && st.xhr) st.xhr.abort();
        if (window.DeskChart) window.DeskChart.clear(el);
        if (window.DeskFit) window.DeskFit.clear(el);

        if (sizePanel && sizePanel.uid === uid) closeSizePanel();
        Desk.grid.removeWidget(el);
        delete Desk.state[uid];
        updateEmpty();
    };

    /** Выйти из редактирования; несохранённые изменения — подтвердить и перезагрузить стол */
    Desk.cancel = function () {
        if (!Desk.dirty) {
            Desk.edit(false);
            return;
        }

        if (!confirm('Отменить несохранённые изменения стола?')) return;

        Desk.dirty = false;
        body_block();
        window.location.reload();
    };

    /*** СОБЫТИЯ СЕТКИ ***/

    function bindGrid() {
        var grid = Desk.grid;

        grid.on('change', function () {
            markShortItems();
            if (trackable()) Desk.setDirty(true);
            if (sizePanel) refreshSizePanel();
        });

        grid.on('added removed', function () {
            markShortItems();
            updateEmpty();
            if (trackable()) Desk.setDirty(true);
        });

        grid.on('dragstart resizestart', onMoveStart);
        grid.on('drag resize', onMove);
        grid.on('dragstop resizestop', onMoveStop);
        grid.on('dropped', onDropped);
    }

    /*** ПЕРЕТАСКИВАНИЕ И РЕСАЙЗ: ПОЛУПРОЗРАЧНАЯ КОПИЯ В МЕСТЕ ПРИЗЕМЛЕНИЯ ***/

    /** Открытая панель размеров блока: {el, uid} */
    var sizePanel = null;

    /**
     * Внутренность заглушки GridStack. Сам элемент заглушки один на сетку и живёт всё
     * время (grid.placeholder), в DOM GridStack вставляет его сразу после обработчика
     * dragstart — поэтому наполняем его напрямую, а если его нет — ищем в DOM на drag
     */
    function placeholderContent() {
        var placeholder = Desk.grid.placeholder;
        if (placeholder && placeholder.querySelector) {
            return placeholder.querySelector('.placeholder-content');
        }

        return Desk.gridEl.querySelector('.grid-stack-placeholder > .placeholder-content');
    }

    /** Положить в заглушку копию содержимого блока (прозрачность и рамка — в CSS) */
    function fillGhost() {
        var drag = Desk.drag;
        if (!drag || drag.filled) return;

        var target = placeholderContent();
        var source = drag.el.querySelector('.desk-item-body');
        if (!target || !source) return;

        var ghost = document.createElement('div');
        ghost.className = 'desk-ghost';
        ghost.setAttribute('aria-hidden', 'true');
        ghost.innerHTML = source.innerHTML;

        // копия не должна дублировать id, менять контекст стола и попадать в формы
        ghost.querySelectorAll('[id]').forEach(function (node) { node.removeAttribute('id'); });
        ghost.querySelectorAll('[data-desk-context]').forEach(function (node) { node.removeAttribute('data-desk-context'); });
        ghost.querySelectorAll('input, select, textarea, button').forEach(function (node) {
            node.disabled = true;
            node.removeAttribute('name');
        });

        target.innerHTML = '';
        target.appendChild(ghost);
        drag.filled = true;
    }

    /** Очистить заглушку */
    function clearGhost() {
        var target = placeholderContent();
        if (target) target.innerHTML = '';
    }

    /** dragstart / resizestart: запомнить блок и исходный размер, наполнить заглушку */
    function onMoveStart(event, el) {
        var node = el && el.gridstackNode;
        if (!node) return;

        Desk.drag = {uid: node.id, el: el, type: event.type, w: node.w, h: node.h, filled: false};
        Desk.root.classList.add('desk-dragging');
        fillGhost();

    }

    /** drag: наполнить заглушку, если на старте её ещё не было */
    function onMove() {
        if (Desk.drag && !Desk.drag.filled) fillGhost();
    }

    /** dragstop: убрать копию */
    function onMoveStop() {
        Desk.drag = null;
        Desk.root.classList.remove('desk-dragging');
        clearGhost();
    }

    /*** РЕСАЙЗ И ПАНЕЛЬ РАЗМЕРОВ ***/

    /**
     * Свой ресайз вместо GridStack-овского (он отключён в init).
     *
     * Причина: GridStack тянет блок плавно и на любой размер, и кажется, что размер может
     * быть каким угодно; вмешиваться в его ресайз нельзя — перестройка узла во время
     * операции ломает его внутреннее состояние. Поэтому размер ведём сами: от курсора
     * считается желаемый размер в ячейках, а рядом с блоком висит панель его размеров.
     *
     * Замок на панели закрыт — блок прыгает только по размерам виджета; отжат
     * (st.free_size) — принимает любой размер. Размер можно и просто выбрать на панели.
     */
    function bindResize() {
        Desk.gridEl.addEventListener('mousedown', function (event) {
            var handle = event.target.closest ? event.target.closest('.desk-resize') : null;
            if (!handle || !Desk.editing || event.button !== 0) return;

            var el = handle.closest('.grid-stack-item');
            if (!el || !el.gridstackNode) return;

            event.preventDefault();
            event.stopPropagation();
            startResize(el);
        });

        // нажатие мимо панели (в том числе по блоку — начало перетаскивания) закрывает её
        document.addEventListener('mousedown', function (event) {
            if (!sizePanel || !event.target.closest) return;
            if (event.target.closest('.desk-size-panel') || event.target.closest('.desk-resize')) return;

            closeSizePanel();
        }, true);

        document.addEventListener('keydown', function (event) {
            if (sizePanel && event.key === 'Escape') closeSizePanel();
        });

        window.addEventListener('scroll', positionSizePanel, true);
        window.addEventListener('resize', positionSizePanel);
    }

    /**
     * Границы узла GridStack: при закрытом замке — размеры виджета, при отжатом — вся сетка.
     * Без этого grid.update() обрезал бы свободный размер по minW/maxW виджета
     *
     * @param {HTMLElement} el
     * @param {Object} st состояние блока
     */
    function applyLimits(el, st) {
        var node = el && el.gridstackNode;
        if (!node || !st) return;

        var limits = st.free_size || !st.meta
            ? {minW: 1, maxW: COLUMNS, minH: 1, maxH: MAX_ROWS}
            : sizeLimits(st.meta, node.w, node.h);

        node.minW = limits.minW;
        node.maxW = limits.maxW;
        node.minH = limits.minH;
        node.maxH = limits.maxH;
    }

    /**
     * Поставить блоку размер: выбран на панели или получился при ресайзе
     *
     * @param {string} uid
     * @param {number} w
     * @param {number} h
     */
    function applySize(uid, w, h) {
        var el = itemEl(uid);
        var st = Desk.state[uid];
        if (!el || !st || !Desk.editing) return;

        var node = el.gridstackNode || {};
        if (node.w === w && node.h === h) return;

        applyLimits(el, st);
        Desk.grid.update(el, {w: w, h: h});

        node = el.gridstackNode || {};
        st.w = node.w || w;
        st.h = node.h || h;
        Desk.setDirty(true);
        Desk.refresh(uid, false);
        refreshSizePanel();
    }

    /**
     * Замок на панели: закрыт — только размеры виджета, отжат — любой размер.
     * При закрытии замка размер приводится к ближайшему разрешённому
     *
     * @param {string} uid
     */
    function toggleFreeSize(uid) {
        var el = itemEl(uid);
        var st = Desk.state[uid];
        if (!el || !st || !Desk.editing) return;

        st.free_size = !st.free_size;
        applyLimits(el, st);
        Desk.setDirty(true);

        var node = el.gridstackNode || {};
        if (!st.free_size && st.meta && !allowsSize(st.meta, node.w, node.h)) {
            var size = nearestSize(st.meta, node.w, node.h);
            applySize(uid, size[0], size[1]);
        }

        refreshSizePanel();
    }

    /**
     * Начать ресайз блока
     *
     * @param {HTMLElement} el
     */
    function startResize(el) {
        var node = el.gridstackNode;
        var st = Desk.state[node.id];
        if (!st || !st.meta) return;

        Desk.resize = {el: el, uid: node.id, meta: st.meta, w: node.w, h: node.h};
        Desk.root.classList.add('desk-dragging', 'desk-resizing');
        el.classList.add('desk-resizing-item');

        applyLimits(el, st);
        openSizePanel(node.id);

        document.addEventListener('mousemove', onResizeMove, true);
        document.addEventListener('mouseup', stopResize, true);
    }

    /**
     * Движение мыши: размер под курсором — ближайший разрешённый, а при отжатом замке любой
     *
     * @param {MouseEvent} event
     */
    function onResizeMove(event) {
        var resize = Desk.resize;
        if (!resize) return;

        var node = resize.el.gridstackNode;
        var st = Desk.state[resize.uid];
        var cellW = Desk.grid.cellWidth();
        var cellH = Desk.grid.getCellHeight(true) || cellW;
        if (!node || !st || cellW <= 0 || cellH <= 0) return;

        var box = Desk.gridEl.getBoundingClientRect();
        var want = [
            Math.max(1, Math.round((event.clientX - box.left - node.x * cellW) / cellW)),
            Math.max(1, Math.round((event.clientY - box.top - node.y * cellH) / cellH))
        ];

        var size = st.free_size
            ? [Math.min(COLUMNS - node.x, want[0]), Math.min(MAX_ROWS, want[1])]
            : nearestSize(resize.meta, want[0], want[1]);

        if (size[0] !== node.w || size[1] !== node.h) {
            Desk.grid.update(resize.el, {w: size[0], h: size[1]});
        }

        refreshSizePanel();
    }

    /** Отпустили кнопку: запомнить размер и перерисовать виджет; панель размеров остаётся */
    function stopResize() {
        var resize = Desk.resize;
        if (!resize) return;

        document.removeEventListener('mousemove', onResizeMove, true);
        document.removeEventListener('mouseup', stopResize, true);

        Desk.resize = null;
        Desk.root.classList.remove('desk-dragging', 'desk-resizing');
        resize.el.classList.remove('desk-resizing-item');

        // размер берём из узла, а если GridStack его пересоздал — из атрибутов блока
        var node = resize.el.gridstackNode || {};
        var st = Desk.state[resize.uid];
        var w = node.w || parseInt(resize.el.getAttribute('gs-w'), 10) || resize.w;
        var h = node.h || parseInt(resize.el.getAttribute('gs-h'), 10) || resize.h;

        refreshSizePanel();

        if (!st || (w === resize.w && h === resize.h)) return;

        st.w = w;
        st.h = h;
        Desk.setDirty(true);
        Desk.refresh(resize.uid, false);
    }

    /*** ПАНЕЛЬ РАЗМЕРОВ БЛОКА ***/

    /**
     * Показать рядом с блоком его размеры: размер ставится нажатием, замок снимает
     * ограничение списком. Панель висит, пока не щёлкнут мимо неё или не нажмут Esc
     *
     * @param {string} uid
     */
    function openSizePanel(uid) {
        var el = itemEl(uid);
        var st = Desk.state[uid];
        if (!el || !st || !st.meta) return;

        if (sizePanel && sizePanel.uid === uid) {
            refreshSizePanel();
            return;
        }

        closeSizePanel();

        var panel = document.createElement('div');
        panel.className = 'desk-size-panel';
        panel.innerHTML = '<span class="desk-size-label">Размеры</span>'
            + (st.meta.sizes || []).map(function (size) {
                return '<button type="button" class="desk-size-chip" data-size="' + esc(size) + '">'
                    + esc(String(size).replace('x', '×')) + '</button>';
            }).join('')
            + '<span class="desk-size-now"></span>'
            + '<button type="button" class="desk-size-lock"><i class="fa-light fa-lock"></i></button>';

        panel.addEventListener('click', onSizePanelClick);
        document.body.appendChild(panel);
        el.classList.add('desk-size-target');

        sizePanel = {el: panel, uid: uid};
        refreshSizePanel();
    }

    /** Нажатие на панели: размер из списка или замок */
    function onSizePanelClick(event) {
        if (!sizePanel) return;

        event.preventDefault();
        event.stopPropagation();

        var chip = event.target.closest('.desk-size-chip');
        if (chip) {
            var size = parseSize(chip.getAttribute('data-size'));
            if (size[0] > 0 && size[1] > 0) applySize(sizePanel.uid, size[0], size[1]);
            return;
        }

        if (event.target.closest('.desk-size-lock')) toggleFreeSize(sizePanel.uid);
    }

    /** Обновить панель: текущий размер, подсветка размера из списка, замок */
    function refreshSizePanel() {
        if (!sizePanel) return;

        var el = itemEl(sizePanel.uid);
        var st = Desk.state[sizePanel.uid];
        if (!el || !st) {
            closeSizePanel();
            return;
        }

        var node = el.gridstackNode || {};
        var w = node.w || st.w;
        var h = node.h || st.h;
        var current = w + 'x' + h;
        var free = !!st.free_size;

        sizePanel.el.classList.toggle('is-free', free);
        sizePanel.el.querySelectorAll('.desk-size-chip').forEach(function (chip) {
            chip.classList.toggle('is-active', !free && chip.getAttribute('data-size') === current);
        });

        var now = sizePanel.el.querySelector('.desk-size-now');
        if (now) now.textContent = w + '×' + h;

        var lock = sizePanel.el.querySelector('.desk-size-lock');
        if (lock) {
            lock.classList.toggle('is-open', free);
            lock.title = free
                ? 'Любой размер. Нажмите, чтобы вернуться к размерам виджета'
                : 'Только размеры виджета. Нажмите, чтобы задать любой размер';
            lock.innerHTML = '<i class="fa-light ' + (free ? 'fa-lock-open' : 'fa-lock') + '"></i>';
        }

        positionSizePanel();
    }

    /**
     * Поставить панель под блоком (у левого края — подальше от ручки ресайза в правом углу),
     * а если снизу не помещается — над блоком
     */
    function positionSizePanel() {
        if (!sizePanel) return;

        var el = itemEl(sizePanel.uid);
        if (!el) {
            closeSizePanel();
            return;
        }

        var box = el.getBoundingClientRect();
        var panel = sizePanel.el;
        var height = panel.offsetHeight;
        var top = box.bottom + 8;

        if (top + height > window.innerHeight - 8) {
            top = Math.max(8, box.top - height - 8);
        }

        panel.style.top = top + 'px';
        panel.style.left = Math.max(8, Math.min(box.left, window.innerWidth - panel.offsetWidth - 8)) + 'px';
    }

    /** Убрать панель размеров */
    function closeSizePanel() {
        if (!sizePanel) return;

        var el = itemEl(sizePanel.uid);
        if (el) el.classList.remove('desk-size-target');

        sizePanel.el.remove();
        sizePanel = null;
    }

    /**
     * Сброс из библиотеки (элемент .desk-lib-item с data-widget, gs-w, gs-h и
     * необязательным data-settings): временный элемент GridStack заменяем полноценным блоком
     */
    function onDropped(event, previousNode, newNode) {
        var el = newNode && newNode.el;
        if (!el || Desk.state[newNode.id]) return;

        var widget = el.getAttribute('data-widget');
        var pos = {x: newNode.x || 0, y: newNode.y || 0};
        var size = (newNode.w || 1) + 'x' + (newNode.h || 1);
        var settings = {};

        try {
            settings = JSON.parse(el.getAttribute('data-settings') || '{}') || {};
        } catch (e) {
            settings = {};
        }

        setTimeout(function () {
            Desk.grid.removeWidget(el, true, false);

            if (!widget || !Desk.meta[widget]) {
                toastr.error('Этот виджет недоступен', 'Это провал!');
                updateEmpty();
                return;
            }

            Desk.addWidget(widget, size, settings, pos);
        }, 0);
    }

    /*** СОХРАНЕНИЕ ***/

    /** Сохранить раскладку: координаты 32 колонок, настройки — из Desk.state */
    Desk.save = function () {
        if (!Desk.grid || !Desk.editing) return;

        if (Desk.grid.getColumn() !== COLUMNS) {
            toastr.info('Сохранить раскладку можно на широком экране', 'Рабочий стол');
            return;
        }

        // GridStack.save не пишет значения по умолчанию (x/y = 0, w/h = 1)
        var items = Desk.grid.save(false).map(function (node) {
            var st = Desk.state[node.id] || {};

            return {
                uid: node.id,
                widget: st.widget,
                x: node.x || 0,
                y: node.y || 0,
                w: node.w || 1,
                h: node.h || 1,
                free_size: st.free_size ? 1 : 0,
                settings: st.settings || {}
            };
        }).filter(function (item) {
            return !!item.widget;
        });

        body_block();

        post(Desk.urls.save, {items: JSON.stringify(items), context: JSON.stringify(Desk.context || {})})
            .done(function (response) {
                body_unblock();

                if (!response || response.result !== 'success') {
                    toastr.error(errorText(response), 'Это провал!');
                    return;
                }

                if (response.version !== undefined) Desk.summary.version = response.version;
                Desk.setDirty(false);
                Desk.edit(false);
                toastr.success('Рабочий стол сохранён', 'Это успех!');
            })
            .fail(function () {
                body_unblock();
                toastr.error('Не получилось сохранить рабочий стол', 'Это провал!');
            });
    };

    /*** ЧАСЫ ВИДЖЕТОВ ***/

    /**
     * Время в блоках с data-desk-clock="Europe/Moscow" (виджет «Часы»).
     *
     * Сервер рисует время на момент отрисовки, дальше его ведёт браузер: иначе часы
     * показывали бы время загрузки страницы. Формат — в data-desk-clock-format ("24" или "12")
     */
    function tickClocks() {
        var nodes = document.querySelectorAll('[data-desk-clock]');
        if (!nodes.length) return;

        nodes.forEach(function (node) {
            var zone = node.getAttribute('data-desk-clock');
            var hour12 = node.getAttribute('data-desk-clock-format') === '12';

            try {
                node.textContent = new Intl.DateTimeFormat('ru-RU', {
                    timeZone: zone,
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: hour12
                }).format(new Date());
            } catch (e) {
                // неизвестный часовой пояс — оставляем то, что отрисовал сервер
            }
        });
    }

    /*** САМООБНОВЛЕНИЕ ВИДЖЕТОВ ***/

    /** Когда виджет обновлялся сам: uid => метка времени */
    var reloaded = {};

    /**
     * Виджеты с data-desk-reload="секунды" перезапрашиваются сами.
     *
     * Атрибут ставит вьюха виджета (например «Внешняя страница» и «Курсы валют»);
     * блок за экраном не трогаем — он перерисуется, когда до него дойдут
     */
    function tickReload() {
        var nodes = document.querySelectorAll('[data-desk-reload]');
        if (!nodes.length) return;

        var now = Date.now();

        nodes.forEach(function (node) {
            var uid = uidOf(node);
            var seconds = parseInt(node.getAttribute('data-desk-reload'), 10);
            if (!uid || !seconds || seconds < 30) return;

            var last = reloaded[uid] || 0;
            if (now - last < seconds * 1000) return;

            reloaded[uid] = now;
            if (!last) return;

            if (!Desk.editing) Desk.refresh(uid, true);
        });
    }

    /*** КОНТЕКСТ СТОЛА ***/

    /**
     * Выбрать валюту или период стола и перерисовать зависящие виджеты
     *
     * @param {string} key currency | period
     * @param {string} value
     */
    Desk.setContext = function (key, value) {
        if (key !== 'currency' && key !== 'period') return;

        var data = {};
        data[key] = value;

        post(Desk.urls.context, data)
            .done(function (response) {
                if (!response || response.result !== 'success') {
                    toastr.error(errorText(response), 'Это провал!');
                    return;
                }

                Desk.context = response.context || Desk.context;

                var flag = key === 'currency' ? 'uses_currency' : 'uses_period';
                Object.keys(Desk.state).forEach(function (uid) {
                    var st = Desk.state[uid];
                    // ещё не загруженные виджеты возьмут новый контекст сами, когда дойдут до экрана
                    if (!st.available || !st.requested) return;
                    if (st.widget === key || (st.meta && st.meta[flag])) Desk.refresh(uid, false);
                });
            })
            .fail(function () {
                toastr.error('Не получилось сменить ' + (key === 'currency' ? 'валюту' : 'период') + ' стола', 'Это провал!');
            });
    };

    /*** ТОЧКИ ВХОДА ДЛЯ БИБЛИОТЕКИ, НАСТРОЕК И СТОЛОВ (этап A4) ***/

    /** Открыть библиотеку виджетов (заглушка до этапа A4) */
    Desk.openLibrary = function () {
        toastr.info('Библиотека появится на следующем этапе', 'Рабочий стол');
    };

    /**
     * Открыть попап настроек блока (заглушка до этапа A4). Попап вызывает Desk.applySettings
     *
     * @param {string} uid
     */
    Desk.openSettings = function (uid) {
        if (!Desk.state[uid]) return;

        toastr.info('Настройки виджета появятся на следующем этапе', 'Рабочий стол');
    };

    /**
     * Применить настройки блока: состояние, «есть изменения», перерисовка
     *
     * @param {string} uid
     * @param {Object} settings
     */
    Desk.applySettings = function (uid, settings) {
        var st = Desk.state[uid];
        if (!st) return;

        if (Desk.summary.can_edit && !Desk.editing) Desk.edit(true);

        st.settings = settings && typeof settings === 'object' && !Array.isArray(settings) ? settings : {};
        Desk.setDirty(true);
        Desk.refresh(uid, false);
    };

    /**
     * Добавить виджет на стол
     *
     * @param {string} widgetId id виджета (Widget::id)
     * @param {string} [size] «4x2»; по умолчанию meta.default_size, неразрешённый — ближайший
     * @param {Object} [settings]
     * @param {{x: number, y: number}|null} [pos] null — первое свободное место
     * @returns {string|null} uid нового блока
     */
    Desk.addWidget = function (widgetId, size, settings, pos) {
        var meta = Desk.meta[widgetId];
        if (!meta) {
            toastr.error('Этот виджет недоступен', 'Это провал!');
            return null;
        }

        if (!Desk.editing && !Desk.edit(true)) return null;

        var wh = parseSize(size || meta.default_size);
        if (!allowsSize(meta, wh[0], wh[1])) wh = nearestSize(meta, wh[0], wh[1]);

        var uid = newUid();
        var node = registerItem({
            uid: uid,
            widget: widgetId,
            x: pos ? pos.x : 0,
            y: pos ? pos.y : 0,
            w: wh[0],
            h: wh[1],
            settings: settings || {}
        });

        if (!pos) {
            delete node.x;
            delete node.y;
            node.autoPosition = true;
        }

        var el = Desk.grid.addWidget(node);
        mountItem(el);
        updateEmpty();
        Desk.setDirty(true);

        if (el.scrollIntoView) el.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        if (meta.needs_setup) Desk.openSettings(uid);

        return uid;
    };

    /** «Сделать своим»: копия стола в личные и переход на неё */
    Desk.copyDesktop = function () {
        body_block();

        post(Desk.urls.copy, {})
            .done(function (response) {
                if (response && response.result === 'success' && response.url) {
                    Desk.dirty = false;
                    toastr.success(response.message, 'Это успех!');
                    window.location.href = response.url;
                    return;
                }

                body_unblock();
                toastr.error(errorText(response), 'Это провал!');
            })
            .fail(function () {
                body_unblock();
                toastr.error('Не получилось скопировать стол', 'Это провал!');
            });
    };

    window.Desk = Desk;
})(window, document, jQuery);
