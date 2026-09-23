/*
 * Рабочий стол (patch v30): подгон списков и таблиц под размер блока.
 *
 * Блок бывает любого размера, а сторона ячейки зависит от ширины экрана, поэтому сервер
 * не знает точно, сколько строк влезет. Вьюха отдаёт строки с запасом ($rows_max) и
 * помечает контейнер классом .desk-fit, а скрипт прячет строки, которые не влезли
 * целиком (класс desk-fit-out) — половины строки на краю блока не бывает.
 *
 * Разметка:
 *   .desk-fit                    — контейнер с ограниченной высотой (.desk-stack-grow, ячейка
 *                                  сетки и т.п.); строки — его прямые дети
 *   data-fit-items="tbody > tr"  — свой выбор строк внутри контейнера (таблица)
 *   data-fit-axis="x"            — подгон по ширине (чипы, колонки), по умолчанию по высоте
 *   data-fit-min="1"             — сколько строк оставить, даже если не влезают (по умолчанию 1)
 *   [data-fit-more="ещё {n}"]    — подпись о спрятанных строках: внутри контейнера (строкой
 *                                  не считается) или сразу за ним; {n} — число; без спрятанных
 *                                  строк подпись скрыта
 *   data-fit-extra="N"           — на подписи: сколько строк сервер не отдал сверх лимита
 *                                  (прибавляется к {n}; подпись видна, даже если всё влезло)
 *
 * Пересчёт — по ResizeObserver на контейнере и после загрузки шрифтов.
 */
(function (window, document) {
    'use strict';

    var DeskFit = {
        items: new Map()    // контейнер => {observer, size}
    };

    /** Строки контейнера: дети или выбор по data-fit-items, без подписи «ещё» */
    function rows(box) {
        var selector = box.getAttribute('data-fit-items');
        var list = selector
            ? Array.prototype.slice.call(box.querySelectorAll(selector))
            : Array.prototype.slice.call(box.children);

        return list.filter(function (el) { return !el.hasAttribute('data-fit-more'); });
    }

    /** Подпись «ещё N»: внутри контейнера или следующий сосед */
    function moreLabel(box) {
        var inner = box.querySelector(':scope > [data-fit-more]');
        if (inner) return inner;

        var next = box.nextElementSibling;
        return next && next.hasAttribute('data-fit-more') ? next : null;
    }

    function overflows(box, axis) {
        return axis === 'x'
            ? box.scrollWidth > box.clientWidth + 1
            : box.scrollHeight > box.clientHeight + 1;
    }

    /**
     * Подогнать один контейнер: показать всё, затем прятать строки с конца, пока содержимое
     * не влезет (итоговая строка и подпись «ещё» остаются на месте)
     *
     * @param {HTMLElement} box
     */
    DeskFit.fit = function (box) {
        var axis = box.getAttribute('data-fit-axis') === 'x' ? 'x' : 'y';
        var min = parseInt(box.getAttribute('data-fit-min'), 10);
        if (isNaN(min)) min = 1;

        var list = rows(box);
        var more = moreLabel(box);
        // строки, которые сервер не отдал сверх лимита, — тоже «ещё»
        var extra = more ? Math.max(0, parseInt(more.getAttribute('data-fit-extra'), 10) || 0) : 0;

        list.forEach(function (el) { el.classList.remove('desk-fit-out'); });
        if (more) {
            more.textContent = template(more, extra);
            more.classList.toggle('desk-fit-out', extra === 0);
        }

        if (!overflows(box, axis)) return;
        if (more) {
            more.textContent = template(more, 99 + extra);
            more.classList.remove('desk-fit-out');
        }

        // строки, начинающиеся за краем, прячем сразу — без пересчёта раскладки на каждую
        var edge = axis === 'x' ? box.getBoundingClientRect().right : box.getBoundingClientRect().bottom;
        var hidden = 0;
        for (var i = list.length - 1; i >= min; i--) {
            var r = list[i].getBoundingClientRect();
            if ((axis === 'x' ? r.left : r.top) < edge) break;
            list[i].classList.add('desk-fit-out');
            hidden++;
        }
        for (var j = list.length - 1 - hidden; j >= min && overflows(box, axis); j--) {
            list[j].classList.add('desk-fit-out');
            hidden++;
        }

        if (more) {
            if (hidden + extra > 0) {
                more.textContent = template(more, hidden + extra);
            } else {
                more.classList.add('desk-fit-out');
            }
        }
    };

    function template(more, count) {
        return (more.getAttribute('data-fit-more') || '+{n}').replace('{n}', count);
    }

    /**
     * Подогнать контейнеры внутри элемента и следить за их размером
     *
     * @param {HTMLElement|Document} root
     */
    DeskFit.render = function (root) {
        (root || document).querySelectorAll('.desk-fit').forEach(function (box) {
            DeskFit.destroy(box);
            DeskFit.fit(box);

            var item = {observer: null, size: box.clientWidth + 'x' + box.clientHeight};
            if ('ResizeObserver' in window) {
                var frame = 0;
                item.observer = new ResizeObserver(function () {
                    // скрытие строк не должно запускать новый пересчёт: реагируем только на размер
                    var size = box.clientWidth + 'x' + box.clientHeight;
                    if (size === item.size) return;
                    item.size = size;

                    cancelAnimationFrame(frame);
                    frame = requestAnimationFrame(function () { DeskFit.fit(box); });
                });
                item.observer.observe(box);
            }

            DeskFit.items.set(box, item);
        });
    };

    /**
     * Перестать следить за контейнером
     *
     * @param {HTMLElement} box
     */
    DeskFit.destroy = function (box) {
        var item = DeskFit.items.get(box);
        if (!item) return;

        if (item.observer) item.observer.disconnect();
        DeskFit.items.delete(box);
    };

    /**
     * Снять слежение со всех контейнеров внутри элемента (перед заменой HTML блока)
     *
     * @param {HTMLElement} root
     */
    DeskFit.clear = function (root) {
        if (!root) return;

        root.querySelectorAll('.desk-fit').forEach(DeskFit.destroy);
    };

    // шрифт догрузился — высота строк изменилась
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(function () {
            DeskFit.items.forEach(function (item, box) { DeskFit.fit(box); });
        });
    }

    window.DeskFit = DeskFit;
})(window, document);
