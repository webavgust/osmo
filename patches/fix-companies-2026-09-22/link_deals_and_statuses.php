<?php
/*
 * Связи КП ↔ сделки Битрикса и статусы КП по стадиям сделок (задача владельца 23.09.2026).
 *
 * Связь ставится, только если совпало всё сразу:
 *  - сумма любого варианта любой редакции КП = сумма сделки (±1, как есть или ±НДС 20%), сумма > 0;
 *  - сделка того же партнёра: её компания Б24 сопоставлена с партнёром КП (partner_crm_companies),
 *    а для прямых продаж OSMOVIEW — заказчик сделки или её компания = компания КП (по названию);
 *  - даты рядом: создание сделки не дальше 45 дней от отправки/создания какой-либо редакции КП;
 *  - кандидат единственный с обеих сторон; ни КП, ни сделка ещё ни с чем не связаны («одна сделка — одно КП»).
 *
 * Статус КП по сделкам (все связи, старые и новые):
 *  - выигрышные стадии → «Выиграно»; Suspended → «Проиграно» (Заморожено); Canceled → «Проиграно» (Отменено);
 *  - несколько сделок: хоть одна выиграна → «Выиграно»; все проиграны → «Проиграно»; иначе не трогаем;
 *  - «Выиграно», подтверждённое спецификацией или договором, в «Проиграно» не переводится — только в отчёт.
 *
 *   php link_deals_and_statuses.php            — расчёт и пробный прогон с откатом
 *   php link_deals_and_statuses.php --apply    — выполнить
 */

foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $name => $stream) {
    if (!defined($name)) define($name, fopen($stream, $name === 'STDIN' ? 'r' : 'w'));
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use App\Modules\Pub\Proposal\Models\ProposalLostReason;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv ?? [], true);

const OSMOVIEW = 8;
const DAYS = 45;
const WON_STAGES = ['Acceptance tests', 'Closing documents', 'Completed', 'Contracting', 'Execution (POST-PAYMENT)', 'Execution (PRE-PAYMENT)'];
const LOST_STAGES = ['Suspended' => 'frozen', 'Canceled' => 'canceled'];

$bx = DB::connection('bitrix');
$UF = 'uf_crm_1717755645';
$norm = function (?string $s): string {
    $s = mb_strtolower(str_replace('ё', 'е', (string) $s));
    $s = preg_replace('~\b(ооо|ао|пао|зао|оао|гк|ип|llc|ltd|inc)\b~u', ' ', $s);
    return preg_replace('~[^\p{L}\p{N}]+~u', '', $s);
};

/*** 1. Кандидаты в связи ***/

$deals = $bx->table('crm_deal as d')->leftJoin('crm_deal_uf as u', 'u.deal_id', '=', 'd.id')
    ->get(['d.id', 'd.title', 'd.company_id', 'd.company_name', 'd.stage_name', 'd.opportunity', 'd.currency_id', 'd.date_create', "u.$UF as customer"])
    ->keyBy('id');
$linkedDeals = DB::table('proposal_crm_deals')->pluck('crm_deal_id')->flip();
$linkedGroups = DB::table('proposal_crm_deals')->pluck('proposal_group')->flip();
$crmOwner = DB::table('partner_crm_companies')->pluck('partner_id', 'crm_company_id');   // компания Б24 → партнёр
$companyName = DB::table('companies')->pluck('name', 'id');

$rows = DB::table('proposals as p')->leftJoin('proposal_variants as v', 'v.proposal_id', '=', 'p.id')
    ->get(['p.group', 'p.number', 'p.partner_id', 'p.company_id', 'p.sended_at', 'p.created_at', 'v.cost_total']);
$groups = $rows->groupBy('group');

$candidates = [];   // group => [deal ids]
foreach ($groups as $group => $list) {
    if (isset($linkedGroups[$group])) continue;
    $first = $list->first();
    $costs = $list->pluck('cost_total')->filter(fn($c) => (float) $c > 0)->map(fn($c) => (float) $c)->unique();
    $dates = $list->flatMap(fn($r) => [$r->sended_at, $r->created_at])->filter()->map(fn($d) => Carbon::parse($d));
    if ($costs->isEmpty() || $dates->isEmpty()) continue;

    foreach ($deals as $deal) {
        if (isset($linkedDeals[$deal->id])) continue;
        $o = (float) $deal->opportunity;
        if ($o <= 0) continue;
        if (!$costs->contains(fn($c) => abs($o - $c) <= 1 || abs($o - $c / 1.2) <= 1 || abs($o - $c * 1.2) <= 1)) continue;

        // тот же партнёр
        $owner = $crmOwner[$deal->company_id] ?? null;
        $samePartner = $owner !== null && (int) $owner === (int) $first->partner_id;
        if (!$samePartner && (int) $first->partner_id === OSMOVIEW && $owner === null && $first->company_id) {
            $kp = $norm($companyName[$first->company_id] ?? '');
            $samePartner = $kp !== '' && ($kp === $norm($deal->customer) || $kp === $norm($deal->company_name));
        }
        if (!$samePartner) continue;

        $created = Carbon::parse($deal->date_create);
        if ($dates->min(fn($d) => abs($d->diffInDays($created, false))) > DAYS) continue;

        $candidates[$group][] = $deal->id;
    }
}
$byDeal = [];
foreach ($candidates as $group => $ids) foreach ($ids as $id) $byDeal[$id][] = $group;

