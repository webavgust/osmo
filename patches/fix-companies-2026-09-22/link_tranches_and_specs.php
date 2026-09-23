<?php
/*
 * Сделки-транши и спецификации → КП по аналогии с AK650 (решение владельца 23.09.2026).
 *
 * AK650 — одно КП, его спецификации и несколько сделок-оплат. Здесь то же для других КП:
 * сделки-оплаты (части, проценты, аванс) и спецификации, чьи платежи и суммы указывают на КП.
 * Пропущено по решению владельца: С.4 Пулково, Птицефабрика, En+ НИ-ТЭЦ, Русал, МТИ этапы, ВТБ PoC,
 * продления клубов SINAPSYSTEC. Для Amna суммы не сходятся — скоринг берёт суммы спецификаций.
 *
 * Спецификация прикрепляется через SpecProposalService::attach() — как из интерфейса: КП в работе
 * становится «Выиграно». Затем статусы КП со сделками — по стадиям (как link_deals_and_statuses.php),
 * «Выиграно», подтверждённое спецификацией, в «Проиграно» не переводится.
 *
 *   php link_tranches_and_specs.php            — пробно, с откатом
 *   php link_tranches_and_specs.php --apply    — выполнить
 */

foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $name => $stream) {
    if (!defined($name)) define($name, fopen($stream, $name === 'STDIN' ? 'r' : 'w'));
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Services\SpecProposalService;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use App\Modules\Pub\Proposal\Models\ProposalLostReason;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv ?? [], true);

const WON_STAGES = ['Acceptance tests', 'Closing documents', 'Completed', 'Contracting', 'Execution (POST-PAYMENT)', 'Execution (PRE-PAYMENT)'];
const LOST_STAGES = ['Suspended' => 'frozen', 'Canceled' => 'canceled'];

// КП => [сделки Б24 => ожидаемое начало названия], [спецификации => ожидаемое название], почему
const PLAN = [
    'AK650' => [[], [81 => 'С.3_Лидары', 83 => 'С.3_Лидары'], 'сумма спецификаций = сумме КП; сделки-оплаты уже связаны владельцем'],
    'AK736' => [[747 => 'Лидары  Перенос стенда', 751 => 'Лидары  Перенос стенда'], [101 => 'С.4_Лидары. перенос точки', 108 => 'С.4_Лидары. перенос точки'], 'платежи спецификаций 320 000 + 2 030 080 = КП'],
    'OD588' => [[319 => 'Shanghai Hengde Science – BMW', 321 => 'Shanghai Hengde Science – BMW', 325 => 'Shanghai Hengde Science – BMW'], [], 'части 2–4, часть 1 уже связана; платёж спецификации С.1 = #319'],
    'PG675' => [[529 => 'МТ-и (депо)'], [], 'платёж спецификации С.2_Метро (доп.работы), прикреплённой к PG675'],
    'PG691' => [[589 => 'СТК - Починковский'], [77 => 'С.1 Маслосырзавод', 78 => 'С.1 Маслосырзавод'], 'платежи спецификаций = сделка; единственное КП заказчика'],
    'AK734' => [[761 => 'Arabian Horses AI Analytics PoC'], [], 'название совпадает; единственное КП заказчика'],
    'AK700' => [[607 => 'Amna Company Limited 2 cameras', 707 => 'Amna Company Limited 2 cameras'], [102 => 'S.1 Amna (проходная)', 103 => 'S.1 Amna (проходная)'], 'учёт рабочего времени по лицу на проходной; суммы — по спецификации'],
    'AA595' => [[55 => 'ССР – Геленджик'], [50 => 'Спецификация 1', 52 => 'Спецификация 1'], 'сделка 18 111 960 = спецификации 12 000 000 + 6 111 960; КП договоров'],
    'OD' => [[], [36 => 'Спецификация 1', 37 => 'Спецификация 1'], 'КП договоров («для договора»), 4 200 000 = спецификация'],
    'PG568' => [[], [42 => 'С.8', 43 => 'С.9', 44 => 'С.10', 45 => 'С.11', 46 => 'С.12', 47 => 'С.13', 48 => 'С.14'], '7 × 288 000 = 2 016 000 = КП'],
    'PG591' => [[], [20 => 'С.12 _Механик Сизов'], '50 000 + НДС = КП; сделка #275 уже связана'],
    'PG685' => [[], [84 => 'С.7_Пулково'], '1 584 000 = КП = сделка #571'],
    'AK732' => [[235 => 'СФЛТ - РРПК - БМРТ - Березина'], [21 => 'С.14_Березина'], 'ред.2 КП (2024) = 170 500 = спецификация = сделка'],
    'AK723' => [[221 => 'СФТЛ-РРПК-БМРТ Владивосток'], [35 => 'С.13_Владивосток'], 'ред.2 КП (2024) = 190 000 = спецификация = сделка'],
    'AK727' => [[223 => 'СФТЛ-РРПК-БМРТ Павел Батов'], [34 => 'С.12_Павел Батов'], 'единственное КП «Павел Батов» 2024 года; спецификация 342 000, сделка 342 500'],
];

