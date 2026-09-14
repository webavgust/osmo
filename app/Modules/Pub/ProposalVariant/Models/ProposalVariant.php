<?php

namespace App\Modules\Pub\ProposalVariant\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariantExtraPay\Models\ProposalVariantExtraPay;
use App\Modules\Pub\ProposalVariantPlatform\Models\ProposalVariantPlatform;
use App\Modules\Pub\ProposalVariantScenario\Models\ProposalVariantScenario;
use App\Modules\Pub\ProposalVariantSoftware\Models\ProposalVariantSoftware;
use App\Modules\Pub\ProposalVariantWork\Models\ProposalVariantWork;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProposalVariant extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['is_main', 'period_type', 'period_value', 'cost_total_base',
        'discount_customer', 'discount_partner_p', 'discount_partner', 'cost_total',
        'soft_cost_total_base', 'soft_discount_partner', 'soft_cost_total', 'soft_discount_customer', 'soft_discount_partner_p',
        'work_cost_total_base', 'work_discount_partner', 'work_cost_total', 'work_discount_customer',
        'neuro_cost_total_base', 'neuro_discount_customer', 'neuro_discount_partner', 'neuro_cost_total','neuro_discount_partner_p',
        'platform_cost_total_base', 'platform_discount_customer', 'platform_discount_partner', 'platform_cost_total', 'platform_discount_partner_p',

        'cost_total', 'neuro_nds_cost_total', 'soft_nds_cost_total', 'work_nds_cost_total', 'nds_cost_total', 'platform_nds_cost_total',
        'task'
    ];




    protected $casts = ['is_main' => 'bool'];


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

            $instance->proposal_scenarios->each(function($sub_instance) {
                $sub_instance->delete();
            });
            $instance->proposal_works->each(function($sub_instance) {
                $sub_instance->delete();
            });
            $instance->proposal_software->each(function($sub_instance) {
                $sub_instance->delete();
            });
        });
    }


    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function proposal_platforms()
    {
        return $this->hasMany(ProposalVariantPlatform::class)->orderBy('sort');
    }

    public function proposal_scenarios()
    {
        return $this->hasMany(ProposalVariantScenario::class)->orderBy('sort');
    }

    public function proposal_works()
    {
        return $this->hasMany(ProposalVariantWork::class);
    }

    public function proposal_software()
    {
        return $this->hasMany(ProposalVariantSoftware::class);
    }


    public function hardware()
    {
        return $this->hasMany(Hardware::class)->orderBy('sort', 'asc');
    }

    public function extra_pays()
    {
        return $this->hasMany(ProposalVariantExtraPay::class)->orderBy('sort');
    }


    public function getFinalPaymentAttribute()
    {
        if($this->extra_pays->isEmpty()) {
            return $this->platform_cost_total + $this->soft_cost_total + $this->neuro_cost_total + $this->neuro_nds_cost_total + $this->soft_nds_cost_total;
        } else {
            return $this->extra_pays->last()->software_end;
        }
    }

    public function getFinalPrepayAttribute()
    {
        if($this->extra_pays->isEmpty()) {
            return $this->work_cost_total + $this->work_nds_cost_total;
        } else {
            return $this->extra_pays->last()->work_end;
        }
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'proposal';
    }

    public static function logLabel(): string
    {
        return 'Вариант';
    }

    /** Варианты сопоставляются по позиции: так сходятся и разные редакции КП */
    public function logKey(int $index): string
    {
        return $this->logPositionKey($index);
    }

    public function logTitle(?int $index = null): string
    {
        if ($index === null && !empty($this->proposal_id)) {
            $position = $this->proposal()->first()?->variants()->pluck('id')->search((int) $this->id);
            $index = $position === false || $position === null ? null : $position + 1;
        }

        return 'Вариант' . ($index ? ' ' . $index : '') . ' (' . $this->logPeriod() . ')';
    }

    /**
     * Период варианта человеческим языком: 1 год / 3 года / пилот 3 месяца / бессрочно
     *
     * @return string
     */
    public function logPeriod(): string
    {
        $value = (int) ($this->period_value ?: 1);

        return match ((string) $this->period_type) {
            'year' => tools()->num_rus($value, ['года', 'год', 'лет'], true),
            'pilot' => 'пилот ' . tools()->num_rus($value, ['месяца', 'месяц', 'месяцев'], true),
            'unlimited' => 'бессрочно',
            default => (string) $this->period_type,
        };
    }

    public static function logChildren(): array
    {
        return [
            'proposal_scenarios' => ProposalVariantScenario::class,
            'proposal_platforms' => ProposalVariantPlatform::class,
            'proposal_works' => ProposalVariantWork::class,
            'proposal_software' => ProposalVariantSoftware::class,
            'extra_pays' => ProposalVariantExtraPay::class,
            'hardware' => Hardware::class,
        ];
    }

    public static function logFields(): array
    {
        return [
            'is_main' => ['label' => 'Основной', 'type' => 'bool'],
            'period_type' => ['label' => 'Период', 'options' => ['year' => 'Годовая', 'pilot' => 'Пилот', 'unlimited' => 'Безлимит']],
            'period_value' => ['label' => 'Срок (лет / мес)'],
            'task' => ['label' => 'ТЗ', 'type' => 'html'],
            'cost_total' => ['label' => 'Итого', 'type' => 'money', 'derived' => true],
            'nds_cost_total' => ['label' => 'НДС итого', 'type' => 'money', 'derived' => true],
            'discount_partner_p' => ['label' => 'Скидка партнёра, %'],
            'platform_discount_partner_p' => ['label' => 'Платформа: скидка партнёра, %'],
            'platform_cost_total_base' => ['label' => 'Платформа: база', 'type' => 'money', 'derived' => true],
            'platform_discount_customer' => ['label' => 'Платформа: со скидкой заказчика', 'type' => 'money', 'derived' => true],
            'platform_discount_partner' => ['label' => 'Платформа: скидка партнёра', 'type' => 'money', 'derived' => true],
            'platform_cost_total' => ['label' => 'Платформа: итого', 'type' => 'money', 'derived' => true],
            'platform_nds_cost_total' => ['label' => 'Платформа: НДС', 'type' => 'money', 'derived' => true],
            'neuro_discount_partner_p' => ['label' => 'Нейросервисы: скидка партнёра, %'],
            'neuro_cost_total_base' => ['label' => 'Нейросервисы: база', 'type' => 'money', 'derived' => true],
            'neuro_discount_customer' => ['label' => 'Нейросервисы: со скидкой заказчика', 'type' => 'money', 'derived' => true],
            'neuro_discount_partner' => ['label' => 'Нейросервисы: скидка партнёра', 'type' => 'money', 'derived' => true],
            'neuro_cost_total' => ['label' => 'Нейросервисы: итого', 'type' => 'money', 'derived' => true],
            'neuro_nds_cost_total' => ['label' => 'Нейросервисы: НДС', 'type' => 'money', 'derived' => true],
            'soft_discount_partner_p' => ['label' => 'ПО: скидка партнёра, %'],
            'soft_cost_total_base' => ['label' => 'ПО: база', 'type' => 'money', 'derived' => true],
            'soft_discount_customer' => ['label' => 'ПО: скидка заказчика', 'type' => 'money', 'derived' => true],
            'soft_discount_partner' => ['label' => 'ПО: скидка партнёра', 'type' => 'money', 'derived' => true],
            'soft_cost_total' => ['label' => 'ПО: итого', 'type' => 'money', 'derived' => true],
            'soft_nds_cost_total' => ['label' => 'ПО: НДС', 'type' => 'money', 'derived' => true],
            'work_cost_total_base' => ['label' => 'Работы: база', 'type' => 'money', 'derived' => true],
            'work_discount_customer' => ['label' => 'Работы: скидка заказчика', 'type' => 'money', 'derived' => true],
            'work_discount_partner' => ['label' => 'Работы: скидка партнёра', 'type' => 'money', 'derived' => true],
            'work_cost_total' => ['label' => 'Работы: итого', 'type' => 'money', 'derived' => true],
            'work_nds_cost_total' => ['label' => 'Работы: НДС', 'type' => 'money', 'derived' => true],
        ];
    }
}
