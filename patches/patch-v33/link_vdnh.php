<?php
/**
 * patch v33: связка пары ВДНХ на проде — главное AA793, второстепенное AK760 (решение владельца 23.09.2026).
 *
 * AK760 заведено в портале 18.05.2026; AA793 — то же КП, перенесённое из OSMOVIEW CP 16.09.2026 (там AK528).
 * Запуск из корня сайта (по SSH в stdin):
 *   runuser -u www-root -- php -- --dry-run < link_vdnh.php   — выполнить и откатить
 *   runuser -u www-root -- php < link_vdnh.php               — выполнить
 * Перед выполнением — копия базы (patches/deploy-v21-v32/backup_db.php).
 */

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalLink;
use App\Modules\Pub\Proposal\Services\ProposalLinkService;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Illuminate\Support\Facades\DB;

$dry = in_array('--dry-run', $argv ?? [], true);

$last = fn(string $number) => Proposal::where('number', $number)->orderByDesc('iteration')->orderByDesc('id')->first();
$main = $last('AA793');
$secondary = $last('AK760');
if (!$main || !$secondary) exit("Не найдено: AA793 " . ($main ? 'есть' : 'нет') . ", AK760 " . ($secondary ? 'есть' : 'нет') . "\n");

$state = function () {
    $counters = ProposalStatusService::counters();

    return [
        'proposal_links' => ProposalLink::count(),
        'КП в расчётах' => ProposalStatusService::latestIterations()->count(),
        'в работе' => $counters['in_work'] ?? null,
        'entity_logs' => DB::table('entity_logs')->count(),
        'proposals' => DB::table('proposals')->count(),
    ];
};

echo ($dry ? "ПРОБНЫЙ ПРОГОН\n" : "ВЫПОЛНЕНИЕ\n");
echo "Главное: {$main->number} #{$main->id} {$main->group} «{$main->name}»\n";
echo "Второстепенное: {$secondary->number} #{$secondary->id} {$secondary->group} «{$secondary->name}»\n";
echo "Мешает второстепенному: " . (ProposalLinkService::blockers($secondary) ? implode('; ', ProposalLinkService::blockers($secondary)) : 'ничего') . "\n";

$before = $state();
DB::beginTransaction();
try {
    $link = ProposalLinkService::link($main, $secondary,
        'ВДНХ: AK760 заведено в портале, AA793 — перенос из OSMOVIEW CP (AK528); главное — AA793 по решению владельца 23.09.2026');
    EntityLogService::flush();
    $after = $state();

    if ($dry) {
        DB::rollBack();
        echo "Откат выполнен\n";
    } else {
        DB::commit();
        echo "Связка #{$link->id} сохранена\n";
    }
} catch (\Throwable $e) {
    DB::rollBack();
    exit('Ошибка, откат: ' . $e->getMessage() . "\n");
}

foreach ($before as $key => $value) {
    printf("  %-16s %8s → %8s\n", $key, $value, $after[$key]);
}
if ($dry) {
    $final = $state();
    echo "После отката совпало с «до»: " . ($final == $before ? 'да' : 'НЕТ') . "\n";
}
