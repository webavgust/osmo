<?php

namespace App\Console\Commands;

use App\Modules\Pub\DealProject\Services\DealProjectSeedService;
use Illuminate\Console\Command;

/**
 * Разовое автосоздание проектов по сделкам Битрикса (patch v24).
 *
 * Запускается один раз после выкатки патча. Повторный запуск ничего не
 * дублирует: сделки с проектом пропускаются.
 */
class DealProjectSeedCommand extends Command
{
    protected $signature = 'deal-project:seed
        {--from=2025-01-01 : с какой даты создания сделки смотрим}
        {--dry-run : только показать, что будет создано}';

    protected $description = 'Завести проекты по проектным сделкам Битрикса';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $from = (string) $this->option('from');

        try {
            $report = DealProjectSeedService::run($from, $dry);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $created = collect($report['rows'])->where('status', 'created');
        $skipped = collect($report['rows'])->where('status', '!=', 'created');

        if ($created->isNotEmpty()) {
            $this->table(
                ['Сделка', 'Партнёр', 'Компания', 'Дата начала', 'Спец. из КП', 'Стадия'],
                $created->map(fn($row) => [
                    $row['deal'], $row['partner'], $row['company'],
                    $row['date_start'], $row['specs'], $row['stage'],
                ])->all()
            );
        }

        foreach ($skipped as $row) {
            $this->warn(sprintf('Сделка #%d пропущена: %s', $row['deal'], $row['reason']));
        }

        $this->info(sprintf(
            '%s: проектов %d, пропущено %d (из них без сопоставления партнёра %d)',
            $dry ? 'Пробный прогон' : 'Создано',
            $report['created'],
            $report['skipped'],
            $skipped->where('status', 'no_partner')->count()
        ));

        return self::SUCCESS;
    }
}
