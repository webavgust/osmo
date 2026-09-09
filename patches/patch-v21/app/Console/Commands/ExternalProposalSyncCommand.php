<?php

namespace App\Console\Commands;

use App\Modules\Pub\ExternalProposal\Services\ExternalProposalService;
use Illuminate\Console\Command;

/**
 * Синхронизация КП OSMOVIEW CP из API (patch v21): список → upsert,
 * затем detail для записей без данных.
 */
class ExternalProposalSyncCommand extends Command
{
    protected $signature = 'external-proposal:sync {--no-details : только список, без detail}';

    protected $description = 'Забрать КП из API OSMOVIEW CP';

    public function handle(): int
    {
        try {
            $result = (new ExternalProposalService())->sync(!$this->option('no-details'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Список: %d записей (новых %d, обновлено %d), detail загружено: %d',
            $result['count'], $result['created'], $result['updated'], $result['details']
        ));

        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return self::SUCCESS;
    }
}
