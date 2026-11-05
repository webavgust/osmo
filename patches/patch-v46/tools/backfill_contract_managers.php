<?php
/**
 * Проставить договорам ответственного менеджера по инициалам в номере (patch v46)
 *
 * Запуск из корня сайта: php patches/patch-v46/tools/backfill_contract_managers.php [--apply]
 * Без --apply только показывает, что будет сделано. Заполняются только пустые manager_id.
 * На проде — от www-root, после копии БД (backup_db.php).
 */

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv ?? [], true);

// инициалы => id; совпавшие у нескольких пользователей не используются (Роман и Наталья — оба RK)
$users = DB::table('users')->whereNotNull('initials')->where('initials', '!=', '')->get(['id', 'initials', 'name', 'last_name']);
$unique = $users->groupBy(fn ($user) => mb_strtoupper($user->initials))->filter(fn ($group) => $group->count() === 1)->map->first();

$set = $skipped = 0;
DB::transaction(function () use ($apply, $unique, &$set, &$skipped) {
    foreach (DB::table('contracts')->whereNull('manager_id')->orderBy('id')->get(['id', 'number']) as $contract) {
        // кириллические А и К встречаются в старых номерах (P-АА-26/10.2024)
        $number = strtr((string) $contract->number, ['А' => 'A', 'К' => 'K', 'М' => 'M', 'О' => 'O', 'Р' => 'P']);
        $user = preg_match('~^[A-Z]{1,2}-([A-Z]{2})-\d~', $number, $match) ? $unique->get($match[1]) : null;

        if (!$user) {
            $skipped++;
            echo "пропущен  {$contract->id}\t{$contract->number}\n";
            continue;
        }

        $set++;
        echo "проставлен {$contract->id}\t{$contract->number}\t{$user->name} {$user->last_name}\n";
        if ($apply) DB::table('contracts')->where('id', $contract->id)->update(['manager_id' => $user->id]);
    }
});

echo ($apply ? 'ЗАПИСАНО' : 'ПРОБНЫЙ ПРОГОН') . ": проставлено {$set}, пропущено {$skipped}\n";
