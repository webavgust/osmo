<?php

namespace App\Modules\Pub\ProposalVariantSoftware\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalSoftware\Models\ProposalSoftware;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVariantSoftware extends ModuleModel
{
    use HasLogger;

    public $timestamps = false;
    protected $fillable = ['cb_nds', 'nds', 'cost', 'count', 'cb_partner_discount', 'discount', 'total', 'discount_customer'];
    protected $casts = ['cb_nds' => 'bool', 'cb_partner_discount' => 'bool'];
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

    public function proposal_software()
    {
        return $this->belongsTo(ProposalSoftware::class);
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'proposal_variant';
    }

    public static function logLabel(): string
    {
        return 'ПО (вариант)';
    }

    /** Строки пересоздаются при каждом сохранении — сопоставление по позиции */
    public function logKey(int $index): string
    {
        return $this->logPositionKey($index);
    }

    public function logTitle(?int $index = null): string
    {
        $text = static::logText($this->proposal_software?->description, 60);

        return 'ПО ' . ($text !== '' ? '«' . $text . '»' : ($index ? '#' . $index : '#' . $this->id));
    }

    public static function logLinks(): array
    {
        return ['proposal_software' => ProposalSoftware::class];
    }

    public static function logFields(): array
    {
        return [
            'proposal_software_id' => ['label' => 'ПО', 'relation' => 'proposal_software'],
            'cost' => ['label' => 'Цена', 'type' => 'money'],
            'count' => ['label' => 'Кол-во'],
            'discount_customer' => ['label' => 'Скидка заказчика, %'],
            'cb_partner_discount' => ['label' => 'Учитывать скидку партнёра', 'type' => 'bool'],
            'discount' => ['label' => 'Скидка', 'type' => 'money', 'derived' => true],
            'total' => ['label' => 'Итого', 'type' => 'money', 'derived' => true],
            'cb_nds' => ['label' => 'НДС', 'type' => 'bool'],
            'nds' => ['label' => 'Сумма НДС', 'type' => 'money', 'derived' => true],
        ];
    }
}
