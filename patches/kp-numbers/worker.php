<?php
/**
 * Сверка чисел одного КП в одном шаблоне: позиции → итоги варианта → PDF → Excel.
 *
 * php worker.php {id КП} {default|client_discount} [--dump]
 * Печатает одну строку JSON с находками. Один процесс — один рендер: шаблоны PDF
 * объявляют глобальную функцию cost_out(), второй рендер в том же процессе падает.
 *
 * Что считается правильным:
 *  - итоги варианта — пересчёт позиций по формулам ProposalRepository::create_variants_new;
 *  - строка документа — точное значение, округлённое вниз или вверх (не дальше единицы);
 *    сумма показанных строк и ИТОГО сверяются строго, ИТОГО — с хранимыми итогами карточки;
 *  - PDF «По умолчанию» и Excel «По умолчанию» — цены после всех скидок (партнёрские);
 *  - PDF «Со скидкой клиента» — строки либо в ценах заказчика, либо партнёрские, без смешения
 *    (что именно — решает владелец; партнёрские отмечаются одной находкой на вариант);
 *  - Excel «Со скидкой клиента» — цены заказчика, НДС с них же (README v16);
 *  - неактивные (жёлтые) позиции видны, но ни в какой итог не входят;
 *  - рубли выводятся целыми, валюта — с центами.
 */

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalTools\Services\ProposalExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

$pid = (int) ($argv[1] ?? 0);
$template = $argv[2] ?? 'default';
$dump = in_array('--dump', $argv, true);

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$http = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->instance('request', Request::create('/', 'GET', [], ['ui_theme' => 'metronic']));
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
auth()->loginUsingId(1);
// POST без CSRF-токена: проверку токена в этом процессе отключаем
$app->instance(\App\Http\Middleware\VerifyCsrfToken::class, new class {
    public function handle($request, $next) { return $next($request); }
});

$out = ['pid' => $pid, 'template' => $template, 'findings' => [], 'stats' => []];
$F = function (string $area, string $code, string $where, $expected = null, $actual = null, string $note = '') use (&$out) {
    $out['findings'][] = compact('area', 'code', 'where', 'expected', 'actual', 'note');
};
// --seen: записать каждое сверенное число документа (для таблицы «было → стало»)
$seen = in_array('--seen', $argv, true);
$S = function (string $area, string $where, $actual) use (&$out, $seen) {
    if ($seen) $out['seen']["$area|$where"] = $actual;
};

