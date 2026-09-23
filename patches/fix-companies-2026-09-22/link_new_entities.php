<?php
/*
 * Связи для КП и спецификаций, появившихся на проде 16–22.09.2026 (разбор 23.09.2026).
 * Только добавление: существующие данные не меняются и не удаляются; связь, которая уже есть, пропускается.
 * Сначала — локально, затем тот же сценарий на проде.
 *
 * Связь ставится, если совпали тема названия, партнёр и заказчик, а кандидат единственный:
 *  - AA796 «Дефекты автомобильных дисков» (Softline → Русал СКАД)
 *    ↔ #865 «Аналитика дефектов автомобильных дисков» (Softline → ЛМЗ СКАД (дочка Русал)).
 * Не связаны (нет уверенности): AA793 ВДНХ (есть ещё AK760; сделка #809 — демо), AA794 Киевская площадь
 * (сделка #813 другого партнёра и уже у AA778), AA797 (сделки нет).
 *
 *   php link_new_entities.php            — пробно, с откатом
 *   php link_new_entities.php --apply    — выполнить
 */

foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $name => $stream) {
    if (!defined($name)) define($name, fopen($stream, $name === 'STDIN' ? 'r' : 'w'));
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv ?? [], true);

// КП => [группа, сделка, начало названия сделки]
const LINKS = [
    'AA796' => ['44bc7157-d84c-4a84-849a-b78d43d74845', 865, 'Аналитика дефектов автомобильных дисков'],
];

$plain = fn($x) => preg_replace('~[^\p{L}\p{N}]+~u', '', mb_strtolower((string) $x));
$problems = [];
foreach (LINKS as $number => [$group, $deal, $title]) {
    if (DB::table('proposals')->where('group', $group)->where('number', $number)->doesntExist()) $problems[] = "КП {$number}: группа {$group} не найдена";
    $real = DB::connection('bitrix')->table('crm_deal')->where('id', $deal)->value('title');
    if ($real !== null && !str_starts_with($plain($real), $plain($title))) $problems[] = "сделка #{$deal}: «{$real}», ожидалось «{$title}»";
    if ($real === null) echo "сделки #{$deal} нет в зеркале Битрикса этой базы — связь всё равно ставлю (зеркало отстаёт)\n";
    $other = DB::table('proposal_crm_deals')->where('crm_deal_id', $deal)->where('proposal_group', '!=', $group)->exists();
    if ($other) $problems[] = "сделка #{$deal} уже связана с другим КП";
}
if ($problems) { echo "Исходное состояние не совпало — ничего не менял:\n  - ", implode("\n  - ", $problems), "\n"; exit(1); }

$before = DB::table('proposal_crm_deals')->count();
DB::beginTransaction();
try {
    foreach (LINKS as $number => [$group, $deal]) {
        if (DB::table('proposal_crm_deals')->where('proposal_group', $group)->where('crm_deal_id', $deal)->exists()) { echo "{$number} ↔ #{$deal}: уже есть\n"; continue; }
        $hasMain = DB::table('proposal_crm_deals')->where('proposal_group', $group)->where('is_main', true)->exists();
        ProposalCrmDeal::create(['proposal_group' => $group, 'crm_deal_id' => $deal, 'is_main' => !$hasMain,
            'comment' => 'Связь по теме, партнёру и заказчику (23.09.2026)', 'linked_at' => now(), 'linked_by' => null]);
        echo "{$number} ↔ #{$deal}: связано\n";
    }
    EntityLogService::flush();
    $after = DB::table('proposal_crm_deals')->count();
    echo "связей было {$before}, стало {$after}", $after < $before ? ' — ОШИБКА: стало меньше' : '', "\n";
    if ($after < $before) throw new RuntimeException('число связей уменьшилось');
    $apply ? DB::commit() : DB::rollBack();
    echo $apply ? "ВЫПОЛНЕНО\n" : "пробный прогон — откачено\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "ОШИБКА, откачено: ", $e->getMessage(), "\n";
    exit(1);
}
