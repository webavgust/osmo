{{-- Диаграмма Ганта по сделкам года (patch v50, дополнение 16.11).
     Данные — DealMovementService::gantt() в $gantt, шаблон ссылки на сделку — $deal_url (%s — id).
     Рисуется на клиенте (vanilla JS), без внешних библиотек. --}}

@section('styles')
    @parent
    <style>
        #mg-gantt { --mg-bg: var(--bs-card-bg, var(--bs-body-bg)); --mg-left: 300px; }
        #mg-gantt .mg-scroll { position: relative; overflow: auto; max-height: 75vh; border-top: 1px solid var(--bs-border-color); }
        #mg-gantt .mg-inner { position: relative; }

        /* шапка: месяцы и дни */
        #mg-gantt .mg-head { position: sticky; top: 0; z-index: 5; display: flex; background: var(--mg-bg); border-bottom: 1px solid var(--bs-border-color); }
        #mg-gantt .mg-corner { position: sticky; left: 0; z-index: 6; flex: 0 0 var(--mg-left); width: var(--mg-left); background: var(--mg-bg);
            border-right: 1px solid var(--bs-border-color); padding: 6px 12px 6px 6px; gap: 6px; font-weight: 600; color: var(--bs-gray-700); display: flex; align-items: flex-end; }
        #mg-gantt .mg-scale { position: relative; flex: 0 0 auto; }
        #mg-gantt .mg-months, #mg-gantt .mg-days { position: relative; }
        #mg-gantt .mg-months { height: 26px; }
        #mg-gantt .mg-years { position: relative; height: 22px; border-bottom: 1px solid var(--bs-border-color); }
        #mg-gantt .mg-yr { position: absolute; top: 0; bottom: 0; padding: 3px 6px 0; font-size: 12px; font-weight: 700; color: var(--bs-gray-800);
            border-left: 1px solid var(--bs-border-color); white-space: nowrap; overflow: hidden; }
        #mg-gantt .mg-yr.mg-yr-current { background: var(--bs-primary-light); color: var(--bs-primary); }
        #mg-gantt .mg-yearband { position: absolute; top: 0; bottom: 0; z-index: 0; pointer-events: none; background: var(--bs-primary-light);
            opacity: .45; border-left: 1px dashed var(--bs-primary); border-right: 1px dashed var(--bs-primary); }
        #mg-gantt .mg-days { height: 20px; border-top: 1px solid var(--bs-border-color); }
        #mg-gantt .mg-month { position: absolute; top: 0; bottom: 0; padding: 5px 6px 0; font-size: 12px; font-weight: 600; color: var(--bs-gray-700);
            border-left: 1px solid var(--bs-border-color); white-space: nowrap; overflow: hidden; }
        #mg-gantt .mg-day { position: absolute; top: 0; bottom: 0; font-size: 10px; line-height: 20px; text-align: center; color: var(--bs-gray-600); }
        #mg-gantt .mg-day.mg-weekend { background: var(--bs-gray-100); color: var(--bs-gray-500); }
        #mg-gantt .mg-today-label { position: absolute; top: 0; transform: translateX(-50%); font-size: 10px; font-weight: 600; line-height: 14px;
            padding: 0 4px; border-radius: 3px; background: var(--bs-danger); color: #fff; white-space: nowrap; z-index: 1; }

        /* сетка: разделители месяцев и линия «Сегодня» */
        #mg-gantt .mg-body { position: relative; z-index: 0; }
        #mg-gantt .mg-vline { position: absolute; top: 0; bottom: 0; width: 0; border-left: 1px solid var(--bs-border-color); opacity: .7; z-index: 1; pointer-events: none; }
        /* отсечка года: на стыке декабря и января линия толще и темнее */
        /* отсечка года в шапке: сплошная линия через годы, месяцы и дни (под меткой «Сегодня») */
        #mg-gantt .mg-hyear { position: absolute; top: 0; bottom: 0; width: 0; margin-left: -1px; border-left: 2px solid var(--bs-gray-400); z-index: 0; pointer-events: none; }
        #mg-gantt .mg-vline.mg-vline-year { border-left: 2px solid var(--bs-gray-400); opacity: 1; margin-left: -1px; }
        #mg-gantt .mg-today { position: absolute; top: 0; bottom: 0; width: 2px; margin-left: -1px; background: var(--bs-danger); z-index: 2; pointer-events: none; }

        /* строки */
        #mg-gantt .mg-row { display: flex; position: relative; border-bottom: 1px solid var(--bs-border-color); }
        #mg-gantt .mg-row:hover, #mg-gantt .mg-row:hover > .mg-left { background: var(--bs-gray-100); }
        #mg-gantt .mg-left { position: sticky; left: 0; z-index: 3; flex: 0 0 var(--mg-left); width: var(--mg-left); background: var(--mg-bg);
            border-right: 1px solid var(--bs-border-color); display: flex; align-items: center; gap: 6px; padding: 0 10px 0 6px; min-width: 0; }
        #mg-gantt .mg-track { position: relative; flex: 0 0 auto; }
        #mg-gantt .mg-deal { height: 40px; }
        #mg-gantt .mg-deal > .mg-left { cursor: pointer; }
        /* раскрытая сделка: блок «строка сделки + стадии» выделен границами сверху и снизу */
        #mg-gantt .mg-deal-open { border-top: 2px solid var(--bs-gray-500); }
        #mg-gantt .mg-sub { border-bottom: 2px solid var(--bs-gray-500); }
        /* одна линия 2px: верхняя рамка ложится на 1px-границу строки выше, у последней стадии своя граница не нужна,
           две раскрытые сделки подряд — нижняя рамка первой служит верхней второй */
        #mg-gantt .mg-deal-open { margin-top: -1px; }
        #mg-gantt .mg-sub > .mg-stage:last-of-type { border-bottom: 0; }
        #mg-gantt .mg-sub + .mg-deal-open { border-top: 0; margin-top: 0; }
        #mg-gantt .mg-stage { height: 26px; }
        #mg-gantt .mg-stage > .mg-left { padding-left: 36px; font-size: 12px; color: var(--bs-gray-700); }
        #mg-gantt .mg-toggle { flex: 0 0 22px; width: 22px; height: 22px; padding: 0; border: 0; background: transparent; color: var(--bs-gray-600); border-radius: 4px; }
        #mg-gantt .mg-toggle:hover { color: var(--bs-primary); background: var(--bs-gray-200); }
        #mg-gantt .mg-name { min-width: 0; flex: 1 1 auto; line-height: 1.2; }
        #mg-gantt .mg-name a { display: block; font-size: 12.5px; font-weight: 600; color: var(--bs-gray-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #mg-gantt .mg-name a:hover { color: var(--bs-primary); }
        #mg-gantt .mg-meta { font-size: 11px; color: var(--bs-gray-600); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #mg-gantt .mg-stage-name { flex: 1 1 auto; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #mg-gantt .mg-stage-days { flex: 0 0 auto; font-size: 11px; color: var(--bs-gray-600); }
        #mg-gantt .mg-dot { flex: 0 0 10px; width: 10px; height: 10px; border-radius: 2px; }

        /* отрезки стадий и метки итога */
        #mg-gantt .mg-seg { position: absolute; top: 50%; height: 14px; margin-top: -7px; border-radius: 2px; z-index: 1; }
        /* наведение: отрезок становится полупрозрачным (70%) */
        #mg-gantt .mg-seg:hover { opacity: .7; z-index: 3; }
        #mg-gantt .mg-seg-chev { border-radius: 0; }
        /* подпись длительности отрезка (развёрнутый вид); фон прячет проходящие вертикали стрелок */
        #mg-gantt .mg-lbl { position: absolute; top: 50%; margin-top: -7px; height: 14px; line-height: 14px; font-size: 11px; text-align: center;
            color: var(--bs-gray-600); white-space: nowrap; z-index: 2; pointer-events: none; background: var(--mg-bg); border-radius: 2px; }
        #mg-gantt .mg-lbl.mg-lbl-in { background: transparent; text-align: right; font-weight: 600; }
        #mg-gantt .mg-lbl.mg-lbl-light { color: #fff; }
        #mg-gantt .mg-lbl.mg-lbl-dark { color: #1e1e2d; }
        /* развёрнутый вид: полоса выше (16px), подписи внутри — название стадии слева, длительность справа */
        #mg-gantt .mg-stage .mg-seg { height: 16px; margin-top: -8px; }
        #mg-gantt .mg-stage .mg-lbl.mg-lbl-in { margin-top: -8px; height: 16px; line-height: 16px; }
        #mg-gantt .mg-lbl.mg-lbl-name { text-align: left; overflow: hidden; }
        #mg-gantt .mg-mark { position: absolute; top: 50%; width: 12px; height: 12px; margin: -6px 0 0 -6px; border-radius: 50%; z-index: 2;
            box-shadow: 0 0 0 2px var(--mg-bg); }
        #mg-gantt .mg-arrows { position: absolute; top: 0; z-index: 1; pointer-events: none; overflow: visible; color: var(--bs-gray-500); }

        /* заголовки групп и пустое состояние */
        #mg-gantt .mg-grouphead { height: 24px; background: var(--bs-gray-100); }
        #mg-gantt .mg-grouphead > .mg-left { background: var(--bs-gray-100); font-size: 12px; font-weight: 700; color: var(--bs-gray-800); padding-left: 12px; }
        #mg-gantt .mg-empty { padding: 40px 20px; text-align: center; color: var(--bs-gray-600); }

        /* плитки групп «Новые запросы» как фильтр */
        #deal-movement .mg-tile { cursor: pointer; transition: outline-color .15s; outline: 2px solid transparent; outline-offset: -2px; }
        #deal-movement .mg-tile:hover { outline-color: var(--bs-gray-300); }
        #deal-movement .mg-tile.mg-tile-active { outline-color: var(--bs-primary); }
    </style>
