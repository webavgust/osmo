<?php

namespace App\Modules\Pub\Payment\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends ModuleModel
{
    use HasLogger;

    public $timestamps = false;
    protected $fillable = ['date_plan', 'date_fact', 'delay', 'amount_plan', 'amount_fact', 'is_unknown'];
    protected $casts = ['date_plan' => 'datetime', 'date_fact' => 'datetime', 'is_unknown' => 'bool'];

    public function contract_specification()
    {
        return $this->belongsTo(ContractSpecification::class);
    }

    public function user()
    {
        // withTrashed: имя не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class)->withTrashed();
    }
    public function getStatusAttribute()
    {
        if(!empty($this->date_fact)) {
            if(empty($this->date_plan) || ($this->date_plan->greaterThan($this->date_fact) || $this->date_plan->isSameDay($this->date_fact))) {
                $status = PaymentStatus::SUCCESS;
            } else {
                $status = PaymentStatus::EXPIRED;
            }
        } else {
            if($this->date_plan?->isFuture() ?? false) {
                $status =  PaymentStatus::WAITING;
            } else {
                $status =  PaymentStatus::DELAYED;
            }
        }

        return $status->data();
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'contract_specification';
    }

    public static function logLabel(): string
    {
        return 'Оплата';
    }

    /** Оплаты пересоздаются целиком при каждом сохранении — сопоставление по позиции */
    public function logKey(int $index): string
    {
        return $this->logPositionKey($index);
    }

    public function logTitle(?int $index = null): string
    {
        $date = $this->date_plan?->format('d.m.Y') ?? $this->date_fact?->format('d.m.Y') ?? 'без даты';
        $amount = $this->amount_plan ?? $this->amount_fact;

        return 'Оплата ' . $date . ($amount !== null ? ' · ' . tools()->cost_normalize((float) $amount, '.', false, ' ', false, 2) : '');
    }

    public static function logIgnore(): array
    {
        return ['delay'];
    }

    public static function logFields(): array
    {
        return [
            'date_plan' => ['label' => 'Дата план', 'type' => 'date'],
            'amount_plan' => ['label' => 'Сумма план', 'type' => 'money'],
            'date_fact' => ['label' => 'Дата факт', 'type' => 'date'],
            'amount_fact' => ['label' => 'Сумма факт', 'type' => 'money'],
            'user_id' => ['label' => 'Менеджер', 'relation' => 'user', 'title' => 'full_name'],
            'is_unknown' => ['label' => 'Неизвестный платёж', 'type' => 'bool'],
        ];
    }
}
