<?php

namespace App\Modules\Pub\LicenseKey\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecificationScenario\Models\ContractSpecificationScenario;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseKey extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['active', 'key', 'active_from', 'active_to'];
    protected $casts = ['active' => 'bool', 'active_from' => 'date', 'active_to' => 'date'];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function specification()
    {
        return $this->belongsTo(ContractSpecification::class, 'contract_specification_id');
    }

    /**
     * Периоды действия ключа: продление = новый период по новой спецификации (patch v49)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function periods()
    {
        return $this->hasMany(LicenseKeyPeriod::class)->orderBy('active_to')->orderBy('id');
    }

    /**
     * Текущий период — последний по дате окончания
     *
     * @return LicenseKeyPeriod|null
     */
    public function currentPeriod(): ?LicenseKeyPeriod
    {
        return $this->periods()->get()->last();
    }

    /**
     * Свести поля ключа (спецификация, даты) к текущему периоду.
     * Вызывается после любого изменения периодов: все потребители
     * (виджеты, отчёты, реестр) читают поля ключа и видят продлённый срок.
     *
     * @return $this
     */
    public function syncFromPeriods(): static
    {
        $period = $this->currentPeriod();
        if (empty($period)) return $this;

        $this->contract_specification_id = $period->contract_specification_id;
        $this->active_from = $period->active_from;
        $this->active_to = $period->active_to;

        if ($this->isDirty()) $this->save();

        return $this;
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'specification';
    }

    public static function logLabel(): string
    {
        return 'Лицензионный ключ';
    }

    public static function logChildren(): array
    {
        return [
            'periods' => LicenseKeyPeriod::class,
        ];
    }

    public function logTitle(?int $index = null): string
    {
        $key = trim((string) $this->key);

        return 'Ключ ' . ($key !== '' ? $key : '#' . $this->id);
    }

    public static function logFields(): array
    {
        return [
            'active' => ['label' => 'Активный', 'type' => 'bool'],
            'key' => ['label' => 'Ключ'],
            'active_from' => ['label' => 'Дата действия, с', 'type' => 'date'],
            'active_to' => ['label' => 'Дата действия, по', 'type' => 'date'],
            'company_id' => ['label' => 'Компания', 'relation' => 'company'],
            'count' => ['label' => 'Кол-во'],
            'comment' => ['label' => 'Комментарий'],
        ];
    }
}
