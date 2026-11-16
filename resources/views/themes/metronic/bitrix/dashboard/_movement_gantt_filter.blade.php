{{-- Фильтр сделок диаграммы Ганта в попапе (patch v50, решение владельца 16.11).
     Клиентский: данные уже в JSON диаграммы (window.MovementGantt), сервер не нужен.
     Кнопка «Фильтр», бейдж и плашки условий — в панели _movement_gantt.blade.php. --}}

@section('styles')
    @parent
    <style>
        #mgf-modal .mgf-dot { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 6px; vertical-align: -1px; }
        #mgf-modal .mgf-dur-row { display: grid; grid-template-columns: minmax(0, 1fr) 90px 90px 36px; gap: 8px; align-items: center; }
        @media (max-width: 575.98px) {
            #mgf-modal .mgf-dur-row { grid-template-columns: minmax(0, 1fr) 70px 70px 36px; gap: 6px; }
        }
        #mgf-chips .mgf-chip { display: inline-flex; align-items: center; gap: 4px; max-width: 100%; }
        #mgf-chips .mgf-chip span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #mgf-chips .mgf-chip button { border: 0; background: transparent; padding: 0 0 0 2px; color: inherit; line-height: 1; }
    </style>
@endsection

<div class="modal fade" id="mgf-modal" tabindex="-1" aria-labelledby="mgf-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down mw-sm-650px">
        <div class="modal-content">
            <div class="modal-header py-4">
                <h3 class="modal-title fs-4" id="mgf-title"><i class="fa-light fa-filter me-2"></i>Фильтр сделок диаграммы</h3>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Закрыть">
                    <i class="fa-light fa-xmark fs-2"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-6">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label for="mgf-stages" class="form-label fw-semibold m-0">Стадии в сделке</label>
                        <div class="btn-group" role="group" aria-label="Режим стадий">
                            <input type="radio" class="btn-check" name="mgf-mode" id="mgf-mode-any" value="any" checked>
                            <label class="btn btn-sm btn-light btn-active-primary" for="mgf-mode-any">любая из выбранных</label>
                            <input type="radio" class="btn-check" name="mgf-mode" id="mgf-mode-all" value="all">
                            <label class="btn btn-sm btn-light btn-active-primary" for="mgf-mode-all">все выбранные</label>
                        </div>
                    </div>
                    <select id="mgf-stages" class="form-select form-select-sm" multiple data-placeholder="Любые стадии"></select>
                </div>

                <div class="mb-6">
                    <label for="mgf-managers" class="form-label fw-semibold">Менеджер</label>
                    <select id="mgf-managers" class="form-select form-select-sm" multiple data-placeholder="Все менеджеры"></select>
                </div>

                <div class="mb-6">
                    <label for="mgf-partners" class="form-label fw-semibold">Партнёр</label>
                    <select id="mgf-partners" class="form-select form-select-sm" multiple data-placeholder="Все партнёры"></select>
                    <div class="form-text">Партнёр — компания сделки в Битрикс24, как в фильтре «Партнёр» реестра сделок.</div>
                </div>

                <div>
                    <label class="form-label fw-semibold">Длительность стадии</label>
                    <div class="form-text mt-0 mb-3">
                        Сумма дней сделки в стадии за всю историю (все заходы, открытая стадия — до сегодня, меньше дня = 0).
                        Пустое «от» или «до» — без границы. Все условия выполняются одновременно.
                    </div>
                    <div class="mgf-dur-row text-gray-600 fs-8 fw-semibold mb-1 d-none" id="mgf-durs-head">
                        <span>Стадия</span><span>от, дн.</span><span>до, дн.</span><span></span>
                    </div>
                    <div class="d-flex flex-column gap-2" id="mgf-durs"></div>
                    <button type="button" class="btn btn-sm btn-light-primary mt-3" id="mgf-dur-add"><i class="fa-light fa-plus"></i> условие</button>
                </div>
            </div>
            <div class="modal-footer py-3 flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-light me-auto" id="mgf-reset">Сбросить</button>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-sm btn-primary" id="mgf-apply"><i class="fa-light fa-check"></i> Применить</button>
            </div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        // фильтр сделок диаграммы в попапе (patch v50).
        // Дописываемые секции Blade складываются так, что этот скрипт стоит раньше скрипта диаграммы, —
        // поэтому запуск после разбора страницы, когда window.MovementGantt уже есть.
        document.addEventListener('DOMContentLoaded', function () {
            const G = window.MovementGantt;
            if (!G) return;

            const STORE_KEY = 'deal_movement_gantt_filter';
            const DAY = 86400000;
            const NO_PARTNER = '__none__';   // значение пункта «партнёр не указан»

            const modalEl = document.getElementById('mgf-modal');
            document.body.appendChild(modalEl);   // вкладка может быть скрыта — модалка живёт у body
            const $ = window.jQuery;
            const hasSelect2 = !!($ && $.fn && $.fn.select2);

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
                });
            }
            function ms(d) { return Date.UTC(+d.slice(0, 4), +d.slice(5, 7) - 1, +d.slice(8, 10)); }
            function ruSort(a, b) { return a.localeCompare(b, 'ru'); }

            // ---- справочники из данных года и суммарные длительности стадий по сделкам ----
            const stageNames = G.data.stages.map(function (s) { return s.name; });
            const managers = [], partners = [];
            let noPartner = false;
            G.data.deals.forEach(function (d) {
                if (managers.indexOf(d.manager) < 0) managers.push(d.manager);
                if (d.company) { if (partners.indexOf(d.company) < 0) partners.push(d.company); } else noPartner = true;

                d._stageDays = {};
                d.segments.forEach(function (s) {
                    const days = s.final ? 0 : Math.max(0, Math.round((ms(s.to) - ms(s.from)) / DAY));
                    d._stageDays[s.stage] = (d._stageDays[s.stage] || 0) + days;
                });
            });
            managers.sort(ruSort);
            partners.sort(ruSort);

            // ---- состояние фильтра ----
            function empty() { return {stages: [], mode: 'any', managers: [], partners: [], durations: []}; }

            /** Убрать то, чего нет в данных выбранного года, и привести типы */
            function sanitize(f) {
                const out = empty();
                if (!f || typeof f !== 'object') return out;
                out.mode = f.mode === 'all' ? 'all' : 'any';
                out.stages = (f.stages || []).filter(function (v) { return stageNames.indexOf(v) >= 0; });
                out.managers = (f.managers || []).filter(function (v) { return managers.indexOf(v) >= 0; });
                out.partners = (f.partners || []).filter(function (v) { return v === NO_PARTNER ? noPartner : partners.indexOf(v) >= 0; });
                out.durations = (f.durations || []).filter(function (c) { return c && stageNames.indexOf(c.stage) >= 0; }).map(function (c) {
                    return {stage: c.stage, from: num(c.from), to: num(c.to)};
                });
                return out;
            }
            function num(v) {
                if (v === null || v === undefined || v === '') return null;
                const n = parseInt(v, 10);
                return isNaN(n) || n < 0 ? null : n;
            }

            let filter = empty();
            try { filter = sanitize(JSON.parse(localStorage.getItem(STORE_KEY) || 'null')); } catch (e) {}

            function activeCount(f) {
                return (f.stages.length ? 1 : 0) + (f.managers.length ? 1 : 0) + (f.partners.length ? 1 : 0) + f.durations.length;
            }

            /** Условие отбора по состоянию фильтра; null — фильтр пуст */
            function matcher(f) {
                if (!activeCount(f)) return null;
                return function (d) {
                    if (f.stages.length) {
                        const has = function (s) { return d._stageDays[s] !== undefined; };
                        if (f.mode === 'all' ? !f.stages.every(has) : !f.stages.some(has)) return false;
                    }
                    if (f.managers.length && f.managers.indexOf(d.manager) < 0) return false;
                    if (f.partners.length && f.partners.indexOf(d.company || NO_PARTNER) < 0) return false;
                    for (let i = 0; i < f.durations.length; i++) {
                        const c = f.durations[i], days = d._stageDays[c.stage];
                        if (days === undefined) return false;
                        if (c.from !== null && days < c.from) return false;
                        if (c.to !== null && days > c.to) return false;
                    }
                    return true;
                };
            }

            function save() { try { localStorage.setItem(STORE_KEY, JSON.stringify(filter)); } catch (e) {} }

            /** Применить состояние: диаграмма, бейдж, плашки; fit — затем масштаб «100%» по оставшимся сделкам */
            function apply(fit) {
                save();
                G.setFilter(matcher(filter), fit);
                const n = activeCount(filter);
                const badge = document.getElementById('mgf-badge');
                badge.textContent = n;
                badge.classList.toggle('d-none', !n);
                document.getElementById('mgf-reset-inline').classList.toggle('d-none', !n);
                chips();
            }

            function durText(c) {
                if (c.from !== null && c.to !== null) return c.stage + ': ' + c.from + '–' + c.to + ' дн.';
                if (c.from !== null) return c.stage + ': от ' + c.from + ' дн.';
                if (c.to !== null) return c.stage + ': до ' + c.to + ' дн.';
                return c.stage + ': есть в сделке';
            }
            function partnerName(v) { return v === NO_PARTNER ? 'не указан' : v; }

            /** Плашки активных условий под панелью, у каждой — крестик */
            function chips() {
                const box = document.getElementById('mgf-chips');
                const list = [];
                if (filter.stages.length) list.push(['stages', 'Стадии (' + (filter.mode === 'all' ? 'все' : 'любая') + '): ' + filter.stages.join(', ')]);
                if (filter.managers.length) list.push(['managers', 'Менеджер: ' + filter.managers.join(', ')]);
                if (filter.partners.length) list.push(['partners', 'Партнёр: ' + filter.partners.map(partnerName).join(', ')]);
                filter.durations.forEach(function (c, i) { list.push(['dur:' + i, durText(c)]); });

                box.innerHTML = list.map(function (c) {
                    return '<span class="badge badge-light-primary fs-7 fw-semibold mgf-chip" title="' + esc(c[1]) + '"><span>' + esc(c[1]) + '</span>'
                        + '<button type="button" data-mgf-remove="' + esc(c[0]) + '" title="Убрать условие"><i class="fa-light fa-xmark"></i></button></span>';
                }).join('');
                box.classList.toggle('d-none', !list.length);
                box.classList.toggle('d-flex', !!list.length);
            }

            document.getElementById('mgf-chips').addEventListener('click', function (e) {
                const b = e.target.closest('[data-mgf-remove]');
                if (!b) return;
                const key = b.getAttribute('data-mgf-remove');
                if (key.indexOf('dur:') === 0) filter.durations.splice(+key.slice(4), 1);
                else filter[key] = [];
                apply(true);
            });

            // ---- форма в попапе ----
            const selStages = document.getElementById('mgf-stages');
            const selManagers = document.getElementById('mgf-managers');
            const selPartners = document.getElementById('mgf-partners');
            const colorOf = {};
            G.data.stages.forEach(function (s) { colorOf[s.name] = s.color; });

            selStages.innerHTML = stageNames.map(function (n) { return '<option value="' + esc(n) + '">' + esc(n) + '</option>'; }).join('');
            selManagers.innerHTML = managers.map(function (n) { return '<option value="' + esc(n) + '">' + esc(n) + '</option>'; }).join('');
            selPartners.innerHTML = (noPartner ? '<option value="' + NO_PARTNER + '">партнёр не указан</option>' : '')
                + partners.map(function (n) { return '<option value="' + esc(n) + '">' + esc(n) + '</option>'; }).join('');

            if (hasSelect2) {
                const base = {dropdownParent: $(modalEl), width: '100%', closeOnSelect: false, allowClear: true, theme: 'bootstrap5'};
                const dot = function (item) {
                    if (!item.id) return item.text;
                    return $('<span><i class="mgf-dot" style="background:' + (colorOf[item.id] || '#a1a5b7') + '"></i>' + esc(item.text) + '</span>');
                };
                $(selStages).select2(Object.assign({placeholder: 'Любые стадии', templateResult: dot, templateSelection: dot}, base));
                $(selManagers).select2(Object.assign({placeholder: 'Все менеджеры', minimumResultsForSearch: Infinity}, base));
                $(selPartners).select2(Object.assign({placeholder: 'Все партнёры'}, base));
            } else {
                [selStages, selManagers, selPartners].forEach(function (s) { s.size = 6; });
            }

            function getVals(sel) { return Array.from(sel.selectedOptions).map(function (o) { return o.value; }); }
            function setVals(sel, vals) {
                Array.from(sel.options).forEach(function (o) { o.selected = vals.indexOf(o.value) >= 0; });
                if (hasSelect2) $(sel).trigger('change');
            }

            const durs = document.getElementById('mgf-durs');
            function durRow(c) {
                const row = document.createElement('div');
                row.className = 'mgf-dur-row';
                row.innerHTML = '<select class="form-select form-select-sm" data-f="stage">'
                    + stageNames.map(function (n) { return '<option value="' + esc(n) + '"' + (c.stage === n ? ' selected' : '') + '>' + esc(n) + '</option>'; }).join('')
                    + '</select>'
                    + '<input type="number" min="0" step="1" class="form-control form-control-sm" data-f="from" placeholder="—" value="' + (c.from ?? '') + '">'
                    + '<input type="number" min="0" step="1" class="form-control form-control-sm" data-f="to" placeholder="—" value="' + (c.to ?? '') + '">'
                    + '<button type="button" class="btn btn-sm btn-icon btn-light-danger" data-mgf-del title="Удалить условие"><i class="fa-light fa-trash"></i></button>';
                durs.appendChild(row);
                durHead();
            }
            function durHead() { document.getElementById('mgf-durs-head').classList.toggle('d-none', !durs.children.length); }

            durs.addEventListener('click', function (e) {
                const b = e.target.closest('[data-mgf-del]');
                if (!b) return;
                b.closest('.mgf-dur-row').remove();
                durHead();
            });
            document.getElementById('mgf-dur-add').addEventListener('click', function () {
                durRow({stage: stageNames[0], from: null, to: null});
            });

            /** Заполнить форму текущим состоянием (при каждом открытии — «Отмена» ничего не меняет) */
            function fill() {
                document.getElementById(filter.mode === 'all' ? 'mgf-mode-all' : 'mgf-mode-any').checked = true;
                setVals(selStages, filter.stages);
                setVals(selManagers, filter.managers);
                setVals(selPartners, filter.partners);
                durs.innerHTML = '';
                filter.durations.forEach(durRow);
                durHead();
            }

            /** Прочитать форму в состояние */
            function read() {
                const f = empty();
                f.mode = document.getElementById('mgf-mode-all').checked ? 'all' : 'any';
                f.stages = getVals(selStages);
                f.managers = getVals(selManagers);
                f.partners = getVals(selPartners);
                durs.querySelectorAll('.mgf-dur-row').forEach(function (row) {
                    let from = num(row.querySelector('[data-f="from"]').value), to = num(row.querySelector('[data-f="to"]').value);
                    if (from !== null && to !== null && from > to) { const t = from; from = to; to = t; }
                    f.durations.push({stage: row.querySelector('[data-f="stage"]').value, from: from, to: to});
                });
                return sanitize(f);
            }

            const modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
            modalEl.addEventListener('show.bs.modal', fill);

            document.getElementById('mgf-open').addEventListener('click', function () {
                this.blur();
                if (modal) modal.show();
            });
            document.getElementById('mgf-apply').addEventListener('click', function () {
                filter = read();
                apply(true);
                if (modal) modal.hide();
            });
            document.getElementById('mgf-reset').addEventListener('click', function () {
                filter = empty();
                fill();
                apply(true);
                if (modal) modal.hide();
            });
            document.getElementById('mgf-reset-inline').addEventListener('click', function () {
                filter = empty();
                apply(true);
            });

            apply(true);   // при загрузке страницы тоже «100%» под отфильтрованные сделки
        });
    </script>
@endsection
