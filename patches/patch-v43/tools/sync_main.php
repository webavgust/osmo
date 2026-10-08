<?php
/*
 * patch v43: однократная починка дубля главной сделки в proposals.crm_deal_id.
 *
 * Скрипты 23.09 создавали привязки ProposalCrmDeal без ProposalDealService::syncMain(),
 * поэтому у части КП главная привязка в proposal_crm_deals есть, а в proposals.crm_deal_id
 * (и crm_deal_linked_at / crm_deal_linked_by) пусто. Скрипт находит группы, где дубль
 * расходится с главной привязкой, и для каждой делает то же, что ProposalDealService::syncMain(),
 * но не трогает proposals.updated_at (syncMain через Eloquent его сдвигает).
 *
 * Берутся только группы, у которых есть привязки: КП без привязок с заполненным crm_deal_id
 * скрипт не трогает (syncMain их бы обнулил), а только считает и печатает.
 * Журнал изменений (EntityLog) не пишется — это починка данных, а не правка пользователя.
 *
 * Запуск из корня сайта:
 *   php patches/patch-v43/tools/sync_main.php --dry-run   — только посчитать
 *   php patches/patch-v43/tools/sync_main.php             — починить
 *
 * На проде перед запуском — копия БД: php patches/deploy-v21-v32/backup_db.php
 */

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;

$dry = in_array('--dry-run', $argv, true);

/**
 * Итерации группы, у которых дубль главной сделки расходится с привязками
 *
 * @param string $group
 * @return \Illuminate\Support\Collection
 */
$stale = function (string $group) {
    // главная — как её выбирает syncMain(): сначала is_main, затем по id
    $main = ProposalCrmDeal::forGroup($group)->first();

    $deal_id = $main?->crm_deal_id;
    $linked_at = $main?->linked_at?->format('Y-m-d H:i:s');
    $linked_by = $main?->linked_by;

    return Proposal::where('group', $group)
        ->get(['id', 'group', 'iteration', 'number', 'crm_deal_id', 'crm_deal_linked_at', 'crm_deal_linked_by'])
        ->filter(function ($proposal) use ($deal_id, $linked_at, $linked_by) {
            $at = $proposal->crm_deal_linked_at ? date('Y-m-d H:i:s', strtotime((string) $proposal->crm_deal_linked_at)) : null;

            return (string) $proposal->crm_deal_id !== (string) $deal_id
                || (string) $at !== (string) $linked_at
                || (string) $proposal->crm_deal_linked_by !== (string) $linked_by;
        });
};

$groups = ProposalCrmDeal::query()->distinct()->orderBy('proposal_group')->pluck('proposal_group');

$changed_groups = 0;
$changed_iterations = 0;

foreach ($groups as $group) {
    $rows = $stale((string) $group);
    if ($rows->isEmpty()) continue;

    $changed_groups++;
    $changed_iterations += $rows->count();

    $number = $rows->first()->number ?: $group;
    echo ($dry ? '[dry] ' : '') . $number . ': итераций ' . $rows->count() . "\n";

    if (!$dry) {
        // то же, что ProposalDealService::syncMain(), но без сдвига proposals.updated_at:
        // починка данных не должна поднимать КП как «изменённые сегодня»
        $main = ProposalCrmDeal::forGroup((string) $group)->first();

        if ($main && !$main->is_main) {
            $main->update(['is_main' => true]);
        }

        Proposal::where('group', (string) $group)->toBase()->update([
            'crm_deal_id' => $main?->crm_deal_id,
            'crm_deal_linked_at' => $main?->linked_at,
            'crm_deal_linked_by' => $main?->linked_by,
        ]);
    }
}

// справочно: КП без привязок, у которых crm_deal_id заполнен (скрипт их не трогает)
$orphans = Proposal::whereNotNull('crm_deal_id')
    ->whereNotIn('group', $groups->all())
    ->count();

echo "\n" . ($dry ? 'Будет изменено' : 'Изменено') . ': КП ' . $changed_groups . ', итераций ' . $changed_iterations . "\n";
echo 'Групп с привязками: ' . $groups->count() . "\n";
echo 'Итераций с crm_deal_id без привязок (не тронуты): ' . $orphans . "\n";