@endsection

<div class="card" id="mg-gantt">
    <div class="card-header align-items-center py-4 gap-3">
        <h4 class="card-title flex-column align-items-start m-0">
            <span class="fw-bold text-gray-900">Сделки на диаграмме {{ $year }}</span>
            <span class="text-gray-600 fs-7 fw-semibold mt-1" id="mg-count"></span>
        </h4>
        <div class="card-toolbar d-flex flex-wrap gap-2 m-0">
            <div class="btn-group" role="group" aria-label="Какие сделки">
                <button type="button" class="btn btn-sm btn-light btn-active-primary active" data-mg-new="0" title="Сделки, у которых в {{ $year }} году был переход или хотя бы день в работе">
                    Все сделки года
                </button>
                <button type="button" class="btn btn-sm btn-light btn-active-primary" data-mg-new="1" title="Только сделки, созданные в {{ $year }} году">
                    Только новые запросы года
                </button>
            </div>
            <div class="btn-group" role="group" aria-label="Период шкалы">
                <button type="button" class="btn btn-sm btn-light btn-active-primary active" data-mg-range="year" title="Шкала — только {{ $year }} год, отрезки обрезаны его границами">
                    Только {{ $year }}
                </button>
                <button type="button" class="btn btn-sm btn-light btn-active-primary" data-mg-range="all" title="Шкала — вся история показанных сделок, {{ $year }} год выделен">
                    Вся история
                </button>
            </div>
            <div class="btn-group" role="group" aria-label="Масштаб">
                <button type="button" class="btn btn-sm btn-light btn-active-primary active" data-mg-scale="fit"><i class="fa-light fa-calendar"></i> <span id="mg-fit-label">Год</span></button>
                <button type="button" class="btn btn-sm btn-light btn-active-primary" data-mg-scale="quarter"><i class="fa-light fa-calendar-range"></i> Квартал</button>
                <button type="button" class="btn btn-sm btn-light btn-active-primary" data-mg-scale="month"><i class="fa-light fa-calendar-days"></i> Месяц</button>
            </div>
            <div class="btn-group" role="group" aria-label="Зум">
                <button type="button" class="btn btn-sm btn-icon btn-light" id="mg-zoom-out" title="Мельче"><i class="fa-light fa-magnifying-glass-minus"></i></button>
                <button type="button" class="btn btn-sm btn-light" id="mg-zoom-data" title="Масштаб по сделкам: все показанные отрезки ровно на один экран">100%</button>
                <button type="button" class="btn btn-sm btn-icon btn-light" id="mg-zoom-in" title="Крупнее"><i class="fa-light fa-magnifying-glass-plus"></i></button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center gap-4 px-6 py-3">
            {{-- фильтр по группе сделки (по выбранному срезу); числа — по текущему отбору «все / новые» --}}
            <div class="btn-group" role="group" aria-label="Группа сделки">
                <button type="button" class="btn btn-sm btn-light btn-active-primary active" data-mg-group="">Все <span class="opacity-75" data-mg-num=""></span></button>
                <button type="button" class="btn btn-sm btn-light btn-active-info" data-mg-group="work"><i class="fa-light fa-person-running"></i> В работе <span class="opacity-75" data-mg-num="work"></span></button>
                <button type="button" class="btn btn-sm btn-light btn-active-danger" data-mg-group="lost"><i class="fa-light fa-circle-xmark"></i> Отвалились <span class="opacity-75" data-mg-num="lost"></span></button>
                <button type="button" class="btn btn-sm btn-light btn-active-success" data-mg-group="done"><i class="fa-light fa-circle-check"></i> Закончились <span class="opacity-75" data-mg-num="done"></span></button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="mg-sort" class="fs-7 fw-semibold text-gray-700 text-nowrap">Сортировка</label>
                <select id="mg-sort" class="form-select form-select-sm w-auto">
                    <option value="funnel">По стадии воронки</option>
                    <option value="created_desc">По дате создания: новые сверху</option>
                    <option value="created_asc">По дате создания: старые сверху</option>
                    <option value="changed">По последнему изменению</option>
                    <option value="duration">По длительности сделки</option>
                    <option value="manager">По менеджеру</option>
                </select>
            </div>
            {{-- фильтр в попапе (_movement_gantt_filter) --}}
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light-primary" id="mgf-open">
                    <i class="fa-light fa-filter"></i> Фильтр
                    <span class="badge badge-circle badge-primary ms-1 d-none" id="mgf-badge"></span>
                </button>
                <button type="button" class="btn btn-sm btn-link text-gray-600 d-none p-0" id="mgf-reset-inline">Сбросить</button>
            </div>
        </div>
        <div class="d-none flex-wrap gap-2 px-6 pb-3" id="mgf-chips"></div>
        <div class="mg-scroll" id="mg-scroll">
            <div class="mg-inner" id="mg-inner"></div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        // диаграмма Ганта «Движение сделок» и вкладки страницы (patch v50)
        (function () {
            const DATA = @json($gantt);
            const AT = @json($at);
            const DEAL_URL = @json($deal_url);

            // ширина левой колонки, px (как --mg-left); на телефоне уже, чтобы шкале оставалось место
            const LEFT = window.innerWidth < 576 ? 130 : 300;
            document.getElementById('mg-gantt')?.style.setProperty('--mg-left', LEFT + 'px');
            const ROW_SUB = 26;               // высота подстроки стадии, px (как .mg-stage)
            const BAR = 14;                   // высота полосы, px
            const BAR_SUB = 16;               // высота полосы в строке стадии (развёрнутый вид), px
            const CHEV = 6;                   // глубина уголка на стыке стадий в свёрнутой полосе, px
            const SCALES = {fit: null, quarter: 10, month: 32};   // пресеты ширины дня, px; fit — вписать период в ширину области
            const ZOOM_STEP = 1.5;            // шаг кнопок −/+
            const ZOOM_MAX = 48;              // верхний предел ширины дня, px
            const MONTHS = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
            const MONTHS_SHORT = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];   // если месяц уже 80 px
            const GROUP_FIELD = AT === 'year_end' ? 'group_ye' : 'group';
            const DAY = 86400000;
            const STORE_KEY = 'deal_movement_gantt_range';
            const ZOOM_KEY = 'deal_movement_gantt_zoom_';   // + range: 'fit' или ширина дня

            const YEAR = DATA.year;
            const Y0 = Date.UTC(YEAR, 0, 1);
            const Y1 = Date.UTC(YEAR + 1, 0, 1);

            const stages = {};
            DATA.stages.forEach(function (s, i) { stages[s.name] = Object.assign({order: i}, s); });

            let savedRange = 'year';
            try { if (localStorage.getItem(STORE_KEY) === 'all') savedRange = 'all'; } catch (e) {}

            // ---- сортировка строк (на клиенте) ----
            const SORT_KEY = 'deal_movement_gantt_sort';
            const GROUP_RANK = {work: 0, done: 1, lost: 2};
            const TODAY_STR = DATA.today;
            // момент среза: «сейчас» — сегодня; «на конец года» — 31.12 (для текущего года — сегодня)
            const CUTOFF = AT === 'year_end' && (YEAR + '-12-31') < TODAY_STR ? YEAR + '-12-31' : TODAY_STR;

            // текущая стадия на срез и дата входа в неё, длительность сделки — считаются один раз
            DATA.deals.forEach(function (d) {
                const segs = d.segments;
                let cur = segs[0];
                segs.forEach(function (s) { if (s.from <= CUTOFF) cur = s; });
                const last = segs[segs.length - 1];
                d._stage = cur.stage;
                d._entered = cur.from;
                d._funnel = stages[cur.stage] ? stages[cur.stage].funnel : -1;
                d._end = last.final ? last.from : TODAY_STR;
            });

            function cmpStr(a, b) { return a < b ? -1 : (a > b ? 1 : 0); }

            /** «По стадии воронки»: в работе (дальше по воронке выше, при равной — кто раньше вошёл), закончились, отвалились (свежие выше) */
            function cmpFunnel(a, b) {
                const ga = a[GROUP_FIELD], gb = b[GROUP_FIELD];
                if (ga !== gb) return GROUP_RANK[ga] - GROUP_RANK[gb];
                if (ga === 'work') return (b._funnel - a._funnel) || cmpStr(a._entered, b._entered) || a.id - b.id;
                return cmpStr(b._entered, a._entered) || a.id - b.id;
            }

            function duration(d) { return (Date.parse(d._end) - Date.parse(d.created)) / DAY; }

            const SORTS = {
                funnel: cmpFunnel,
                created_desc: function (a, b) { return cmpStr(b.created, a.created) || b.id - a.id; },
                created_asc: function (a, b) { return cmpStr(a.created, b.created) || a.id - b.id; },
                changed: function (a, b) { return cmpStr(b.changed, a.changed) || b.id - a.id; },
                duration: function (a, b) { return (duration(b) - duration(a)) || a.id - b.id; },
                manager: function (a, b) { return a.manager.localeCompare(b.manager, 'ru') || cmpFunnel(a, b); },
            };

            let savedSort = 'funnel';
            try { const v = localStorage.getItem(SORT_KEY); if (v && SORTS[v]) savedSort = v; } catch (e) {}

            // zoom — ширина дня, px; null — вписать период (fit)
            const state = {zoom: null, range: savedRange, group: null, onlyNew: false, open: new Set(), w: 1, sort: savedSort};

            /** Масштаб для периода из localStorage */
            function loadZoom(range) {
                try {
                    const v = parseFloat(localStorage.getItem(ZOOM_KEY + range));
                    return v > 0 ? Math.min(ZOOM_MAX, v) : null;
                } catch (e) { return null; }
            }
            function saveZoom() {
                try { localStorage.setItem(ZOOM_KEY + state.range, state.zoom ? String(state.zoom) : 'fit'); } catch (e) {}
            }
            state.zoom = loadZoom(state.range);

            const scroll = document.getElementById('mg-scroll');
            const inner = document.getElementById('mg-inner');

            /** Дата Y-m-d в UTC-миллисекундах */
            function ms(d) { return Date.UTC(+d.slice(0, 4), +d.slice(5, 7) - 1, +d.slice(8, 10)); }

            // период шкалы: «Только год» — 1.01…31.12; «Вся история» — с начала месяца самой ранней даты
            // среди сделок года до конца месяца с последним событием (не раньше сегодня);
            // начало потом уточняется по видимым сделкам — historyStart()
            const HISTORY = (function () {
                let min = Y0, max = Math.max(ms(DATA.today), Y1 - DAY);
                DATA.deals.forEach(function (d) {
                    d.segments.forEach(function (s) { min = Math.min(min, ms(s.from)); max = Math.max(max, ms(s.to)); });
                });
                const a = new Date(min), b = new Date(max);
                return {start: Date.UTC(a.getUTCFullYear(), a.getUTCMonth(), 1), end: Date.UTC(b.getUTCFullYear(), b.getUTCMonth() + 1, 1)};
            })();

            /**
             * Начало «Вся история» по видимым сейчас сделкам (фильтр попапа, группа, «только новые»):
             * начало месяца самой ранней даты, но не позже 1 января выбранного года — год виден целиком;
             * нет видимых сделок — выбранный год
             */
            function historyStart() {
                let min = Y0;
                DATA.deals.forEach(function (d) {
                    if (visible(d)) d.segments.forEach(function (s) { min = Math.min(min, ms(s.from)); });
                });
                const a = new Date(min);
                return Date.UTC(a.getUTCFullYear(), a.getUTCMonth(), 1);
            }

            /** Текущий период шкалы: start (ms), days; конец «Вся история» — по всем сделкам года */
            function period() {
                const p = state.range === 'all' ? {start: historyStart(), end: HISTORY.end} : {start: Y0, end: Y1};
                return {start: p.start, days: Math.round((p.end - p.start) / DAY)};
            }

            let P = period();

            /** Номер дня от начала периода (может быть вне 0…P.days) */
            function dayIdx(d) { return Math.round((ms(d) - P.start) / DAY); }
            function msIdx(t) { return Math.round((t - P.start) / DAY); }

            function fmt(d) { return d.slice(8, 10) + '.' + d.slice(5, 7) + '.' + d.slice(0, 4); }

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
                });
            }

            function plural(n, one, few, many) {
                const n10 = n % 10, n100 = n % 100;
                if (n10 === 1 && n100 !== 11) return one;
                if (n10 >= 2 && n10 <= 4 && (n100 < 10 || n100 >= 20)) return few;
                return many;
            }

            function color(name) { return stages[name] ? stages[name].color : '#a1a5b7'; }

            /** Фон отрезка: заливка цветом стадии; пауза (Suspended) — бледная штриховка тем же цветом */
            function fill(name) {
                const c = color(name);
                if (!stages[name] || !stages[name].pause) return 'background:' + c;
                const rgb = [1, 3, 5].map(function (i) { return parseInt(c.slice(i, i + 2), 16); }).join(',');
                return 'background:repeating-linear-gradient(135deg,rgba(' + rgb + ',.75) 0 2px,rgba(' + rgb + ',.18) 2px 6px)';
            }

            /** Длительность: «<1 дн.» для отрезков внутри одного дня */
            function daysText(n) { return n < 1 ? '<1 дн.' : n + ' дн.'; }

            /** Ширина дня, при которой период вписывается в область шкалы без прокрутки */
            function fitWidth() {
                return Math.max(0.2, Math.floor((scroll.clientWidth - LEFT) / P.days * 1000) / 1000);
            }

            /** Ширина дня, px: не меньше «вписать» (мельче — пустое место справа) */
            function dayWidth() {
                const fit = fitWidth();
                return state.zoom && state.zoom > fit ? state.zoom : fit;
            }

            /** Геометрия отрезка: x начала/конца (px, до обрезки) и подсказка */
            function geometry(seg, isLast, w) {
                const open = isLast && !seg.final;
                const xs = dayIdx(seg.from) * w;
                const xe = open ? (dayIdx(seg.to) + 0.5) * w : dayIdx(seg.to) * w;
                const days = Math.round((ms(seg.to) - ms(seg.from)) / DAY);
                const title = seg.final
                    ? seg.stage + ': ' + fmt(seg.from) + ' — итог'
                    : seg.stage + ': с ' + fmt(seg.from) + ' по ' + (open ? 'сегодня, ' + fmt(seg.to) : fmt(seg.to)) + ', ' + daysText(days);

                return {xs: xs, xe: xe, open: open, days: days, title: title};
            }

            /** HTML полосы отрезка или метки итога, обрезанный границами периода; пусто, если вне периода */
            function segHtml(seg, isLast, w, W, chev) {
                const g = geometry(seg, isLast, w);
                if (seg.final) {
                    if (g.xs < 0 || g.xs > W) return '';
                    return '<div class="mg-mark" style="left:' + g.xs + 'px;background:' + color(seg.stage) + '" title="' + esc(g.title) + '"></div>';
                }
                if (g.xe < 0 || g.xs > W) return '';
                const a = Math.max(0, g.xs), b = Math.min(W, g.xe);
                let width = Math.max(2, b - a), shape = '';
                // свёрнутая полоса: стык стадий уголком — острие справа заходит в выемку следующего отрезка
                if (chev) {
                    const tip = chev.tip && b === g.xe && b < W;      // справа не обрезано границей периода
                    const notch = chev.notch && a === g.xs;            // слева не обрезано
                    if (tip || notch) {
                        if (tip) width += CHEV;
                        shape = ';clip-path:polygon(0 0,' + (tip ? 'calc(100% - ' + CHEV + 'px) 0,100% 50%,calc(100% - ' + CHEV + 'px) 100%' : '100% 0,100% 100%')
                            + ',0 100%' + (notch ? ',' + CHEV + 'px 50%' : '') + ');border-radius:0;';
                    }
                }
                return '<div class="mg-seg' + (shape ? ' mg-seg-chev' : '') + '" style="left:' + a + 'px;width:' + width + 'px;' + fill(seg.stage) + shape + '" title="' + esc(g.title) + '"></div>';
            }

            /** Сделка в отборе «все / новые» (без группы и без фильтра попапа) */
            function inYear(deal) { return !state.onlyNew || deal.is_new; }

            /** Сделка в отборе «все / новые» и под фильтром попапа (без группы) */
            function inBase(deal) { return inYear(deal) && (!state.filter || state.filter(deal)); }

            /** Подходит ли сделка под все фильтры */
            function visible(deal) { return inBase(deal) && (!state.group || deal[GROUP_FIELD] === state.group); }

            /** Подпись месяца по ширине: полная, «Янв», первая буква или ничего */
            function monthLabel(m, width) {
                if (width >= 80) return MONTHS[m];
                if (width >= 30) return MONTHS_SHORT[m];
                if (width >= 12) return MONTHS[m].charAt(0);
                return '';
            }

            /** Шапка: годы (в «Вся история»), месяцы, дни (если день не уже 18px), метка «Сегодня» */
            function headHtml(w, W) {
                let years = '', months = '', days = '', yearLines = '';
                const first = new Date(P.start);
                let y = first.getUTCFullYear(), m = first.getUTCMonth();
                while (Date.UTC(y, m, 1) < P.start + P.days * DAY) {
                    const a = msIdx(Date.UTC(y, m, 1)), b = msIdx(Date.UTC(y, m + 1, 1));
                    if (m === 0 && a > 0) yearLines += '<div class="mg-hyear" style="left:' + a * w + 'px"></div>';
                    months += '<div class="mg-month" style="left:' + a * w + 'px;width:' + (b - a) * w + 'px">' + monthLabel(m, (b - a) * w) + '</div>';
                    if (++m === 12) { m = 0; y++; }
                }
                if (state.range === 'all') {
                    for (y = first.getUTCFullYear(); Date.UTC(y, 0, 1) < P.start + P.days * DAY; y++) {
                        const a = Math.max(0, msIdx(Date.UTC(y, 0, 1))), b = Math.min(P.days, msIdx(Date.UTC(y + 1, 0, 1)));
                        years += '<div class="mg-yr' + (y === YEAR ? ' mg-yr-current' : '') + '" style="left:' + a * w + 'px;width:' + (b - a) * w + 'px">' + y + '</div>';
                    }
                    years = '<div class="mg-years">' + years + '</div>';
                }
                if (w >= 18) {
                    for (let d = 0; d < P.days; d++) {
                        const date = new Date(P.start + d * DAY);
                        const wd = date.getUTCDay();
                        days += '<div class="mg-day' + (wd === 0 || wd === 6 ? ' mg-weekend' : '') + '" style="left:' + d * w + 'px;width:' + w + 'px">' + date.getUTCDate() + '</div>';
                    }
                }
                const today = dayIdx(DATA.today);
                if (today >= 0 && today < P.days) {
                    days += '<div class="mg-today-label" style="left:' + (today + 0.5) * w + 'px;top:3px">Сегодня</div>';
                }

                return '<div class="mg-head">'
                    + '<div class="mg-corner">' + toggleAllHtml() + 'Сделка</div>'
                    + '<div class="mg-scale" style="width:' + W + 'px">'
                    + years
                    + '<div class="mg-months">' + months + '</div>'
                    + '<div class="mg-days">' + days + '</div>'
                    + yearLines
                    + '</div></div>';
            }

            /** Тёмный ли цвет #rrggbb (для подписи внутри полосы — белый или тёмный текст) */
            function isDark(hex) {
                const c = [1, 3, 5].map(function (i) { return parseInt(hex.slice(i, i + 2), 16) / 255; });
                return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2] < 0.55;
            }

            /** Ширина текста в px по реальному шрифту страницы (canvas measureText) */
            const measureCtx = document.createElement('canvas').getContext('2d');
            const FONT_FAMILY = getComputedStyle(document.body).fontFamily || 'sans-serif';
            const textWidthCache = {};
            function textWidth(text, weight) {
                const key = weight + '|' + text;
                if (textWidthCache[key] === undefined) {
                    measureCtx.font = weight + ' 11px ' + FONT_FAMILY;
                    textWidthCache[key] = Math.ceil(measureCtx.measureText(text).width);
                }
                return textWidthCache[key];
            }

            /**
             * Подписи отрезков одной строки стадии (развёрнутый вид).
             * Внутри полосы: если вмещаются «название + отступ + длительность» — название слева у начала,
             * длительность справа у конца; если вмещается только длительность — она справа внутри.
             * Иначе длительность снаружи: справа от конца полосы (за стрелкой-переходом), иначе слева от начала,
             * иначе не показывается — длительность остаётся в title.
             * items — видимые отрезки строки по времени: {a, b, days, stage, arrow} или метка итога {a, b, mark}.
             */
            function labelsHtml(items, w, W) {
                const today = dayIdx(DATA.today);
                const todayX = today >= 0 && today < P.days ? (today + 0.5) * w : null;
                const PAD = 4, GAP = 8;     // отступ от краёв полосы и между названием и длительностью
                let html = '', taken = 0;   // taken — правая граница занятого (полосы и подписи) слева

                items.forEach(function (it, k) {
                    if (it.mark) { taken = Math.max(taken, it.b); return; }
                    const text = daysText(it.days);
                    const lw = textWidth(text, 600) + 2;
                    const bw = it.b - it.a;
                    let limit = k < items.length - 1 ? items[k + 1].a - 3 : W - 2;
                    if (todayX !== null && it.b <= todayX + 1) limit = Math.min(limit, todayX - 3);

                    const right = it.b + (it.arrow ? 9 : 4);   // за горизонталью стрелки (5px) и её вертикалью
                    const left = it.a - 4 - lw;
                    let style = null, cls = 'mg-lbl';
                    if (lw + 2 * PAD <= bw) {
                        // внутри полосы; на штриховке паузы — тёмный текст
                        const st = stages[it.stage];
                        const tone = st && !st.pause && isDark(st.color) ? ' mg-lbl-light' : ' mg-lbl-dark';
                        cls += ' mg-lbl-in' + tone;
                        style = 'left:' + (it.b - PAD - lw) + 'px';
                        const nw = textWidth(it.stage, 600) + 2;
                        if (nw + GAP + lw + 2 * PAD <= bw) {
                            html += '<span class="mg-lbl mg-lbl-in mg-lbl-name' + tone + '" style="left:' + (it.a + PAD) + 'px;width:' + nw + 'px">' + esc(it.stage) + '</span>';
                        }
                    } else if (right + lw <= limit) {
                        style = 'left:' + right + 'px';
                    } else if (left >= Math.max(0, taken + 3)) {
                        style = 'left:' + left + 'px';
                    }
                    if (style) {
                        html += '<span class="' + cls + '" style="' + style + ';width:' + lw + 'px">' + esc(text) + '</span>';
                        const m = style.match(/left:([\d.-]+)/);
                        taken = Math.max(it.b, +m[1] + lw);
                    } else {
                        taken = Math.max(taken, it.b);
                    }
                });

                return html;
            }

            /** Строка сделки (свёрнутая полоса) и, если развёрнута, подстроки стадий со стрелками */
            function dealHtml(deal, w, W) {
                const open = state.open.has(deal.id);
                const segs = deal.segments;
                const meta = deal.manager + (deal.company ? ' · ' + deal.company : '');
                const url = DEAL_URL.replace('%s', deal.id);

                let bars = '';
                segs.forEach(function (s, i) {
                    bars += segHtml(s, i === segs.length - 1, w, W, {
                        notch: i > 0 && !segs[i - 1].final,
                        tip: i < segs.length - 1 && !segs[i + 1].final,
                    });
                });

                let html = '<div class="mg-row mg-deal' + (open ? ' mg-deal-open' : '') + '" data-id="' + deal.id + '">'
                    + '<div class="mg-left" data-toggle="' + deal.id + '">'
                    + '<button type="button" class="mg-toggle" data-toggle="' + deal.id + '" title="' + (open ? 'Свернуть' : 'Развернуть по стадиям') + '">'
                    + '<i class="fa-light ' + (open ? 'fa-chevron-down' : 'fa-chevron-right') + '"></i></button>'
                    + '<div class="mg-name">'
                    + '<a href="' + esc(url) + '" target="_blank" title="' + esc(deal.title) + ' — открыть сделку в Битрикс24">' + esc(deal.title) + '</a>'
                    + '<div class="mg-meta" title="' + esc(meta) + '">' + esc(meta) + '</div>'
                    + '</div></div>'
                    + '<div class="mg-track" style="width:' + W + 'px">' + bars + '</div>'
                    + '</div>';

                if (!open) return html;

                // строки стадий в порядке первого посещения; повторные заходы — в той же строке
                const rows = [];
                segs.forEach(function (s) { if (rows.indexOf(s.stage) < 0) rows.push(s.stage); });

                let sub = '';
                rows.forEach(function (name) {
                    let cells = '', total = 0, finalOnly = true;
                    const items = [];   // видимые отрезки и метки строки по времени: для подписей длительности
                    segs.forEach(function (s, i) {
                        if (s.stage !== name) return;
                        const isLast = i === segs.length - 1;
                        cells += segHtml(s, isLast, w, W);
                        const g = geometry(s, isLast, w);
                        if (!s.final) { finalOnly = false; total += g.days; }
                        if (s.final) {
                            if (g.xs >= 0 && g.xs <= W) items.push({a: g.xs - 6, b: g.xs + 6, mark: true});
                        } else if (g.xe >= 0 && g.xs <= W) {
                            const a = Math.max(0, g.xs), b = Math.min(W, g.xe);
                            items.push({a: a, b: Math.max(a + 2, b), days: g.days, stage: s.stage, arrow: !isLast});
                        }
                    });
                    cells += labelsHtml(items, w, W);
                    sub += '<div class="mg-row mg-stage">'
                        + '<div class="mg-left"><i class="mg-dot" style="' + fill(name) + '"></i>'
                        + '<span class="mg-stage-name" title="' + esc(name) + '">' + esc(name) + '</span>'
                        + '<span class="mg-stage-days">' + (finalOnly ? 'итог' : esc(daysText(total))) + '</span></div>'
                        + '<div class="mg-track" style="width:' + W + 'px">' + cells + '</div>'
                        + '</div>';
                });

                // стрелки переходов: от конца отрезка вправо, затем вниз/вверх к следующему отрезку
                let paths = '';
                for (let i = 0; i < segs.length - 1; i++) {
                    const a = segs[i], b = segs[i + 1];
                    const x = dayIdx(a.to) * w;
                    if (x < 0 || x > W) continue;
                    const ra = rows.indexOf(a.stage), rb = rows.indexOf(b.stage);
                    if (ra === rb) continue;
                    const half = b.final ? 6 : BAR_SUB / 2;
                    const ya = ra * ROW_SUB + ROW_SUB / 2;
                    const down = rb > ra;
                    const yb = rb * ROW_SUB + ROW_SUB / 2 + (down ? -half - 1 : half + 1);
                    const x2 = x + 5;
                    paths += '<path d="M' + x + ' ' + ya + 'H' + x2 + 'V' + yb + '" fill="none" stroke="currentColor" stroke-width="1.2"/>'
                        + '<path d="M' + (x2 - 3.5) + ' ' + (yb + (down ? -5 : 5)) + 'L' + x2 + ' ' + yb + 'L' + (x2 + 3.5) + ' ' + (yb + (down ? -5 : 5)) + 'Z" fill="currentColor"/>';
                }
                const svg = '<svg class="mg-arrows" style="left:' + LEFT + 'px" width="' + W + '" height="' + rows.length * ROW_SUB + '">' + paths + '</svg>';

                return html + '<div class="mg-sub" style="position:relative">' + sub + svg + '</div>';
            }

            /** Подписи и числа кнопок фильтров */
            function toolbar() {
                const base = DATA.deals.filter(inBase);
                const counts = {'': base.length, work: 0, lost: 0, done: 0};
                base.forEach(function (d) { counts[d[GROUP_FIELD]]++; });
                document.querySelectorAll('[data-mg-num]').forEach(function (el) { el.textContent = counts[el.getAttribute('data-mg-num')]; });

                // «N из M»: M — все сделки отбора «все / новые», без группы и фильтра попапа
                const total = DATA.deals.filter(inYear).length;
                const shown = state.group ? counts[state.group] : base.length;
                document.getElementById('mg-count').textContent = (state.group || state.filter)
                    ? shown + ' из ' + total + ' ' + plural(total, 'сделки', 'сделок', 'сделок')
                    : shown + ' ' + plural(shown, 'сделка', 'сделки', 'сделок');
                document.getElementById('mg-fit-label').textContent = state.range === 'all' ? 'Всё' : 'Год';

                setActive('[data-mg-group]', 'data-mg-group', state.group || '');
                setActive('[data-mg-range]', 'data-mg-range', state.range);
                // пресет подсвечен, только если ширина дня с ним совпала
                const fit = fitWidth(), w = dayWidth();
                const preset = w <= fit + 0.001 ? 'fit' : (Math.abs(w - SCALES.quarter) < 0.01 ? 'quarter' : (Math.abs(w - SCALES.month) < 0.01 ? 'month' : ''));
                setActive('[data-mg-scale]', 'data-mg-scale', preset);
                document.getElementById('mg-zoom-out').disabled = w <= fit + 0.001;
                document.getElementById('mg-zoom-in').disabled = w >= ZOOM_MAX - 0.001;
                setActive('[data-mg-new]', 'data-mg-new', state.onlyNew ? '1' : '0');
                document.querySelectorAll('[data-gantt-group]').forEach(function (t) {
                    t.classList.toggle('mg-tile-active', t.getAttribute('data-gantt-group') === state.group);
                });
            }

            function render(again) {
                if (!scroll.offsetParent) return;   // вкладка скрыта — ширины нет, отрисуем при показе
                // «Вся история»: начало шкалы зависит от видимых сделок — левый край прокрутки держит ту же дату
                const prev = {start: P.start, w: state.w, left: scroll.scrollLeft};
                P = period();
                const w = dayWidth();
                const W = P.days * w;
                state.w = w;
                const deals = DATA.deals.filter(visible).sort(SORTS[state.sort]);

                toolbar();

                let grid = '';
                if (state.range === 'all') {
                    grid += '<div class="mg-yearband" style="left:' + (LEFT + msIdx(Y0) * w) + 'px;width:' + (msIdx(Y1) - msIdx(Y0)) * w + 'px"></div>';
                }
                const first = new Date(P.start);
                for (let y = first.getUTCFullYear(), m = first.getUTCMonth() + 1; Date.UTC(y, m, 1) < P.start + P.days * DAY; m++) {
                    // m растёт за 11 — Date.UTC переносит в следующий год; январь = отсечка года
                    grid += '<div class="mg-vline' + (m % 12 === 0 ? ' mg-vline-year' : '') + '" style="left:' + (LEFT + msIdx(Date.UTC(y, m, 1)) * w) + 'px"></div>';
                }
                const today = dayIdx(DATA.today);
                if (today >= 0 && today < P.days) grid += '<div class="mg-today" style="left:' + (LEFT + (today + 0.5) * w) + 'px"></div>';

                let rows = '';
                let manager = null;
                deals.forEach(function (d) {
                    // «По менеджеру» — строка-заголовок группы с именем и числом сделок
                    if (state.sort === 'manager' && d.manager !== manager) {
                        manager = d.manager;
                        const n = deals.filter(function (x) { return x.manager === manager; }).length;
                        rows += '<div class="mg-row mg-grouphead"><div class="mg-left">' + esc(manager) + '<span class="text-gray-600 fw-semibold ms-2">' + n + '</span></div>'
                            + '<div class="mg-track" style="width:' + W + 'px"></div></div>';
                    }
                    rows += dealHtml(d, w, W);
                });
                if (!deals.length) rows = '<div class="mg-empty" style="width:' + LEFT + 'px;position:sticky;left:0">Нет сделок под фильтр</div>';

                inner.style.width = (LEFT + W) + 'px';
                inner.innerHTML = headHtml(w, W) + '<div class="mg-body">' + grid + rows + '</div>';
                if (prev.start !== P.start && prev.w) {
                    scroll.scrollLeft = Math.max(0, (prev.left / prev.w + (prev.start - P.start) / DAY) * w);
                }

                // fit: после отрисовки могла появиться/пропасть вертикальная прокрутка — вписать ещё раз
                if (!again && dayWidth() !== w) render(true);
            }

            /** Перерисовать с новой шириной дня (null — вписать), сохранив дату в центре видимой области */
            function rescale(zoom) {
                const view = scroll.clientWidth - LEFT;
                const center = P.start + (scroll.scrollLeft + view / 2) / state.w * DAY;
                state.zoom = zoom && zoom > fitWidth() + 0.001 ? Math.min(ZOOM_MAX, zoom) : null;
                saveZoom();
                render();
                scroll.scrollLeft = Math.max(0, msIdx(center) * state.w - view / 2);
            }

            /**
             * «100%»: масштаб, при котором отрезки всех показанных сделок (от самого раннего начала
             * до самого позднего конца в пределах шкалы) ровно помещаются в ширину области, и прокрутка к ним.
             */
            function zoomToData() {
                P = period();   // после смены фильтра период «Вся история» мог сдвинуться
                const end = P.start + P.days * DAY;
                let min = Infinity, max = -Infinity;
                DATA.deals.filter(visible).forEach(function (d) {
                    d.segments.forEach(function (s) {
                        min = Math.min(min, Math.max(P.start, ms(s.from)));
                        max = Math.max(max, Math.min(end, ms(s.to) + DAY));
                    });
                });
                if (!isFinite(min) || max <= min) { rescale(null); return; }
                const PAD = 2;   // дней запаса по краям, чтобы метки итога не упирались в край
                const span = (max - min) / DAY + PAD * 2;
                const view = scroll.clientWidth - LEFT;
                state.zoom = Math.min(ZOOM_MAX, view / span);
                if (state.zoom <= fitWidth() + 0.001) state.zoom = null;
                saveZoom();
                render();
                scroll.scrollLeft = Math.max(0, (msIdx(min) - PAD) * state.w);
            }

            /**
             * Треугольник «развернуть / свернуть все» в шапке колонки «Сделка».
             * Вправо — все свёрнуты, тире — раскрыта часть (клик раскрывает все), вниз — раскрыты все (клик сворачивает).
             */
            function toggleAllHtml() {
                const shown = DATA.deals.filter(visible);
                const opened = shown.filter(function (d) { return state.open.has(d.id); }).length;
                const all = shown.length > 0 && opened === shown.length;
                const icon = all ? 'fa-chevron-down' : (opened > 0 ? 'fa-minus' : 'fa-chevron-right');
                return '<button type="button" class="mg-toggle" data-toggle-all="' + (all ? 'collapse' : 'expand') + '" title="' + (all ? 'Свернуть все' : 'Развернуть все') + '">'
                    + '<i class="fa-light ' + icon + '"></i></button>';
            }

            /** Прокрутить шкалу к началу выбранного года */
            function scrollToYear() { scroll.scrollLeft = Math.max(0, msIdx(Y0) * state.w); }

            /** Активная кнопка группы — класс active (btn-active-*); фокус снимается, чтобы не путал подсветку */
            function setActive(selector, attr, value) {
                document.querySelectorAll(selector).forEach(function (b) {
                    b.classList.toggle('active', b.getAttribute(attr) === value);
                });
            }

            function setGroup(group) {
                state.group = group || null;
                render();
            }

            // разворот сделки — по стрелке или по левой ячейке (ссылка открывает сделку)
            inner.addEventListener('click', function (e) {
                if (e.target.closest('a')) return;
                const t = e.target.closest('[data-toggle]');
                if (!t) return;
                const id = +t.getAttribute('data-toggle');
                state.open.has(id) ? state.open.delete(id) : state.open.add(id);
                render();
            });

            function onClick(selector, handler) {
                document.querySelectorAll(selector).forEach(function (b) {
                    b.addEventListener('click', function (e) { b.blur(); handler(b, e); });
                });
            }

            onClick('[data-mg-scale]', function (b) { rescale(SCALES[b.getAttribute('data-mg-scale')]); });
            onClick('#mg-zoom-in', function () { rescale(Math.min(ZOOM_MAX, dayWidth() * ZOOM_STEP)); });
            onClick('#mg-zoom-out', function () { rescale(dayWidth() / ZOOM_STEP); });
            onClick('#mg-zoom-data', function () { zoomToData(); });
            onClick('[data-mg-new]', function (b) { state.onlyNew = b.getAttribute('data-mg-new') === '1'; render(); });
            onClick('[data-mg-group]', function (b) { setGroup(b.getAttribute('data-mg-group')); });
            onClick('[data-mg-range]', function (b) {
                state.range = b.getAttribute('data-mg-range');
                try { localStorage.setItem(STORE_KEY, state.range); } catch (e) {}
                state.zoom = loadZoom(state.range);
                render();
                scrollToYear();
            });
            // развернуть / свернуть все — треугольник в шапке (шапка перерисовывается, поэтому делегирование)
            inner.addEventListener('click', function (e) {
                const t = e.target.closest('[data-toggle-all]');
                if (!t) return;
                if (t.getAttribute('data-toggle-all') === 'expand') DATA.deals.filter(visible).forEach(function (d) { state.open.add(d.id); });
                else state.open.clear();
                render();
            });

            // сортировка: выбор запоминается; раскрытые сделки (state.open) остаются раскрытыми
            const sortSelect = document.getElementById('mg-sort');
            sortSelect.value = state.sort;
            sortSelect.addEventListener('change', function () {
                state.sort = SORTS[sortSelect.value] ? sortSelect.value : 'funnel';
                try { localStorage.setItem(SORT_KEY, state.sort); } catch (e) {}
                render();
            });

            // fit вписан в ширину области — пересчёт при изменении размера окна
            let resizeFrame = null;
            window.addEventListener('resize', function () {
                // и при зуме: «вписать» и пределы −/+ зависят от ширины области
                cancelAnimationFrame(resizeFrame);
                resizeFrame = requestAnimationFrame(function () { render(); });
            });

            // ---- вкладки страницы: ?view=table|gantt, без перезагрузки ----
            const panes = {table: document.getElementById('mv-pane-table'), gantt: document.getElementById('mv-pane-gantt')};
            let rendered = false;

            function showView(view) {
                Object.keys(panes).forEach(function (k) { panes[k].classList.toggle('d-none', k !== view); });
                document.querySelectorAll('[data-mv-view]').forEach(function (a) {
                    a.classList.toggle('active', a.getAttribute('data-mv-view') === view);
                });

                // год и срез сохраняют вкладку
                const input = document.querySelector('[data-mv-view-input]');
                if (input) input.value = view;
                document.querySelectorAll('[data-mv-keep-view]').forEach(function (a) {
                    const u = new URL(a.href);
                    u.searchParams.set('view', view);
                    a.href = u.toString();
                });
                try {
                    const u = new URL(window.location.href);
                    u.searchParams.set('view', view);
                    history.replaceState(null, '', u.toString());
                } catch (e) {}

                // первый показ скрытой вкладки — ширина появилась только сейчас; при первом показе — «100%» (решение владельца 16.11)
                if (view === 'gantt') {
                    if (rendered) render(); else zoomToData();
                    rendered = true;
                }
            }

            document.querySelectorAll('[data-mv-view]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    showView(a.getAttribute('data-mv-view'));
                });
            });

            // плитки групп блока «Новые запросы» — переход на диаграмму с фильтром; повторный клик снимает
            document.querySelectorAll('[data-gantt-group]').forEach(function (t) {
                t.addEventListener('click', function () {
                    const g = t.getAttribute('data-gantt-group');
                    state.group = state.group === g ? null : g;
                    if (state.group) {
                        showView('gantt');
                        window.scrollTo({top: document.getElementById('mv-tabs').getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth'});
                    } else {
                        toolbar();
                    }
                });
            });

            // API для фильтра в попапе (_movement_gantt_filter): данные и установка условия отбора
            window.MovementGantt = {
                data: DATA,
                stages: stages,
                /** fn(deal) → bool или null — без фильтра; перерисовывает диаграмму; fit — затем «100%» (решение владельца 16.11) */
                setFilter: function (fn, fit) {
                    state.filter = fn || null;
                    if (!scroll.offsetParent) { toolbar(); return; }
                    if (fit) zoomToData(); else render();
                },
            };

            toolbar();
            if (!panes.gantt.classList.contains('d-none')) showView('gantt');
        })();
    </script>
@endsection

{{-- фильтр сделок диаграммы в попапе --}}
@include('bitrix.dashboard._movement_gantt_filter')
