<?php

namespace App\Modules\Pub\ProposalVariantWork\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalWork\Models\ProposalWork;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVariantWork extends ModuleModel
{
    use HasLogger;

    public $timestamps = false;
    protected $fillable = ['cb_nds', 'nds', 'cost', 'count', 'discount_customer', 'cb_partner_discount', 'discount', 'total', 'discount_partner'];
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

    public function proposal_work()
    {
        return $this->belongsTo(ProposalWork::class);
    }

    public function getCostDiscountAttribute()
    {
        return round(($this->cost - $this->discount), 2);
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'proposal_variant';
    }

    public static function logLabel(): string
    {
        return 'Работа (вариант)';
    }

    /** Строки пересоздаются при каждом сохранении — сопоставление по позиции */
    public function logKey(int $index): string
    {
        return $this->logPositionKey($index);
    }

    public function logTitle(?int $index = null): string
    {
        $text = static::logText($this->proposal_work?->description, 60);

        return 'Работа ' . ($text !== '' ? '«' . $text . '»' : ($index ? '#' . $index : '#' . $this->id));
    }

    public static function logLinks(): array
    {
        return ['proposal_work' => ProposalWork::class];
    }

    public static function logFields(): array
    {
        return [
            'proposal_work_id' => ['label' => 'Работа', 'relation' => 'proposal_work'],
            'cost' => ['label' => 'Цена', 'type' => 'money'],
            'count' => ['label' => 'Часы'],
            'discount_customer' => ['label' => 'Скидка заказчика, %'],
            'discount_partner' => ['label' => 'Скидка партнёра, %'],
            'cb_partner_discount' => ['label' => 'Учитывать скидку партнёра', 'type' => 'bool'],
            'discount' => ['label' => 'Скидка', 'type' => 'money', 'derived' => true],
            'total' => ['label' => 'Итого', 'type' => 'money', 'derived' => true],
            'cb_nds' => ['label' => 'НДС', 'type' => 'bool'],
            'nds' => ['label' => 'Сумма НДС', 'type' => 'money', 'derived' => true],
        ];
    }
}
