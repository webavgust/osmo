<?php

namespace App\Modules\Pub\EntityLog\Console;

use App\Modules\Pub\EntityLog\Models\EntityLog;
use App\Modules\Pub\EntityLog\Models\EntityLogChange;
use App\Modules\Pub\EntityLog\Services\EntityLogDiff;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Пересчёт строк изменений журнала по сохранённым слепкам (patch v31).
 *
 * Нужен после правок сравнения: строки события пишутся один раз в момент записи,
 * и старые события показывали бы старую картину (перестановку вариантов как
 * полсотни изменений, итоги без пометки «косвенное»). Слепки не трогаются —
 * пересчитываются только строки entity_log_changes и счётчик changes_count.
 *
 * Пересчитываются события «изменено»: у них есть предыдущий слепок той же строки.
 * Общие поля группы (logSharedFields: статус КП и сделка) берутся так же, как при
 * записи, — из последнего слепка группы на момент события.
 */
class RediffCommand extends Command
{
    protected $signature = 'entity-log:rediff
        {--type= : слаг корня (proposal, partner, company)}
        {--group= : ключ ленты (у КП — group)}
        {--id=* : только эти события}
        {--dry-run : показать, что изменится, ничего не записывая}';

    protected $description = 'Пересчитать строки изменений журнала по сохранённым слепкам';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $types = EntityLogService::types();

        $query = EntityLog::query()
            ->where('event', EntityLog::EVENT_UPDATED)
            ->whereNotNull('data')
            ->orderBy('id');

        if ($this->option('type')) $query->where('type', (string) $this->option('type'));
        if ($this->option('group')) $query->where('group_key', (string) $this->option('group'));
        if ($this->option('id')) $query->whereIn('id', array_map('intval', (array) $this->option('id')));

        $checked = $changed = $rows_before = $rows_after = 0;

        $query->chunkById(100, function ($logs) use ($types, $dry, &$checked, &$changed, &$rows_before, &$rows_after) {
            foreach ($logs as $log) {
                $checked++;
                $class = $types[$log->type] ?? null;
                if (!$class) continue;

                $old = $this->previous($log, $class);
                if ($old === null) continue;

                try {
                    $rows = EntityLogDiff::compare($old, $log->data_array);
                } catch (\Throwable $e) {
                    $this->warn(sprintf('#%d: %s', $log->id, $e->getMessage()));
                    continue;
                }

                $before = (int) $log->changes_count;
                $rows_before += $before;
                $rows_after += count($rows);

                if (!$this->differs($log, $rows)) continue;

                $changed++;
                $this->line(sprintf('#%d %s %s: строк %d → %d', $log->id, $log->created_at, $log->title, $before, count($rows)));

                if ($dry) continue;

                DB::transaction(function () use ($log, $rows) {
                    EntityLogChange::where('entity_log_id', $log->id)->delete();

                    if (!empty($rows)) {
                        EntityLogChange::insert(array_map(fn($row) => $row + ['entity_log_id' => $log->id], $rows));
                    }

                    $log->changes_count = count($rows);
                    $log->save();
                });
            }
        });

        $this->info(sprintf(
            '%sпроверено событий: %d, пересчитано: %d, строк было %d, стало %d',
            $dry ? '[проверка] ' : '',
            $checked,
            $changed,
            $rows_before,
            $rows_after
        ));

        return self::SUCCESS;
    }

    /**
     * Старый слепок для события: предыдущий слепок той же строки с общими полями группы
     * на момент события (как EntityLogService::overlayShared при записи)
     *
     * @param EntityLog $log
     * @param string $class класс корня
     * @return array|null
     */
    protected function previous(EntityLog $log, string $class): ?array
    {
        $prev = EntityLog::where('type', $log->type)
            ->where('model_id', $log->model_id)
            ->withSnapshot()
            ->where('id', '<', $log->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $old = $prev?->data_array;
        if (empty($old)) return null;

        $shared = method_exists($class, 'logSharedFields') ? $class::logSharedFields() : [];
        if (empty($shared)) return $old;

        $latest = EntityLog::timeline($log->type, $log->group_key)
            ->withSnapshot()
            ->where('id', '<', $log->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (empty($latest) || (int) $latest->model_id === (int) $log->model_id || $latest->id === $prev->id) return $old;

        $attrs = (array) ($latest->data_array['attrs'] ?? []);
        foreach ($shared as $field) {
            if (array_key_exists($field, $attrs)) {
                $old['attrs'][$field] = $attrs[$field];
            }
        }

        return $old;
    }

    /**
     * Пересчёт отличается от записанного: другое число строк, другие строки или пометки
     *
     * @param EntityLog $log
     * @param array $rows
     * @return bool
     */
    protected function differs(EntityLog $log, array $rows): bool
    {
        $stored = EntityLogChange::where('entity_log_id', $log->id)
            ->orderBy('id')
            ->get(['kind', 'model_class', 'model_key', 'field', 'old_value', 'new_value', 'derived'])
            ->map(fn($row) => [$row->kind, $row->model_class, $row->model_key, $row->field, (string) $row->old_value, (string) $row->new_value, (bool) $row->derived])
            ->all();

        $fresh = array_map(fn($row) => [$row['kind'], $row['model_class'], $row['model_key'], $row['field'], (string) $row['old_value'], (string) $row['new_value'], (bool) $row['derived']], $rows);

        return $stored !== $fresh;
    }
}
