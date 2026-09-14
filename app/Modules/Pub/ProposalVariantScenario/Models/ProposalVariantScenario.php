<?php

namespace App\Modules\Pub\ProposalVariantScenario\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVariantScenario extends ModuleModel
{
    use HasLogger;

    public $timestamps = false;
        protected $fillable = ['sort', 'real_name', 'mnemonic_name', 'comment', 'cb_process', 'cb_nds', 'nds', 'cost', 'count', 'cost_total', 'discount', 'cost_discount', 'default_cost_year', 'default_cost_unlimited'];
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
            $instance->neuroservices()->sync([]);
        });
    }


    public function proposal_variant()
    {
        return $this->belongsTo(ProposalVariant::class);
    }
    public function scenario()
    {
        return $this->belongsTo(Scenario::class);
    }

    public function neuroservices()
    {
        return $this->belongsToMany(Neuroservice::class)->withPivot(['cost']);
    }

    public function getCostSavedAttribute()
    {
        $ret = ['year' => 0, 'unlimited' => 0];
        $this->neuroservices->each(function($neuro) use (&$ret) {
            $ret['year'] += $neuro->cost['year'];
            $ret['unlimited'] += $neuro->cost['unlimited'] ?? neuro->cost['year'];
        });

        return $ret;
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
        return 'Нейросервис';
    }

    /** Строки пересоздаются при каждом сохранении — сопоставление по сценарию */
    public function logKey(int $index): string
    {
        return (string) $this->scenario_id;
    }

    public function logTitle(?int $index = null): string
    {
        $name = trim((string) $this->real_name);
        if ($name === '') $name = trim((string) ($this->scenario?->name ?? ''));

        return 'Нейросервис «' . ($name !== '' ? static::logText($name, 80) : '#' . $this->scenario_id) . '»';
    }

    public static function logIgnore(): array
    {
        return ['sort'];
    }

    public static function logFields(): array
    {
        return [
            'scenario_id' => ['label' => 'Сценарий', 'relation' => 'scenario'],
            'real_name' => ['label' => 'Название'],
            'mnemonic_name' => ['label' => 'Мнемоника'],
            'comment' => ['label' => 'Примечание', 'type' => 'html'],
            'cb_process' => ['label' => 'В расчёте', 'type' => 'bool'],
            'cost' => ['label' => 'Цена', 'type' => 'money'],
            'discount' => ['label' => 'Скидка заказчика, %'],
            'cost_discount' => ['label' => 'Цена итог', 'type' => 'money', 'derived' => true],
            'count' => ['label' => 'Лицензии'],
            'cb_nds' => ['label' => 'НДС', 'type' => 'bool'],
            'nds' => ['label' => 'Сумма НДС', 'type' => 'money', 'derived' => true],
            'default_cost_year' => ['label' => 'Цена по прайсу (год)', 'type' => 'money'],
            'default_cost_unlimited' => ['label' => 'Цена по прайсу (бессрочно)', 'type' => 'money'],
            'cost_rules' => ['label' => 'Правила цены', 'type' => 'json'],
        ];
    }
}
