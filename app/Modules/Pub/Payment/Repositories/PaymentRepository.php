<?php

namespace App\Modules\Pub\Payment\Repositories;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;

class PaymentRepository
{

    public static function create(ContractSpecification $spec, array $data)
    {
        $spec->payments()->delete();

        foreach($data as $payment) {
            $date_plan = !empty($payment['date_plan']) ? Carbon::createFromFormat('Y-m-d', $payment['date_plan']) : null;
            $date_fact = !empty($payment['date_fact']) ? Carbon::createFromFormat('Y-m-d', $payment['date_fact']) : null;
            $delay = !empty($date_plan) && !empty($date_fact) && $date_fact->greaterThan($date_plan) ? $date_plan->diffInDays($date_fact) : null;

            if(!empty($date_fact) && empty($payment['amount_fact'])) $payment['amount_fact'] = $payment['amount_plan'];

            Payment::make([
                'is_unknown' => $payment['is_unknown'] ?? false,
                'date_plan' => $date_plan,
                'date_fact' => $date_fact,
                'delay' => $delay,
                'amount_plan' => $payment['amount_plan'],
                'amount_fact' => $payment['amount_fact'],
            ])
            ->contract_specification()->associate($spec)
            ->user()->associate(User::findOrFail($payment['user_id']))
            ->save();
        }
    }
}
