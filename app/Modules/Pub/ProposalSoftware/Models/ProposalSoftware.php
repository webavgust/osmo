<?php

namespace App\Modules\Pub\ProposalSoftware\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalSoftwareScenario\Models\ProposalSoftwareScenario;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProposalSoftware extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['cb_process', 'description', 'notice', 'sort'];
    public $timestamps = false;

    /**
     * Дополняем слушатели событий
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($instance) {
            // почистим variants
        });
    }


    public function proposal()
    {
        return $this->belongsTo(Proposal::class)->orderBy('sort');
    }

    public function proposal_variant()
    {
        return $this->belongsTo(ProposalVariant::class);
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'proposal';
    }

    public static function logLabel(): string
    {
        return 'ПО (позиция КП)';
    }

    /** Строки пересоздаются при каждом сохранении — сопоставление по позиции */
    public function logKey(int $index): string
    {
        return $this->logPositionKey($index);
    }

    public function logTitle(?int $index = null): string
    {
        $text = static::logText($this->description, 60);

        return 'ПО ' . ($text !== '' ? '«' . $text . '»' : ($index ? '#' . $index : '#' . $this->id));
    }

    public static function logIgnore(): array
    {
        return ['sort'];
    }

    public static function logFields(): array
    {
        return [
            'cb_process' => ['label' => 'В расчёте', 'type' => 'bool'],
            'description' => ['label' => 'Описание', 'type' => 'html'],
            'notice' => ['label' => 'Примечание', 'type' => 'html'],
        ];
    }
}
