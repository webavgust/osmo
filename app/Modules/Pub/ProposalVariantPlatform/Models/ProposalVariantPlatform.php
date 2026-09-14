<?php

namespace App\Modules\Pub\ProposalVariantPlatform\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVariantPlatform extends ModuleModel
{
    use HasLogger;

    public $timestamps = false;
    protected $fillable = ['sort', 'description', 'notice', 'cb_process', 'cb_nds', 'nds', 'cost', 'count', 'discount', 'cost_discount'];
    protected $casts = ['cb_process' => 'bool', 'cb_nds' => 'bool'];

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


    public function proposal_variant()
    {
        return $this->belongsTo(ProposalVariant::class);
    }

    public function getCostTotalAttribute()
    {
        return $this->cost_discount * $this->count;
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'proposal_variant';
    }

    public static function logLabel(): string
    {
        return 'Платформа';
    }

    /** Строки пересоздаются при каждом сохранении — сопоставление по позиции */
    public function logKey(int $index): string
    {
        return $this->logPositionKey($index);
    }

    public function logTitle(?int $index = null): string
    {
        $text = static::logText($this->description, 60);

        return 'Платформа ' . ($text !== '' ? '«' . $text . '»' : ($index ? '#' . $index : '#' . $this->id));
    }

    public static function logIgnore(): array
    {
        return ['sort'];
    }

    public static function logFields(): array
    {
        return [
            'description' => ['label' => 'Описание', 'type' => 'html'],
            'notice' => ['label' => 'Примечание', 'type' => 'html'],
            'cb_process' => ['label' => 'В расчёте', 'type' => 'bool'],
            'cost' => ['label' => 'Цена', 'type' => 'money'],
            'discount' => ['label' => 'Скидка заказчика, %'],
            'cost_discount' => ['label' => 'Цена итог', 'type' => 'money', 'derived' => true],
            'count' => ['label' => 'Кол-во'],
            'cb_nds' => ['label' => 'НДС', 'type' => 'bool'],
            'nds' => ['label' => 'Сумма НДС', 'type' => 'money', 'derived' => true],
        ];
    }
}
