<?php

namespace App\Modules\Pub\EntityLog\Console;

use App\Modules\Pub\EntityLog\Models\EntityLog;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use Illuminate\Console\Command;

/**
 * Baseline-слепки журнала изменений (patch v29).
 *
 * По всем корням из config/entity_log.php (у КП — каждая строка-редакция)
 * снимается первый слепок, если слепков ещё нет. На проде запускается один раз
 * после миграций; повторный запуск ничего не дублирует.
 */
class BaselineCommand extends Command
{
    protected $signature = 'entity-log:baseline
        {--chunk=100 : размер порции строк}';

    protected $description = 'Снять baseline-слепки корней журнала изменений, у которых слепков ещё нет';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $total_created = $total_skipped = 0;

        foreach (EntityLogService::types() as $type => $class) {
            $created = $skipped = $failed = 0;

            $class::query()->orderBy((new $class)->getKeyName())->chunk($chunk, function ($rows) use ($type, &$created, &$skipped, &$failed) {
                $ids = $rows->map(fn($row) => (int) $row->getKey())->all();

                $have = EntityLog::where('type', $type)
                    ->whereIn('model_id', $ids)
                    ->whereNotNull('data')
                    ->distinct()
                    ->pluck('model_id')
                    ->map(fn($id) => (int) $id)
                    ->flip();

                foreach ($rows as $row) {
                    if ($have->has((int) $row->getKey())) {
                        $skipped++;
                        continue;
                    }

                    try {
                        EntityLogService::baseline($row) ? $created++ : $failed++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->warn(sprintf('%s #%s: %s', $type, $row->getKey(), $e->getMessage()));
                    }
                }
            });

            $this->info(sprintf('%s (%s): создано %d, уже было %d%s', $type, class_basename($class), $created, $skipped, $failed ? ', ошибок ' . $failed : ''));

            $total_created += $created;
            $total_skipped += $skipped;
        }

        $this->info(sprintf('Итого: создано %d, уже было %d', $total_created, $total_skipped));

        return self::SUCCESS;
    }
}