try {
    $p = Proposal::find($pid);
    if (!$p) throw new RuntimeException('нет КП');
    $out['number'] = $p->number;
    $out['iteration'] = $p->iteration;
    $out['group'] = $p->group;
    $out['created'] = (string) $p->created_at;
    $out['currency'] = $p->currency_slug;
    $variants = $p->variants;
    $rate = (float) $p->nds;
    $symbol = (string) ($p->currency->symbol ?? '');

    /* ---------- 1. Модель: пересчёт как в ProposalRepository::create_variants_new ---------- */
    $models = [];
    foreach ($variants as $v) {
        $m = ['items' => [], 'blocks' => []];
        foreach (['platform' => ['proposal_platforms', (float) $v->platform_discount_partner_p],
                  'neuro' => ['proposal_scenarios', (float) $v->neuro_discount_partner_p]] as $code => [$rel, $pp]) {
            $b = ['base' => 0.0, 'client' => 0.0, 'nds' => 0.0, 'final' => 0.0];
            foreach ($v->{$rel} as $it) {
                $cnt = (float) $it->count;
                if ($cnt <= 0) continue;
                $cost = (float) $it->cost;
                $ct = $cost * $cnt;
                $cd = $ct - round($ct / 100 * (int) $it->discount);
                $final = $cd * (1 - $pp / 100);
                $row = ['block' => $code, 'id' => $it->id, 'processed' => (bool) $it->cb_process, 'cost' => $cost,
                    'count' => $cnt, 'list' => $ct, 'client' => $cd, 'final' => $final,
                    'stored_final' => (float) $it->cost_discount * $cnt, 'nds' => (float) $it->nds,
                    'nds_calc' => $it->cb_nds ? $final / 100 * $rate : 0.0,
                    'client_nds' => $it->cb_nds ? $cd / 100 * $rate : 0.0,
                    'label' => $code === 'platform'
                        ? (Str::contains((string) $it->description, 'платформа') ? __('proposal_pdf.proposal_platform') : __('proposal_pdf.proposal_platform_ai_agent'))
                        : null];
                $m['items'][] = $row;
                if ($row['processed']) {
                    $b['base'] += $ct; $b['client'] += $cd; $b['nds'] += $row['nds_calc']; $b['final'] += $final;
                }
            }
            $b['partner'] = $b['client'] - round($b['client'] * $pp / 100);
            $m['blocks'][$code] = $b;
        }
        foreach (['soft' => ['proposal_software', 'proposal_software'], 'work' => ['proposal_works', 'proposal_work']] as $code => [$rel, $ref]) {
            $b = ['base' => 0.0, 'client' => 0.0, 'nds' => 0.0, 'final' => 0.0, 'cust' => 0.0, 'part' => 0.0];
            foreach ($v->{$rel} as $it) {
                $cnt = (float) $it->count;
                if ($cnt <= 0) continue;
                $cost = (float) $it->cost;
                $ct = $cost * $cnt;
                $dc = (float) $it->discount_customer;
                $cust = $dc > 0 ? $ct * ($dc / 100) : 0.0;
                $part = (float) $it->discount - $cust;
                $row = ['block' => $code, 'id' => $it->id, 'processed' => (bool) ($it->{$ref}->cb_process ?? true),
                    'cost' => $cost, 'count' => $cnt, 'list' => $ct, 'client' => $ct - $cust, 'final' => (float) $it->total,
                    'stored_final' => (float) $it->total, 'nds' => (float) $it->nds,
                    'nds_calc' => $it->cb_nds ? (float) $it->total / 100 * $rate : 0.0,
                    'client_nds' => $it->cb_nds ? ($ct - $cust) / 100 * $rate : 0.0,
                    'group' => $code === 'work' ? ($it->proposal_work->group ?? 'Без группы') : null];
                if ($code === 'work') {
                    // итог работы по её процентам
                    $calc = ($ct - $cust) * (1 - ((float) $it->discount_partner) / 100);
                    if (abs($calc - $row['final']) > 0.02) $F('КП', 'работа: итог ≠ цена×кол-во−скидки', "вариант {$v->id} работа {$it->id}", round($calc, 2), $row['final']);
                }
                $m['items'][] = $row;
                if ($row['processed']) {
                    $b['base'] += $ct; $b['client'] += $ct - $cust; $b['nds'] += $row['nds_calc']; $b['final'] += $row['final'];
                    $b['cust'] += $cust; $b['part'] += $part;
                }
            }
            $b['partner'] = $b['final'];
            $m['blocks'][$code] = $b;
        }
        $models[$v->id] = $m;

        /* ---------- 2. Хранимые итоги варианта против пересчёта ---------- */
        $bl = $m['blocks'];
        $chk = [
            'platform_cost_total_base' => $bl['platform']['base'], 'platform_discount_customer' => $bl['platform']['client'],
            'platform_cost_total' => $bl['platform']['partner'], 'platform_nds_cost_total' => $bl['platform']['nds'],
            'neuro_cost_total_base' => $bl['neuro']['base'], 'neuro_discount_customer' => $bl['neuro']['client'],
            'neuro_cost_total' => $bl['neuro']['partner'], 'neuro_nds_cost_total' => $bl['neuro']['nds'],
            'soft_cost_total_base' => $bl['soft']['base'], 'soft_discount_customer' => $bl['soft']['cust'],
            'soft_cost_total' => $bl['soft']['partner'], 'soft_nds_cost_total' => $bl['soft']['nds'],
            'work_cost_total_base' => $bl['work']['base'], 'work_discount_customer' => $bl['work']['cust'],
            'work_cost_total' => $bl['work']['partner'], 'work_nds_cost_total' => $bl['work']['nds'],
        ];
        $nds = $bl['platform']['nds'] + $bl['neuro']['nds'] + $bl['soft']['nds'] + $bl['work']['nds'];
        $chk['nds_cost_total'] = $nds;
        $chk['cost_total'] = $bl['platform']['partner'] + $bl['neuro']['partner'] + $bl['soft']['partner'] + $bl['work']['partner'] + $nds;
        foreach ($chk as $field => $calc) {
            $stored = (float) $v->{$field};
            if (abs($stored - $calc) > 1.0) $F('КП', 'итог варианта ≠ сумме позиций', "вариант {$v->id} {$field}", round($calc, 2), $stored);
        }
        foreach ($m['items'] as $it) {
            if (abs($it['nds'] - $it['nds_calc']) > 1.0) $F('КП', 'НДС позиции ≠ ставка×цена', "вариант {$v->id} {$it['block']} {$it['id']}", round($it['nds_calc'], 2), $it['nds']);
            if ($it['block'] === 'neuro' && abs($it['stored_final'] - $it['final']) > 0.01) {
                $F('КП', 'цена сценария хранится в целых рублях', "вариант {$v->id} сценарий {$it['id']}", round($it['final'], 2), $it['stored_final'], 'cost_discount — целое, строка PDF расходится с итогом');
            } elseif (abs($it['stored_final'] - $it['final']) > 1.0) {
                $F('КП', 'цена позиции после скидок ≠ пересчёту', "вариант {$v->id} {$it['block']} {$it['id']}", round($it['final'], 2), $it['stored_final']);
            }
        }
        // доплаты: хранимые против пересчёта от текущих итогов (ProposalVariantExtraPayService::create)
        if ($v->extra_pays->isNotEmpty()) {
            $bs = $v->neuro_cost_total + $v->platform_cost_total + $v->neuro_nds_cost_total + $v->platform_nds_cost_total;
            $bw = $v->work_cost_total + $v->work_nds_cost_total;
            foreach ($v->extra_pays as $x) {
                $pc = (float) $x->percent;
                $val = 0;
                if ($x->block === 'software') { $val = round($bs / 100 * $pc, 2); $bs += $val; }
                if ($x->block === 'work') { $val = round($bw / 100 * $pc, 2); $bw += $val; }
                if ($x->block === 'all') { $a = round($bs / 100 * $pc, 2); $c = round($bw / 100 * $pc, 2); $bs += $a; $bw += $c; $val = $a + $c; }
                if (abs($val - (float) $x->value) > 0.5 || abs(($bs + $bw) - (float) $x->total) > 0.5) {
                    $F('КП', 'доплата не пересчитана от текущих итогов', "вариант {$v->id} доплата {$x->id}", round($bs + $bw, 2), (float) $x->total);
                }
            }
            if ((float) $v->soft_cost_total > 0) $F('КП', 'доплаты не учитывают блок ПО', "вариант {$v->id}", round((float) $v->soft_cost_total), null);
        }
    }

    /* ---------- 3. PDF ---------- */
    $vdata = [];
    foreach ($variants as $v) $vdata[$v->id] = ['name' => '', 'cameras' => '10', 'period_po' => '', 'period_pk' => ''];
    $req = Request::create(route('proposal.report', [$p, $p->iteration], false), 'POST', [
        'active' => $variants->pluck('id')->all(), 'language' => 'ru', 'template' => $template,
        'show_unprocessed' => 1, 'variant' => $vdata, 'form' => ['contact' => ''],
    ], ['ui_theme' => 'metronic']);
    $res = $http->handle($req);
    if ($res->getStatusCode() !== 200) throw new RuntimeException('PDF: HTTP ' . $res->getStatusCode());
    $html = $res->getContent();

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xp = new DOMXPath($dom);

    $norm = fn(string $s) => trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $s)));
    $txt = fn(?DOMNode $n) => $n ? $norm($n->textContent) : '';
    // текст узла без вложенных div с любым из классов $skip (строка скидки, зачёркнутая цена)
    $textSkip = function (DOMNode $n, array $skip) use ($norm) {
        $walk = function (DOMNode $n) use (&$walk, $skip): string {
            if ($n instanceof DOMText) return $n->data;
            if ($n instanceof DOMElement && $n->nodeName === 'div'
                && array_intersect($skip, preg_split('/\s+/', $n->getAttribute('class')))) return '';
            $s = '';
            foreach ($n->childNodes as $c) $s .= $walk($c);
            return $s;
        };
        return $norm($walk($n));
    };
    $amt = function (?string $s): ?float {
        if ($s === null) return null;
        $s = trim(str_replace("\xc2\xa0", ' ', $s));
        if ($s === '') return null;
        if ($s === '-') return 0.0;
        // «– 12 000 ₽» (длинное тире) — строка скидки, это не минус
        if (str_starts_with($s, "\xe2\x80\x93")) $s = trim(substr($s, 3));
        // настоящий минус: cost_normalize печатает «- 175 711 422», в валюте «₹ -46,080.00»
        $neg = (bool) preg_match('/(^|\s)-\s?\d/', $s);
        // рубли — «1 234 567», валюта — «1,234,567.89»: пробел и запятая разделяют разряды, точка — центы
        if (!preg_match('/\d[\d ,]*(\.\d+)?/', $s, $mm)) return null;
        return ($neg ? -1 : 1) * (float) str_replace([' ', ','], '', $mm[0]);
    };
    $tds = fn(DOMNode $tr) => array_values(array_filter(iterator_to_array($tr->childNodes), fn($c) => $c instanceof DOMElement && $c->nodeName === 'td'));
    // $tol > 0 — строка: допустимо округление вниз или вверх (сумма строк проверяется отдельно, строго)
    // рубли выводятся целыми, валюта — с центами
    $prec = $p->isForeignCurrency ? 2 : 0;
    $cmp = function (string $area, string $where, $exp, $act, string $code = 'число не совпадает', string $note = '', float $tol = 0.0) use ($F, $S, $prec) {
        $S($area, $where, $act);
        if ($act === null && $exp === null) return;
        if ($act !== null && ($tol > 0 ? abs($exp - $act) < $tol : abs(round($exp, $prec) - $act) <= 0.001)) return;
        $rounding = $act !== null && abs($exp - $act) < 1.0;
        $F($area, $rounding ? 'округление на 1' : $code, $where, round($exp, 2), $act, $note);
    };
    $active = fn(array $m, string $code) => count(array_filter($m['items'], fn($i) => $i['block'] === $code && $i['processed']));
    $csum = fn(array $m, string $code, string $field = 'client') => array_sum(array_map(fn($i) => $i['block'] === $code && $i['processed'] ? $i[$field] : 0, $m['items']));

    $mainTables = iterator_to_array($xp->query("//table[@keep='main_table']"));
    $prepays = iterator_to_array($xp->query("//span[@keep='prepay']"));
    $finals = iterator_to_array($xp->query("//span[@keep='final_payment']"));
    $area = $template === 'default' ? 'PDF' : 'PDF клиент';
    $client = $template !== 'default';

    foreach ($variants->values() as $vi => $v) {
        $m = $models[$v->id];
        $bl = $m['blocks'];
        $W = "вариант " . ($vi + 1);

        /* 3.1 сводная таблица */
        $rows = [];
        foreach ($xp->query("//table[@keep='{$v->id}__proposal']//tr") as $tr) {
            $cls = ' ' . $tr->getAttribute('class') . ' ';
            if (str_contains($cls, ' subcaption ') || str_contains($cls, ' caption ') || str_contains($cls, ' clear ')) continue;
            $c = $tds($tr);
            if (count($c) < 5) continue;
            $rows[] = ['label' => $txt($c[0]), 'vals' => [$amt($txt($c[1])), $amt($txt($c[2])), $amt($txt($c[3])), $amt($txt($c[4]))]];
        }
        // «для клиента» — из позиций (хранимая цена клиента у нейросервисов бывала устаревшей),
        // остальные колонки — хранимые итоги варианта; строки-разбивки сверяем с допуском округления
        $exp = [];
        $tolRow = [];
        $has = ['soft' => $active($m, 'soft') > 0, 'platform' => $active($m, 'platform') > 0, 'neuro' => $active($m, 'neuro') > 0, 'work' => $active($m, 'work') > 0];
        $cl = ['soft' => $csum($m, 'soft'), 'platform' => $csum($m, 'platform'), 'neuro' => $csum($m, 'neuro'), 'work' => $csum($m, 'work')];
        if ($has['soft']) {
            $exp[] = [__('proposal_pdf.proposal_soft'), [$cl['soft'], $v->soft_cost_total, $v->soft_nds_cost_total, $v->soft_cost_total + $v->soft_nds_cost_total]];
        }
        if ($has['platform'] || $has['neuro']) {
            $exp[] = [__('proposal_pdf.proposal_software_group'), [$cl['platform'] + $cl['neuro'], $v->platform_cost_total + $v->neuro_cost_total,
                $v->platform_nds_cost_total + $v->neuro_nds_cost_total, $v->platform_cost_total + $v->platform_nds_cost_total + $v->neuro_cost_total + $v->neuro_nds_cost_total]];
        }
        if ($has['platform']) {
            $plats = array_values(array_filter($m['items'], fn($i) => $i['block'] === 'platform' && $i['processed']));
            $exp[] = [count($plats) > 1 ? __('proposal_pdf.proposal_platform_group_title') : __('proposal_pdf.proposal_platform'),
                [$cl['platform'], $v->platform_cost_total, $v->platform_nds_cost_total, $v->platform_cost_total + $v->platform_nds_cost_total]];
            if (count($plats) > 1) foreach ($plats as $i) {
                $tolRow[count($exp)] = true;
                $exp[] = [$i['label'], [$i['client'], $i['final'], $i['nds_calc'], $i['final'] + $i['nds_calc']]];
            }
        }
        if ($has['neuro']) {
            $exp[] = [__('proposal_pdf.proposal_neuro'), [$cl['neuro'], $v->neuro_cost_total, $v->neuro_nds_cost_total, $v->neuro_cost_total + $v->neuro_nds_cost_total]];
        }
        if ($has['work']) {
            $exp[] = [__('proposal_pdf.proposal_work'), [$cl['work'], $v->work_cost_total, $v->work_nds_cost_total, $v->work_cost_total + $v->work_nds_cost_total]];
            $groups = [];
            foreach ($m['items'] as $i) {
                if ($i['block'] !== 'work' || !$i['processed']) continue;
                $groups[$i['group']] ??= [0.0, 0.0, 0.0];
                $groups[$i['group']][0] += $i['client'];
                $groups[$i['group']][1] += $i['final'];
                $groups[$i['group']][2] += $i['nds_calc'];
            }
            if (count($groups) > 1) foreach ($groups as $g => [$gc, $gt, $gn]) {
                $tolRow[count($exp)] = true;
                $exp[] = [Lang::has("proposal_pdf.$g") ? __("proposal_pdf.$g") : $g, [$gc, $gt, $gn, $gt + $gn]];
            }
        }
        $client_total = array_sum(array_map(fn($code) => $has[$code] ? $cl[$code] : 0, array_keys($has)));
        $exp[] = [__('proposal_pdf.proposal_footer_total'), [$client_total, $v->cost_total - $v->nds_cost_total, $v->nds_cost_total, $v->cost_total]];

        $cols = ['для клиента', 'для партнёра', 'НДС', 'итоговая'];
        $ai = 0;
        foreach ($exp as $ei => [$label, $vals]) {
            // строки, которых быть не должно (например, неактивная платформа в разбивке)
            $skipped = 0;
            while ($ai < count($rows) && mb_strtoupper($rows[$ai]['label']) !== mb_strtoupper($label)) {
                $F($area, 'лишняя строка в сводной', "$W «{$rows[$ai]['label']}»", null, implode(' / ', array_map(fn($x) => $x ?? '·', $rows[$ai]['vals'])));
                $ai++; $skipped++;
            }
            if ($ai >= count($rows)) {
                // строки нет: откатываемся, чтобы не потерять остальные
                $ai -= $skipped;
                array_splice($out['findings'], count($out['findings']) - $skipped, $skipped);
                $F($area, 'нет строки в сводной', "$W «{$label}»", implode(' / ', array_map(fn($x) => round($x), $vals)), null);
                continue;
            }
            // цена клиента по блокам округляется отдельно — в сумме блоков допустима единица
            foreach ($vals as $k => $e) $cmp($area, "$W сводная «{$label}» — {$cols[$k]}", (float) $e, $rows[$ai]['vals'][$k], 'число не совпадает', '', (isset($tolRow[$ei]) || $k === 0) ? 1.0 : 0.0);
            $ai++;
        }
        for (; $ai < count($rows); $ai++) $F($area, 'лишняя строка в сводной', "$W «{$rows[$ai]['label']}»", null, implode(' / ', array_map(fn($x) => $x ?? '·', $rows[$ai]['vals'])));

        // сходится ли показанное: ИТОГО = сумма строк верхнего уровня; «итоговая» = партнёр + НДС
        $topLabels = [mb_strtoupper(__('proposal_pdf.proposal_soft')), mb_strtoupper(__('proposal_pdf.proposal_software_group')), mb_strtoupper(__('proposal_pdf.proposal_work'))];
        $top = array_filter($rows, fn($r) => in_array(mb_strtoupper($r['label']), $topLabels, true));
        $itogo = end($rows);
        if ($itogo && mb_strtoupper($itogo['label']) === mb_strtoupper(__('proposal_pdf.proposal_footer_total')) && $top) {
            foreach ([0, 1, 2, 3] as $k) {
                $sum = array_sum(array_map(fn($r) => $r['vals'][$k] ?? 0, $top));
                if (abs($sum - ($itogo['vals'][$k] ?? 0)) > 0.001) {
                    $F($area, 'ИТОГО сводной ≠ сумме строк', "$W сводная ИТОГО — {$cols[$k]}", $sum, $itogo['vals'][$k], 'по показанным числам');
                }
            }
        }
        foreach ($rows as $r) {
            if (isset($r['vals'][1], $r['vals'][2], $r['vals'][3]) && abs($r['vals'][1] + $r['vals'][2] - $r['vals'][3]) > 1.001) {
                $F($area, 'итоговая ≠ партнёр + НДС', "$W сводная «{$r['label']}»", $r['vals'][1] + $r['vals'][2], $r['vals'][3], 'по показанным числам');
            }
        }

        /* 3.2 таблица приложения */
        $table = $mainTables[$vi] ?? null;
        if (!$table) { $F($area, 'нет таблицы приложения', $W); continue; }
        $sectionMap = [__('proposal_pdf.tr_platform_title') => 'soft', __('proposal_pdf.proposal_platform') => 'platform',
            __('proposal_pdf.tr_neuroservices_title') => 'neuro', __('proposal_pdf.tr_works_title') => 'work'];
        $section = null; $shown = []; $itogoRow = null;
        foreach ($xp->query('.//tr', $table) as $tr) {
            $cls = ' ' . $tr->getAttribute('class') . ' ';
            $c = $tds($tr);
            if (str_contains($cls, ' caption ')) continue;
            if (str_contains($cls, ' subcaption ')) { $section = $sectionMap[$txt($c[1] ?? null)] ?? '?'; continue; }
            if ($c && $c[0]->getAttribute('colspan') === '4') { $itogoRow = $c; continue; }
            if (count($c) < 6) continue;
            $d = $xp->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' text-danger ')]", $c[2])->item(0);
            $st = $xp->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' text-secondary ')]", $c[2])->item(0);
            $shown[] = ['section' => $section, 'warn' => str_contains($cls, ' bg-light-warning '),
                'price' => $amt($textSkip($c[2], ['text-danger', 'text-secondary'])),
                'disc' => $d ? $amt($txt($d)) : null, 'strike' => $st ? $amt($txt($st)) : null,
                'count_txt' => $txt($c[3]), 'count' => $amt($txt($c[3])), 'total_txt' => $txt($c[4]), 'total' => $amt($txt($c[4]))];
        }
        // ожидаемые строки в порядке шаблона: ПО, платформа, нейросервисы, работы
        $order = ['soft' => 0, 'platform' => 1, 'neuro' => 2, 'work' => 3];
        $expItems = $m['items'];
        usort($expItems, fn($a, $b) => $order[$a['block']] <=> $order[$b['block']]);
        if (count($expItems) !== count($shown)) $F($area, 'число строк приложения не совпадает', $W, count($expItems), count($shown));
        $names = ['soft' => 'ПО', 'platform' => 'платформа', 'neuro' => 'нейросервис', 'work' => 'работа'];
        $sumProcessed = 0.0; $sumAll = 0.0; $kinds = [];
        foreach ($expItems as $k => $e) {
            $s = $shown[$k] ?? null;
            if (!$s) break;
            $w = "$W приложение: {$names[$e['block']]} №" . ($k + 1) . ($e['processed'] ? '' : ' (неактивная)');
            if ($s['section'] !== $e['block']) $F($area, 'строка не в своём разделе', $w, $e['block'], $s['section']);
            if ($s['warn'] === $e['processed']) $F($area, 'подсветка неактивной не та', $w, $e['processed'] ? 'обычная' : 'жёлтая', $s['warn'] ? 'жёлтая' : 'обычная');
            if (abs($e['count'] - round($e['count'])) > 0.001 && !str_contains($s['count_txt'], ',') && !str_contains($s['count_txt'], '.')) $F($area, 'дробное количество округлено', $w, $e['count'], $s['count_txt']);
            elseif (abs((float) str_replace(',', '.', preg_replace('/[^\d,.]/', '', $s['count_txt'])) - $e['count']) > 0.001) $F($area, 'количество не совпадает', $w, $e['count'], $s['count_txt']);
            if (substr_count($s['total_txt'], $symbol) > 1) $F($area, 'сумма напечатана дважды', "$w — итого", null, $s['total_txt']);
            $unit_final = $e['final'] / $e['count'];
            $unit_client = $e['client'] / $e['count'];
            if (!$client) {
                $cmp($area, "$w — цена", $e['cost'], $s['price']);
                $disc = $e['cost'] - $unit_final;
                if (round($disc) > 0 || $s['disc'] !== null) $cmp($area, "$w — скидка на ед.", $disc, $s['disc'] ?? 0.0, 'число не совпадает', '', 1.0);
                $cmp($area, "$w — итого", $e['final'], $s['total'], 'число не совпадает', '', 1.0);
            } else {
                $S($area, "$w — цена", $s['price']);
                $S($area, "$w — итого", $s['total']);
                // строка — в ценах заказчика или после всех скидок; какие именно, решает владелец, смешения быть не должно
                $kind = function ($shownVal, $clientVal, $finalVal, $tol) {
                    if ($shownVal === null) return 'нет';
                    if (abs($shownVal - $clientVal) < $tol) return 'client';
                    if (abs($shownVal - $finalVal) < $tol) return 'final';
                    return 'none';
                };
                $kp = $kind($s['price'], $unit_client, $unit_final, 1.001);
                $kt = $kind($s['total'], $e['client'], $e['final'], 1.0);
                if ($kp === 'none' || $kp === 'нет') $F($area, 'цена в приложении — ни клиента, ни партнёра', "$w — цена", round($unit_client, 2), $s['price'], 'после всех скидок ' . round($unit_final, 2));
                if ($kt === 'none' || $kt === 'нет') $F($area, 'итог строки в приложении — ни клиента, ни партнёра', "$w — итого", round($e['client'], 2), $s['total'], 'после всех скидок ' . round($e['final'], 2));
                // по строкам с партнёрской скидкой видно, в каких ценах приложение
                if (abs($e['client'] - $e['final']) >= 1.0 && in_array($kt, ['client', 'final'], true)) $kinds[$kt] = ($kinds[$kt] ?? 0) + 1;
                if ($s['strike'] !== null && abs($s['strike'] - round($e['cost'])) > 1.001) $F($area, 'зачёркнутая цена ≠ прайсу', "$w — цена", $e['cost'], $s['strike']);
            }
            if (!$s['warn']) $sumProcessed += (float) $s['total'];
            $sumAll += (float) $s['total'];
        }
        if (!empty($kinds['client']) && !empty($kinds['final'])) $F($area, 'в приложении смешаны цены клиента и партнёра', $W, null, json_encode($kinds));
        elseif (!empty($kinds['final'])) $F($area, 'приложение в ценах партнёра (после всех скидок)', $W, null, $kinds['final'] . ' строк с партнёрской скидкой', 'решает владелец');
        // «ПО: - (без НДС)» — прочерк вместо нуля
        $amtLine = fn(?DOMNode $n) => $n === null ? null : (preg_match('/\d/', $txt($n)) ? $amt($txt($n)) : (str_contains($txt($n), '-') ? 0.0 : null));
        // строка ИТОГО приложения
        if ($itogoRow) {
            $cell = $itogoRow[1] ?? null;
            $main = $cell ? $xp->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' fw-bold ')]", $cell)->item(0) : null;
            $itogoVal = $amt($txt($main ?? $cell));
            $S($area, "$W приложение ИТОГО", $itogoVal);
            $S($area, "$W приложение", $itogoVal);
            $partnerMode = !$client || !empty($kinds['final']) || empty($kinds['client']);
            $expTotal = $partnerMode ? $v->cost_total - $v->nds_cost_total : array_sum(array_map(fn($e) => $e['processed'] ? $e['client'] : 0, $m['items']));
            $cmp($area, "$W приложение ИТОГО (без НДС)", $expTotal, $itogoVal, 'ИТОГО приложения ≠ итогу карточки');
            if (abs($sumProcessed - (float) $itogoVal) > 0.001) $F($area, 'ИТОГО приложения ≠ сумме строк', "$W приложение", $sumProcessed, $itogoVal, 'по показанным числам, жёлтые не считаем');
            if (abs($sumAll - $sumProcessed) > 0.001 && abs($sumAll - (float) $itogoVal) < 0.001) $F($area, 'в ИТОГО попали неактивные (жёлтые) строки', "$W приложение", $sumProcessed, $itogoVal);
            if ($v->nds_cost_total > 0) {
                $nd = $xp->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' text-nowrap ')]", $cell)->item(0);
                if (!$nd) $F($area, 'в ИТОГО приложения нет НДС', "$W приложение", round((float) $v->nds_cost_total), null);
                elseif ($partnerMode) $cmp($area, "$W приложение ИТОГО — НДС", (float) $v->nds_cost_total, $amt($txt($nd)));
            }
            if (!$client) {
                $lines = $xp->query('.//div', $itogoRow[2]);
                $cmp($area, "$W приложение «ПО: … (без НДС)»", $v->soft_cost_total + $v->platform_cost_total + $v->neuro_cost_total, $amtLine($lines->item(0)));
                $cmp($area, "$W приложение «УСЛУГИ: … (без НДС)»", (float) $v->work_cost_total, $amtLine($lines->item(1)));
            }
        } else {
            $F($area, 'нет строки ИТОГО в приложении', $W);
        }

        /* 3.3 условия оплаты */
        $last = $v->extra_pays->last();
        $expPre = $last ? (float) $last->work_end : $v->work_cost_total + $v->work_nds_cost_total;
        $expFin = $last ? (float) $last->software_end + ($v->soft_cost_total + $v->soft_nds_cost_total)
            : (float) $v->platform_cost_total + $v->soft_cost_total + $v->neuro_cost_total + $v->platform_nds_cost_total + $v->neuro_nds_cost_total + $v->soft_nds_cost_total;
        $cmp($area, "$W условия оплаты — предоплата", $expPre, isset($prepays[$vi]) ? $amt($txt($prepays[$vi])) : null);
        $cmp($area, "$W условия оплаты — окончательный расчёт", $expFin, isset($finals[$vi]) ? $amt($txt($finals[$vi])) : null);
        $expFinNds = (float) $v->platform_nds_cost_total + $v->neuro_nds_cost_total + $v->soft_nds_cost_total;
        $finText = isset($finals[$vi]) ? $txt($finals[$vi]->parentNode) : '';
        $shownNds = preg_match('/НДС:\s*([^)]*)\)/u', $finText, $mm) ? $amt($mm[1]) : 0.0;
        if ($expFinNds > 0 || $shownNds > 0) $cmp($area, "$W условия оплаты — НДС в окончательном расчёте", $expFinNds, $shownNds);
        $preText = isset($prepays[$vi]) ? $txt($prepays[$vi]->parentNode) : '';
        $shownPreNds = preg_match('/НДС:\s*([^)]*)\)/u', $preText, $mm) ? $amt($mm[1]) : 0.0;
        if ($v->work_nds_cost_total > 0 || $shownPreNds > 0) $cmp($area, "$W условия оплаты — НДС в предоплате", (float) $v->work_nds_cost_total, $shownPreNds);
        if ($dump) $out['dump'][$v->id] = ['summary' => $rows, 'appendix' => $shown, 'itogo' => $itogoRow ? $txt($itogoRow[1]) : null, 'pre' => $preText, 'fin' => $finText];
    }

    /* ---------- 4. Excel ---------- */
    $book = ProposalExcelService::build($p, $variants->pluck('id')->all(), $template, true);
    $xarea = $client ? 'Excel клиент' : 'Excel';
    $blockOf = ['ПЛАТФОРМА' => 'platform', 'ПО' => 'soft', 'НЕЙРОСЕРВИСЫ' => 'neuro', 'РАБОТЫ' => 'work'];
    foreach ($variants->values() as $vi => $v) {
        $m = $models[$v->id];
        $W = "вариант " . ($vi + 1);
        $sh = $book->getSheet($vi);
        $hi = $sh->getHighestRow();
        $block = null; $xrows = []; $xtotal = null; $head = [];
        for ($r = 1; $r <= $hi; $r++) {
            $a = $sh->getCell("A$r")->getValue();
            $b = $sh->getCell("B$r")->getValue();
            if (is_string($a) && isset($blockOf[$a])) { $block = $blockOf[$a]; continue; }
            if ($b === 'Наименование') { $head = []; foreach (range('A', 'K') as $L) $head[(string) $sh->getCell("$L$r")->getValue()] = $L; continue; }
            if ($a === 'ИТОГО') { $xtotal = $r; continue; }
            if (is_numeric($a) && $block) {
                $row = ['block' => $block, 'r' => $r, 'fill' => $sh->getStyle("A$r")->getFill()->getStartColor()->getRGB()];
                foreach ($head as $title => $L) if ($title !== '') $row[$title] = $sh->getCell("$L$r")->getValue();
                $xrows[] = $row;
            }
        }
        $order = ['platform' => 0, 'soft' => 1, 'neuro' => 2, 'work' => 3];
        $expItems = $m['items'];
        usort($expItems, fn($a, $b) => $order[$a['block']] <=> $order[$b['block']]);
        if (count($expItems) !== count($xrows)) $F($xarea, 'число строк не совпадает', $W, count($expItems), count($xrows));
        // $tol: строка — допустимо округление вниз или вверх; суммы строк и ИТОГО сверяются строго
        $xc = function (string $where, $exp, $act, float $tol = 0.011) use ($F, $S, $xarea) {
            $act = $act === null || $act === '' ? 0.0 : (float) $act;
            $S($xarea, $where, $act);
            if (abs($exp - $act) >= $tol) $F($xarea, abs($exp - $act) < 1.0 ? 'копейки / округление' : 'число не совпадает', $where, round($exp, 2), round($act, 2));
        };
        $num = fn($x) => $x === null || $x === '' ? 0.0 : (float) $x;
        $names = ['soft' => 'ПО', 'platform' => 'платформа', 'neuro' => 'нейросервис', 'work' => 'работа'];
        $xsum = ['list' => 0.0, 'total' => 0.0, 'nds' => 0.0, 'with' => 0.0, 'cust' => 0.0, 'part' => 0.0];
        foreach ($expItems as $k => $e) {
            $x = $xrows[$k] ?? null;
            if (!$x) break;
            $w = "$W строка {$x['r']}: {$names[$e['block']]}" . ($e['processed'] ? '' : ' (неактивная)');
            $S($xarea, "$w — итого с НДС", $num($x['Итого с НДС'] ?? null));
            if ($x['block'] !== $e['block']) $F($xarea, 'строка не в своём блоке', $w, $e['block'], $x['block']);
            if (($x['fill'] === 'FFF8DD') === $e['processed']) $F($xarea, 'подсветка неактивной не та', $w, $e['processed'] ? 'обычная' : 'жёлтая', $x['fill']);
            $xc("$w — кол-во", $e['count'], $x['Кол-во'] ?? null);
            if (!$client) {
                $xc("$w — прайс", $e['cost'], $x['Прайс'] ?? null);
                $xc("$w — скидка заказчику", ($e['list'] - $e['client']) / $e['count'], $x['Скидка заказчику'] ?? null, 1.0);
                $xc("$w — скидка партнёру", ($e['client'] - $e['final']) / $e['count'], $x['Скидка партнёру'] ?? null, 1.0);
                $xc("$w — цена итог", $e['final'] / $e['count'], $x['Цена итог'] ?? null, 1.0);
                $xc("$w — итого", $e['final'], $x['Итого'] ?? null, 1.0);
                $xc("$w — НДС", $e['nds_calc'], $x['НДС'] ?? null, 1.0);
                // внутри строки — строго: цена итог × кол-во = итого, итого + НДС = итого с НДС
                $xc("$w — цена итог × кол-во", $num($x['Итого'] ?? null), $num($x['Цена итог'] ?? null) * $e['count']);
                $xc("$w — итого + НДС", $num($x['Итого'] ?? null) + $num($x['НДС'] ?? null), $x['Итого с НДС'] ?? null);
                $xc("$w — прайс − скидки = цена итог", $num($x['Прайс'] ?? null) - $num($x['Скидка заказчику'] ?? null) - $num($x['Скидка партнёру'] ?? null), $x['Цена итог'] ?? null, 0.011 + 1 / $e['count']);
                if ($e['processed']) {
                    $xsum['list'] += $num($x['Прайс'] ?? null) * $e['count']; $xsum['total'] += $num($x['Итого'] ?? null);
                    $xsum['nds'] += $num($x['НДС'] ?? null); $xsum['with'] += $num($x['Итого с НДС'] ?? null);
                    $xsum['cust'] += $num($x['Скидка заказчику'] ?? null) * $e['count']; $xsum['part'] += $num($x['Скидка партнёру'] ?? null) * $e['count'];
                }
            } else {
                $xc("$w — цена", $e['client'] / $e['count'], $x['Цена'] ?? null, 1.0);
                $xc("$w — итого", $e['client'], $x['Итого'] ?? null, 1.0);
                // НДС клиентского файла — с цены заказчика
                $xc("$w — НДС", $e['client_nds'], $x['НДС'] ?? null, 1.0);
                $xc("$w — цена × кол-во", $num($x['Итого'] ?? null), $num($x['Цена'] ?? null) * $e['count']);
                $xc("$w — итого + НДС", $num($x['Итого'] ?? null) + $num($x['НДС'] ?? null), $x['Итого с НДС'] ?? null);
                if ($e['processed']) {
                    $xsum['total'] += $num($x['Итого'] ?? null); $xsum['nds'] += $num($x['НДС'] ?? null); $xsum['with'] += $num($x['Итого с НДС'] ?? null);
                }
            }
        }
        if (!$xtotal) { $F($xarea, 'нет строки ИТОГО', $W); continue; }
        $g = fn($L) => (float) $sh->getCell("$L$xtotal")->getValue();
        $kpTot = function ($where, $exp, $act, float $tol = 0.011) use ($F, $S, $xarea, $W) {
            $S($xarea, "$W $where", $act);
            if (abs($exp - $act) >= $tol) $F($xarea, abs($exp - $act) <= 1.0 ? 'ИТОГО расходится с КП на копейки' : 'ИТОГО не совпадает с КП', "$W $where", round($exp, 2), round($act, 2));
        };
        $sumTot = function ($where, $exp, $act) use ($F, $xarea, $W) {
            if (abs($exp - $act) > 0.011) $F($xarea, 'ИТОГО ≠ сумме строк', "$W $where", round($exp, 2), round($act, 2));
        };
        if (!$client) {
            $kpTot('ИТОГО прайс', (float) $v->platform_cost_total_base + $v->neuro_cost_total_base + $v->soft_cost_total_base + $v->work_cost_total_base, $g('C'));
            $kpTot('ИТОГО без НДС', (float) ($v->cost_total - $v->nds_cost_total), $g('H'));
            $kpTot('ИТОГО НДС', (float) $v->nds_cost_total, $g('I'));
            $kpTot('ИТОГО с НДС', (float) $v->cost_total, $g('J'));
            // прайс за единицу бывает дробным (валюта), а итог прайса — в целых
            if (abs($xsum['list'] - $g('C')) >= 0.011 + 0.5 * count($expItems)) $F($xarea, 'ИТОГО ≠ сумме строк', "$W ИТОГО прайс", round($xsum['list'], 2), $g('C'));
            $sumTot('ИТОГО скидка заказчику', $xsum['cust'], $g('D'));
            $sumTot('ИТОГО скидка партнёру', $xsum['part'], $g('E'));
            $sumTot('ИТОГО без НДС', $xsum['total'], $g('H'));
            $sumTot('ИТОГО НДС', $xsum['nds'], $g('I'));
            $sumTot('ИТОГО с НДС', $xsum['with'], $g('J'));
        } else {
            $kpTot('ИТОГО (цена клиента)', array_sum(array_map(fn($e) => $e['processed'] ? $e['client'] : 0, $m['items'])), $g('E'), 2.0);
            $kpTot('ИТОГО НДС (с цены клиента)', array_sum(array_map(fn($e) => $e['processed'] ? $e['client_nds'] : 0, $m['items'])), $g('F'), 2.0);
            $sumTot('ИТОГО (цена клиента)', $xsum['total'], $g('E'));
            $sumTot('ИТОГО НДС', $xsum['nds'], $g('F'));
            $sumTot('ИТОГО с НДС', $xsum['with'], $g('G'));
        }
    }
    $out['stats']['variants'] = $variants->count();
} catch (\Throwable $e) {
    $out['error'] = get_class($e) . ': ' . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine();
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
