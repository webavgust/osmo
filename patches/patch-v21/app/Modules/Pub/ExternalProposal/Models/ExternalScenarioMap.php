<?php

namespace App\Modules\Pub\ExternalProposal\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Scenario\Models\Scenario;

/**
 * Соответствие сценария внешней системы нашему справочнику (patch v21).
 */
class ExternalScenarioMap extends ModuleModel
{
    protected $table = 'external_scenario_map';

    protected $fillable = ['source', 'external_scenario_id', 'external_name', 'scenario_id', 'updated_by'];

    public function scenario()
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * Запомнить пару «его сценарий → наш»
     *
     * @param string $source
     * @param string $external_scenario_id
     * @param int $scenario_id
     * @param string|null $external_name
     * @param int|null $user_id
     * @return static
     */
    public static function remember(string $source, string $external_scenario_id, int $scenario_id, ?string $external_name = null, ?int $user_id = null): static
    {
        return static::updateOrCreate(
            ['source' => $source, 'external_scenario_id' => $external_scenario_id],
            ['scenario_id' => $scenario_id, 'external_name' => $external_name, 'updated_by' => $user_id]
        );
    }
}
