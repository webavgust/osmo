<?php

namespace App\Modules\Pub\LicenseKey\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;

/**
 * Период действия лицензионного ключа (patch v49, часть C).
 *
 * Ключ продлевается новым периодом по новой спецификации, а не выдаётся заново.
 * Текущий (последний по active_to) период дублируется в поля ключа —
 * LicenseKey::syncFromPeriods().
 */
class LicenseKeyPeriod extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['contract_specification_id', 'active_from', 'active_to'];
    protected $casts = ['active_from' => 'date', 'active_to' => 'date'];

    /**
     * Ключ периода
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function license_key()
    {
        return $this->belongsTo(LicenseKey::class);
    }

    /**
     * Спецификация, по которой выдан период
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function specification()
    {
        return $this->belongsTo(ContractSpecification::class, 'contract_specification_id');
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'license_key';
    }

    public static function logLabel(): string
    {
        return 'Период ключа';
    }

    public function logTitle(?int $index = null): string
    {
        $from = $this->active_from?->format('d.m.Y') ?? '—';
        $to = $this->active_to?->format('d.m.Y') ?? '—';

        return 'Период ' . $from . ' – ' . $to;
    }

    public static function logFields(): array
    {
        return [
            'contract_specification_id' => ['label' => 'Спецификация', 'relation' => 'specification'],
            'active_from' => ['label' => 'Дата действия, с', 'type' => 'date'],
            'active_to' => ['label' => 'Дата действия, по', 'type' => 'date'],
        ];
    }
}
