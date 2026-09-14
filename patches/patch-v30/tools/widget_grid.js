/*
 * Прогон виджета по сетке размеров (patch v30) — замеры внутри страницы.
 *
 * Страницу собирает widget_grid.php: в ней один виджет во всех размерах, каждый в
 * <section class="desk-grid-run" data-size="WxH">. Скрипт дорисовывает графики и подгон .desk-fit,
 * ждёт, затем для каждого размера смотрит, что видно, что обрезано, что многоточием,
 * какой самый мелкий шрифт и сколько площади занято. Отчёт — JSON в
 * <script id="desk-grid-report">, его читает PHP из --dump-dom безголового Edge.
 */
(function () {
    'use strict';

    var errors = [];
    window.addEventListener('error', function (e) { errors.push(String(e.message || e)); });

    var SKIP = 'script,style,template,noscript';

    function visible(el) {
        if (!el.getClientRects().length) return false;
        var cs = getComputedStyle(el);
        return cs.visibility !== 'hidden' && parseFloat(cs.opacity) > 0.05;
    }

    function label(el) {
        var name = el.tagName.toLowerCase();
        var classes = Array.prototype.filter.call(el.classList, function (c) {
            return /^desk-|^badge|^symbol|^bullet|^btn/.test(c);
        }).slice(0, 2);

        return name + (classes.length ? '.' + classes.join('.') : '');
    }

    function textOf(el) {
        return (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 48);
    }

    /** Есть ли у элемента собственный текст (не только у детей) */
    function ownText(el) {
        for (var i = 0; i < el.childNodes.length; i++) {
            var node = el.childNodes[i];
            if (node.nodeType === 3 && node.nodeValue.trim() !== '') return true;
        }
        return false;
    }

    /** Видимая область элемента: пересечение с обрезающими предками до блока виджета */
    function viewport(el, widget) {
        var box = {left: -1e9, top: -1e9, right: 1e9, bottom: 1e9};
        var scroll = false;
        var clip = false;

        for (var p = el.parentElement; p; p = p.parentElement) {
            var cs = getComputedStyle(p);
            if (cs.overflowX !== 'visible' || cs.overflowY !== 'visible' || p === widget) {
                var r = p.getBoundingClientRect();
                box.left = Math.max(box.left, r.left);
                box.top = Math.max(box.top, r.top);
                box.right = Math.min(box.right, r.right);
                box.bottom = Math.min(box.bottom, r.bottom);
                if (/auto|scroll/.test(cs.overflowY + cs.overflowX)) scroll = true;
                if (p.classList.contains('desk-clip')) clip = true;
            }
            if (p === widget) break;
        }

        return {box: box, scroll: scroll, clip: clip};
    }

    function measure(section) {
        var out = {
            size: section.getAttribute('data-size'),
            exception: section.getAttribute('data-exception') || null,
            error: false, empty: false,
            shown: [], truncated: [], clipped: [], cut: 0, scrolled: 0, faded: 0, fitOut: 0,
            minFont: null, cover: [0, 0], charts: [], overflow: false
        };

        var widget = section.querySelector('.desk-widget');
        if (!widget) return out;

        out.error = !!widget.querySelector('.desk-error');
        out.empty = !!widget.querySelector('.desk-empty');
        // спрятанная подпись «ещё N» — не строка
        out.fitOut = widget.querySelectorAll('.desk-fit-out:not([data-fit-more])').length;
        out.overflow = widget.scrollHeight > widget.clientHeight + 1 || widget.scrollWidth > widget.clientWidth + 1;

        var body = widget.querySelector('.desk-widget-body') || widget;
        var br = body.getBoundingClientRect();
        var bcs = getComputedStyle(body);
        var inner = {
            left: br.left + parseFloat(bcs.paddingLeft), right: br.right - parseFloat(bcs.paddingRight),
            top: br.top + parseFloat(bcs.paddingTop), bottom: br.bottom - parseFloat(bcs.paddingBottom)
        };
        var used = {left: 1e9, top: 1e9, right: -1e9, bottom: -1e9};
        var seen = {};

        widget.querySelectorAll('*').forEach(function (el) {
            if (el.matches(SKIP) || el.closest('.apexcharts-canvas')) return;

            var isChart = el.classList.contains('desk-chart');
            var isLeaf = isChart || ownText(el) || el.matches('img,input,select,button,.desk-bar,.desk-split,svg,i');
            if (!isLeaf || !visible(el)) return;

            var r = el.getBoundingClientRect();
            if (r.width < 1 || r.height < 1) return;

            var v = viewport(el, widget);
            var inside = r.right > v.box.left + 1 && r.left < v.box.right - 1 && r.bottom > v.box.top + 1 && r.top < v.box.bottom - 1;
            var text = isChart ? '[график]' : (el.tagName === 'I' ? '' : textOf(el));
            var head = !!el.closest('.desk-widget-head');

            if (!inside) {
                if (v.scroll) out.scrolled++; else if (v.clip) out.faded++; else out.cut++;
                return;
            }

            var cutEdge = r.left < v.box.left - 1.5 || r.right > v.box.right + 1.5 || r.top < v.box.top - 1.5 || r.bottom > v.box.bottom + 1.5;
            if (cutEdge && !v.scroll) {
                if (v.clip) {
                    out.faded++;
                } else if (text !== '' || isChart) {
                    out.clipped.push(label(el) + (text ? ' «' + text + '»' : ''));
                }
            }

            if (text !== '' && !isChart && el.scrollWidth > el.clientWidth + 1 && getComputedStyle(el).overflowX !== 'visible') {
                out.truncated.push(text);
            }

            if (isChart) out.charts.push(Math.round(r.width) + 'x' + Math.round(r.height));

            if (text !== '' && !isChart) {
                var fs = parseFloat(getComputedStyle(el).fontSize);
                if (out.minFont === null || fs < out.minFont) out.minFont = fs;
                if (!head && !seen[text] && out.shown.length < 40) {
                    seen[text] = true;
                    out.shown.push(text);
                }
            }

            if (!head) {
                used.left = Math.min(used.left, Math.max(r.left, inner.left));
                used.right = Math.max(used.right, Math.min(r.right, inner.right));
                used.top = Math.min(used.top, Math.max(r.top, inner.top));
                used.bottom = Math.max(used.bottom, Math.min(r.bottom, inner.bottom));
            }
        });

        var iw = Math.max(1, inner.right - inner.left);
        var ih = Math.max(1, inner.bottom - inner.top);
        if (used.right > used.left) {
            out.cover = [Math.round((used.right - used.left) / iw * 100), Math.round((used.bottom - used.top) / ih * 100)];
        }
        out.body = [Math.round(iw), Math.round(ih)];

        return out;
    }

    function run() {
        var report = {errors: errors, sizes: []};
        document.querySelectorAll('section.desk-grid-run').forEach(function (section) {
            try {
                report.sizes.push(measure(section));
            } catch (e) {
                report.sizes.push({size: section.getAttribute('data-size'), exception: 'measure: ' + e.message});
            }
        });

        var node = document.createElement('script');
        node.type = 'application/json';
        node.id = 'desk-grid-report';
        node.textContent = JSON.stringify(report).replace(/</g, '\\u003c');
        document.body.appendChild(node);
    }

    window.addEventListener('load', function () {
        try {
            if (window.DeskChart) window.DeskChart.render(document);
        } catch (e) { errors.push('charts: ' + e.message); }

        var ready = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
        ready.then(function () {
            if (window.DeskFit) window.DeskFit.render(document);
            setTimeout(run, 1500);
        });
    });
})();