$bx = DB::connection('bitrix');
$problems = [];
$groups = [];
foreach (PLAN as $number => [$deals, $specs]) {
    $found = DB::table('proposals')->where('number', $number)->distinct()->pluck('group');
    if ($found->count() !== 1) { $problems[] = "КП {$number}: групп " . $found->count(); continue; }
    $groups[$number] = $found->first();
    foreach ($deals as $id => $title) {
        $real = (string) $bx->table('crm_deal')->where('id', $id)->value('title');
        $plain = fn($x) => preg_replace("~[^\p{L}\p{N}]+~u", "", mb_strtolower($x));
        if (!str_starts_with($plain($real), $plain($title))) $problems[] = "сделка #{$id}: «{$real}», ожидалось начало «{$title}»";
        if (DB::table('proposal_crm_deals')->where('crm_deal_id', $id)->exists()) $problems[] = "сделка #{$id} уже связана";
    }
    foreach ($specs as $id => $name) {
        if (DB::table('contract_specifications')->where('id', $id)->value('name') !== $name) $problems[] = "спецификация #{$id}: ожидалась «{$name}»";
        if (DB::table('contract_specification_proposals')->where('contract_specification_id', $id)->exists()) $problems[] = "спецификация #{$id} уже прикреплена";
    }
}
if ($problems) { echo "Исходное состояние не совпало — ничего не менял:\n  - ", implode("\n  - ", $problems), "\n"; exit(1); }
echo "Сверка: ок\n", $apply ? "РЕЖИМ: выполнить\n" : "РЕЖИМ: пробный, с откатом\n";

$labels = ['won' => 'Выиграно', 'lost' => 'Проиграно', 'in_work' => 'В работе'];
$statusOf = fn($group) => DB::table('proposals')->where('group', $group)->orderByDesc('id')->value('status') ?: 'in_work';

DB::beginTransaction();
try {
    foreach (PLAN as $number => [$deals, $specs, $why]) {
        $group = $groups[$number];
        $proposal = Proposal::where('group', $group)->orderByDesc('id')->first();
        $before = $statusOf($group);
        $hasMain = ProposalCrmDeal::where('proposal_group', $group)->where('is_main', true)->exists();

        foreach (array_keys($deals) as $id) {
            ProposalCrmDeal::create(['proposal_group' => $group, 'crm_deal_id' => $id, 'is_main' => !$hasMain,
                'comment' => 'Транш по аналогии с AK650 (23.09.2026)', 'linked_at' => now(), 'linked_by' => null]);
            $hasMain = true;
        }
        foreach (array_keys($specs) as $id) {
            SpecProposalService::attach(ContractSpecification::findOrFail($id), $proposal);
        }

        // статус по сделкам
        $dealIds = DB::table('proposal_crm_deals')->where('proposal_group', $group)->pluck('crm_deal_id');
        $stages = $bx->table('crm_deal')->whereIn('id', $dealIds)->pluck('stage_name');
        $now = $statusOf($group);
        $confirmed = DB::table('contract_specification_proposals')->where('proposal_group', $group)->exists();
        $target = null;
        if ($stages->contains(fn($s) => in_array($s, WON_STAGES, true))) $target = ['won', null, $stages->first(fn($s) => in_array($s, WON_STAGES, true))];
        elseif ($stages->isNotEmpty() && $stages->every(fn($s) => isset(LOST_STAGES[$s]))) {
            $stage = $stages->contains('Canceled') ? 'Canceled' : 'Suspended';
            $target = ['lost', LOST_STAGES[$stage], $stage];
        }
        $note = '';
        if ($target && $target[0] !== $now) {
            if ($now === 'won' && $target[0] === 'lost' && $confirmed) {
                $note = " (сделка {$target[2]}, но «Выиграно» подтверждено спецификацией — не меняю)";
            } else {
                ProposalStatusService::set($proposal->refresh(), ProposalStatus::from($target[0]), $target[1] ? ProposalLostReason::from($target[1]) : null,
                    'По стадии сделки Битрикс24: ' . $target[2] . ' (' . $dealIds->map(fn($id) => "#$id")->implode(', ') . ')');
            }
        }
        $after = $statusOf($group);
        printf("%-6s +сделки %-17s +спец %-26s | статус %s%s | %s\n", $number, implode(',', array_keys($deals)) ?: '—', implode(',', array_keys($specs)) ?: '—',
            $before === $after ? $labels[$after] : $labels[$before] . ' → ' . $labels[$after], $note, $why);
    }
    EntityLogService::flush();
    echo "\nсвязей сделок: ", collect(PLAN)->sum(fn($p) => count($p[0])), ", спецификаций: ", collect(PLAN)->sum(fn($p) => count($p[1])), "\n";
    $apply ? DB::commit() : DB::rollBack();
    echo $apply ? "ВЫПОЛНЕНО\n" : "пробный прогон — всё откачено\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "ОШИБКА, всё откачено: ", $e->getMessage(), " ", $e->getFile(), ':', $e->getLine(), "\n";
    exit(1);
}