$links = [];
$doubtful = [];
foreach ($candidates as $group => $ids) {
    $number = $groups[$group]->first()->number;
    if (count($ids) === 1 && count($byDeal[$ids[0]]) === 1) {
        $links[$group] = $ids[0];
    } else {
        $doubtful[] = "{$number}: сделки " . implode(', ', array_map(fn($id) => "#{$id}" . (count($byDeal[$id]) > 1 ? ' (подходит ещё ' . (count($byDeal[$id]) - 1) . ' КП)' : ''), $ids));
    }
}

echo "=== Новые связи (", count($links), ")\n";
foreach ($links as $group => $id) {
    $p = $groups[$group]->first();
    $d = $deals[$id];
    printf("  %-8s %-34s ↔ #%-4d %-45s %s | %s %s | %s\n", $p->number, mb_substr((string) ($companyName[$p->company_id] ?? '—'), 0, 34), $id, mb_substr($d->title, 0, 45),
        mb_substr($d->customer ?: $d->company_name, 0, 25), number_format((float) $d->opportunity, 0, ',', ' '), $d->currency_id, $d->stage_name);
}
echo "\n=== Не связаны: кандидат не единственный (", count($doubtful), ")\n  ", implode("\n  ", $doubtful) ?: '—', "\n";

/*** 2. Статусы ***/

$allLinks = DB::table('proposal_crm_deals')->get(['proposal_group', 'crm_deal_id'])->groupBy('proposal_group')->map(fn($l) => $l->pluck('crm_deal_id')->all());
foreach ($links as $group => $id) $allLinks[$group] = [$id];

$confirmed = DB::table('contract_specification_proposals')->pluck('proposal_group')
    ->merge(DB::table('contracts as c')->join('proposals as p', 'p.id', '=', 'c.proposal_id')->pluck('p.group'))->flip();
$current = DB::table('proposals')->whereIn('id', DB::table('proposals')->selectRaw('MAX(id)')->groupBy('group'))->get(['group', 'number', 'status'])->keyBy('group');

$changes = [];
$blocked = [];
foreach ($allLinks as $group => $ids) {
    if (!isset($current[$group])) continue;
    $stages = collect($ids)->map(fn($id) => $deals[$id]->stage_name ?? null)->filter();
    if ($stages->isEmpty()) continue;
    $won = $stages->first(fn($s) => in_array($s, WON_STAGES, true));
    $allLost = $stages->every(fn($s) => isset(LOST_STAGES[$s]));
    if ($won) {
        $target = ['won', null, $won];
    } elseif ($allLost) {
        $stage = $stages->contains('Canceled') ? 'Canceled' : 'Suspended';
        $target = ['lost', LOST_STAGES[$stage], $stage];
    } else {
        continue;
    }
    $now = $current[$group]->status ?: 'in_work';
    if ($now === $target[0]) continue;
    $row = ['group' => $group, 'number' => $current[$group]->number, 'from' => $now, 'to' => $target[0], 'reason' => $target[1], 'stage' => $target[2], 'deals' => $ids];
    if ($now === 'won' && $target[0] === 'lost' && isset($confirmed[$group])) $blocked[] = $row;
    else $changes[] = $row;
}

$fmt = fn($r) => sprintf("  %-8s %s → %s (%s, сделки %s)", $r['number'], $r['from'], $r['to'] . ($r['reason'] ? '/' . $r['reason'] : ''), $r['stage'], implode(',', array_map(fn($id) => "#$id", $r['deals'])));
echo "\n=== Смена статусов (", count($changes), "): ", collect($changes)->countBy(fn($r) => "{$r['from']}→{$r['to']}")->map(fn($n, $k) => "$k $n")->implode(', '), "\n";
echo implode("\n", array_map($fmt, $changes)), "\n";
echo "\n=== Не трогаю: «Выиграно» подтверждено спецификацией/договором, а сделка проиграна (", count($blocked), ")\n", implode("\n", array_map($fmt, $blocked)) ?: '  —', "\n";

/*** 3. Запись ***/

DB::beginTransaction();
try {
    foreach ($links as $group => $id) {
        ProposalCrmDeal::create(['proposal_group' => $group, 'crm_deal_id' => $id, 'is_main' => true,
            'comment' => 'Связь по совпадению суммы, партнёра и даты (23.09.2026)', 'linked_at' => now(), 'linked_by' => null]);
    }
    foreach ($changes as $r) {
        $proposal = Proposal::where('group', $r['group'])->orderByDesc('id')->first();
        ProposalStatusService::set($proposal, ProposalStatus::from($r['to']), $r['reason'] ? ProposalLostReason::from($r['reason']) : null,
            'По стадии сделки Битрикс24: ' . $r['stage'] . ' (' . implode(', ', array_map(fn($id) => "#$id", $r['deals'])) . ')');
    }
    EntityLogService::flush();
    echo "\nсвязей записано: ", count($links), ", статусов изменено: ", count($changes), "\n";
    $apply ? DB::commit() : DB::rollBack();
    echo $apply ? "ВЫПОЛНЕНО\n" : "пробный прогон — всё откачено\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "ОШИБКА, всё откачено: ", $e->getMessage(), " ", $e->getFile(), ':', $e->getLine(), "\n";
    exit(1);
}
