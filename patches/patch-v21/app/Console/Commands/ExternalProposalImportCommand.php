<?php

namespace App\Console\Commands;

use App\Modules\Pub\ExternalProposal\Services\ExternalProposalService;
use Illuminate\Console\Command;

/**
 * Импорт КП OSMOVIEW CP из файла с ответом API (patch v21).
 *
 * Пока прямой доступ к API закрыт, ответ detail копируется из браузера в
 * файл и заливается этой командой. Принимает ответ detail, голый `data`
 * и ответ списка. Doc-id для detail берётся из `--id` либо из имени файла
 * вида `detail_<docid>_....json`.
 */
class ExternalProposalImportCommand extends Command
{
    protected $signature = 'external-proposal:import {file : путь к JSON-файлу} {--id= : doc-id записи (для detail)}';

    protected $description = 'Импорт КП OSMOVIEW CP из JSON-файла (ответ list или detail)';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $path = is_file($file) ? $file : base_path($file);

        if (!is_file($path)) {
            $this->error("Файл не найден: {$file}");
            return self::FAILURE;
        }

        $external_id = $this->option('id') ?: null;
        if (empty($external_id) && preg_match('/detail_([A-Za-z0-9]{15,})_/', basename($path), $m)) {
            $external_id = $m[1];
        }

        try {
            $rows = (new ExternalProposalService())->importJson((string) file_get_contents($path), $external_id);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        foreach ($rows as $row) {
            $this->line(sprintf(
                '#%d  %s  %s  %s  payload: %s',
                $row->id,
                str_pad((string) $row->external_id, 22),
                str_pad((string) $row->external_number, 8),
                $row->name,
                $row->has_payload ? 'да' : 'нет'
            ));
        }

        $this->info('Импортировано записей: ' . $rows->count());

        return self::SUCCESS;
    }
}
