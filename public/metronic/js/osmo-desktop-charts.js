/*
 * Рабочий стол (patch v30): графики внутри виджетов на ApexCharts.
 *
 * Виджет отдаёт с сервера пустой блок с настройками в data-chart (его собирает
 * Widget::chart()), а этот файл превращает его в график после вставки HTML —
 * Desk.applyHtml зовёт DeskChart.render(элемент блока).
 *
 * График живёт по площади блока: размер блока меняется (в том числе свободным
 * размером) — ApexCharts перерисовывается по ResizeObserver.
 */
(function (window, document) {
    'use strict';

    var DeskChart = {
        /** Отрисованные графики: элемент → {chart, observer} */
        items: new WeakMap()
    };

    /** Цвета Metronic по имени */
    function color(name) {
        var css = getComputedStyle(document.documentElement);
        var value = css.getPropertyValue('--bs-' + (name || 'primary')).trim();

        return value || '#009ef7';
    }

    /**
     * Построить настройки ApexCharts из описания виджета
     *
     * @param {Object} conf {type, series, labels, colors, stacked, money, symbol, sparkline, height}
     * @returns {Object}
     */
    function options(conf) {
        var colors = (conf.colors || ['primary']).map(color);
        var spark = !!conf.sparkline;

        var opts = {
            chart: {
                type: conf.type || 'line',
                height: '100%',
                width: '100%',
                parentHeightOffset: 0,
                fontFamily: 'inherit',
                toolbar: {show: false},
                zoom: {enabled: false},
                animations: {enabled: false},
                sparkline: {enabled: spark},
                stacked: !!conf.stacked
            },
            colors: colors,
            series: conf.series || [],
            labels: conf.labels || [],
            dataLabels: {enabled: false},
            legend: {show: !spark && !!conf.legend, fontSize: '11px', markers: {width: 8, height: 8}},
            grid: {show: !spark, borderColor: color('gray-200'), strokeDashArray: 3, padding: {left: 0, right: 0, top: 0, bottom: 0}},
            tooltip: {enabled: conf.tooltip !== false, style: {fontSize: '12px'}},
            stroke: {curve: 'smooth', width: conf.type === 'bar' ? 0 : 2},
            xaxis: {
                categories: conf.categories || [],
                labels: {show: !spark, style: {fontSize: '10px', colors: color('gray-600')}, rotate: 0, hideOverlappingLabels: true, trim: true},
                axisBorder: {show: false},
                axisTicks: {show: false},
                tooltip: {enabled: false}
            },
            yaxis: {
                labels: {show: !spark, style: {fontSize: '10px', colors: color('gray-600')}, formatter: short},
                forceNiceScale: true
            },
            plotOptions: {
                bar: {borderRadius: 3, columnWidth: conf.column_width || '55%', horizontal: !!conf.horizontal},
                radialBar: {
                    hollow: {size: '58%'},
                    track: {background: color('gray-200')},
                    dataLabels: {
                        name: {show: !!conf.label, fontSize: '11px', color: color('gray-600'), offsetY: 18},
                        value: {fontSize: '18px', fontWeight: 700, offsetY: conf.label ? -16 : 6, formatter: function (v) { return Math.round(v) + ' %'; }}
                    }
                }
            }
        };

        if (conf.money) {
            opts.tooltip.y = {formatter: function (value) { return short(value) + ' ' + (conf.symbol || ''); }};
        }

        return opts;
    }

    /** Число коротко: как Widget::compact на сервере */
    function short(value) {
        var abs = Math.abs(value || 0);
        if (abs >= 1e9) return (value / 1e9).toFixed(1).replace('.', ',') + ' млрд';
        if (abs >= 1e6) return (value / 1e6).toFixed(1).replace('.', ',') + ' млн';
        if (abs >= 1e4) return Math.round(value / 1e3) + ' тыс.';

        return String(Math.round(value)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    /**
     * Отрисовать графики внутри элемента (блок стола или карточка библиотеки)
     *
     * @param {HTMLElement|Document} root
     */
    DeskChart.render = function (root) {
        if (typeof window.ApexCharts === 'undefined') return;

        (root || document).querySelectorAll('.desk-chart[data-chart]').forEach(function (node) {
            DeskChart.destroy(node);

            var conf;
            try {
                conf = JSON.parse(node.getAttribute('data-chart'));
            } catch (e) {
                return;
            }

            var chart = new window.ApexCharts(node, options(conf));
            chart.render();

            // блок меняет размер (в том числе свободным размером) — график пересчитывается
            var observer = null;
            if ('ResizeObserver' in window) {
                var timer = null;
                observer = new ResizeObserver(function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        try {
                            chart.updateOptions({chart: {height: '100%'}}, false, false);
                        } catch (e) { /* график уже снят */ }
                    }, 120);
                });
                observer.observe(node);
            }

            DeskChart.items.set(node, {chart: chart, observer: observer});
        });
    };

    /**
     * Снять график с элемента (перед заменой HTML блока)
     *
     * @param {HTMLElement} node
     */
    DeskChart.destroy = function (node) {
        var item = DeskChart.items.get(node);
        if (!item) return;

        if (item.observer) item.observer.disconnect();
        try {
            item.chart.destroy();
        } catch (e) { /* уже снят */ }

        DeskChart.items.delete(node);
    };

    /**
     * Снять все графики внутри элемента
     *
     * @param {HTMLElement} root
     */
    DeskChart.clear = function (root) {
        if (!root) return;

        root.querySelectorAll('.desk-chart').forEach(DeskChart.destroy);
    };

    window.DeskChart = DeskChart;
})(window, document);
