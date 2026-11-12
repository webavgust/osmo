<?php

namespace App\Modules\Pub\ContractSpecification\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Services\SpecProposalService;
use App\Modules\Pub\ContractSpecification\Services\SpecReconcileService;
use App\Modules\Pub\ContractSpecificationScenario\Models\ContractSpecificationScenario;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfiguration;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractSpecification extends ModuleModel
{
    use HasLogger;

    public $timestamps = false;
    protected $fillable = ['name', 'date_create', 'amount', 'status', 'is_signed', 'report_data', 'currency'];
    protected $casts = ['is_signed' => 'boolean', 'report_data' => 'json', 'date_create' => 'date'];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function contract_specification_scenarios()
    {
        return $this->hasMany(ContractSpecificationScenario::class);
    }

    /**
     * Ключи, у которых есть период по этой спецификации (patch v49): продлённый ключ
     * виден и в старой спецификации (со старым сроком), и в новой (с новым).
     * Даты периода — pivot->active_from / pivot->active_to (строки Y-m-d).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function license_keys()
    {
        return $this->belongsToMany(LicenseKey::class, 'license_key_periods', 'contract_specification_id', 'license_key_id')
            ->withPivot(['id', 'active_from', 'active_to']);
    }

    public function project_configurations()
    {
        return $this->hasMany(ProjectConfiguration::class);
    }

    /**
     * Привязки КП к спецификации (patch v16)
     */
    public function proposal_links()
    {
        return $this->hasMany(ContractSpecificationProposal::class, 'contract_specification_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_slug', 'slug');
    }

    public function canDelete()
    {
        // patch v28: раньше is_admin(), но «админом» был каждый (право super_user у всех).
        // Признак админа стал доступом в админ-панель, а удаление спецификаций владелец
        // оставил всем пользователям портала
        return auth()->check();
    }

    public function getAmountPastAttribute()
    {
        return $this->payments()->whereNotNull('date_fact')->sum('amount_fact');
    }
    public function getAmountFutureAttribute()
    {
        return $this->payments()->whereNull('date_fact')->sum('amount_plan');
    }
    public function getAmountAllAttribute()
    {
        return $this->payments()->sum('amount_plan');
    }

    /**
     * Сумма спецификации (patch v49): поле amount, если оно заполнено (> 0),
     * иначе сумма плановых платежей.
     *
     * Поле суммы в формах давно закомментировано, у новых спецификаций amount = NULL,
     * а сумма по задумке — это график платежей. Единый источник для показа и итогов.
     * Если платежи уже загружены (with('payments')) — запроса не будет.
     *
     * @return float
     */
    public function getAmountTotalAttribute(): float
    {
        return static::amountTotal(
            $this->amount,
            fn() => $this->relationLoaded('payments')
                ? $this->payments->sum('amount_plan')
                : $this->payments()->sum('amount_plan')
        );
    }

    /**
     * Сумма спецификации по сырым значениям — для выборок через DB::table (patch v49)
     *
     * @param mixed $amount значение поля amount
     * @param mixed $payments сумма плановых платежей или замыкание, которое её вернёт
     * @return float
     */
    public static function amountTotal($amount, $payments): float
    {
        if ((float) $amount > 0) return (float) $amount;

        return (float) (is_callable($payments) ? $payments() : $payments);
    }

    /**
     * SQL-выражение суммы спецификации (patch v49): amount, если он > 0,
     * иначе сумма плановых платежей. Для выборок через DB::table.
     *
     * @param string $alias псевдоним таблицы contract_specifications в запросе
     * @return string
     */
    public static function amountTotalSql(string $alias = 's'): string
    {
        return "(CASE WHEN {$alias}.amount > 0 THEN {$alias}.amount ELSE "
            . "(SELECT COALESCE(SUM(pt.amount_plan), 0) FROM payments pt WHERE pt.contract_specification_id = {$alias}.id) END)";
    }

    public function getNameFullAttribute()
    {
        $contract = $this->contract;
        $type = ContractType::from($contract->type);

        return$type->data()['label'] . ' (' . $contract->number . ')  ->  ' . $this->name;
    }

    /**
     * Прикреплённые КП — последние редакции
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAttachedProposalsAttribute()
    {
        return SpecProposalService::attached($this);
    }

    /**
     * Сверка суммы спецификации с платежами и КП
     *
     * @return array
     */
    public function getReconcileAttribute()
    {
        return SpecReconcileService::check($this);
    }

    /**
     * Дата спецификации, а если своей нет — дата рамочного договора
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function getDateAttribute()
    {
        return $this->date_create ?? $this->contract?->date;
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'contract';
    }

    public static function logLabel(): string
    {
        return 'Спецификация';
    }

    public function logTitle(?int $index = null): string
    {
        $name = static::logText($this->name, 80);

        return 'Спецификация «' . ($name !== '' ? $name : '#' . $this->id) . '»';
    }

    public static function logChildren(): array
    {
        return [
            'payments' => Payment::class,
            'contract_specification_scenarios' => ContractSpecificationScenario::class,
            'proposal_links' => ContractSpecificationProposal::class,
            'license_keys' => LicenseKey::class,
        ];
    }

    public static function logIgnore(): array
    {
        return ['report_data', 'project_configuration_id'];
    }

    public static function logFields(): array
    {
        return [
            'name' => ['label' => 'Название'],
            'status' => ['label' => 'Статус', 'enum' => ContractSpecificationStatus::class],
            'company_id' => ['label' => 'Компания', 'relation' => 'company'],
            'currency_slug' => ['label' => 'Валюта', 'relation' => 'currency', 'title' => 'name'],
            'date_create' => ['label' => 'Дата', 'type' => 'date'],
            'is_signed' => ['label' => 'Подписана', 'type' => 'bool'],
            'amount' => ['label' => 'Сумма', 'type' => 'money'],
            'closed_at' => ['label' => 'Дата закрытия', 'type' => 'date'],
        ];
    }
}
