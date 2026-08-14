<?php

namespace App\Modules\Pub\ContractSpecification\Services;

use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\Currency\Services\CurrencyService;
use Illuminate\Support\Collection;

/**
 * Сверка суммы спецификации, графика платежей и прикреплённых КП.
 *
 * Три числа должны сходиться: сумма спецификации, сумма плановых платежей по
 * ней и сумма КП, из которого спецификация выросла. Расходятся они обычно
 * не по злому умыслу — платёж забыли завести, спецификацию подписали в другой
 * редакции КП, — но узнаётся об этом сейчас только в момент сдачи отчётности.
 *
 * Отменённые спецификации не сверяем: у них расхождение — норма.
 */
class SpecReconcileService
{
    /** Расхождение до этой суммы — округление, а не ошибка */
    public const TOLERANCE = 1.0;

    /** Расхождение выше этой доли считается грубым */
    public const HARD_SHARE = 0.05;

    /**
     * Сверка одной спецификации
     *
     * @param ContractSpecification $spec
     * @return array [
     *     'skip' => bool, 'ok' => bool, 'hard' => bool,
     *     'amount' => float, 'payments' => float, 'proposals' => float|null,
     *     'diff_payments' => float, 'diff_proposals' => float|null,
     *     'reasons' => array<string,string>,
     * ]
     */
    public static function check(ContractSpecification $spec): array
    {
        $ret = [
            'skip' => (string) $spec->status === ContractSpecificationStatus::CANCELED->value,
            'ok' => true,
            'hard' => false,
            'amount' => (float) $spec->amount,
            'payments' => (float) $spec->payments->sum('amount_plan'),
            'payments_count' => $spec->payments->count(),
            'proposals' => null,
            'diff_payments' => 0.0,
            'diff_proposals' => null,
            'reasons' => [],
        ];

        if ($ret['skip']) return $ret;

        $ret['diff_payments'] = $ret['payments'] - $ret['amount'];

        // сумма прикреплённых КП — только если валюты совпадают: приводить КП
        // к валюте спецификации по сегодняшнему курсу здесь нечестно
        $proposals = static::proposalsSum($spec);
        if ($proposals !== null) {
            $ret['proposals'] = $proposals;
            $ret['diff_proposals'] = $proposals - $ret['amount'];
        }

        $symbol = $spec->currency->symbol ?? '';

        if ($ret['amount'] <= 0 && $ret['payments'] > 0) {
            $ret['reasons']['no_amount'] = 'Сумма спецификации не заполнена, а платежи на '
                . tools()->cost_normalize(round($ret['payments'])) . ' ' . $symbol . ' есть';
        } elseif ($ret['amount'] > 0 && $ret['payments_count'] === 0) {
            $ret['reasons']['no_payments'] = 'График платежей пуст: спецификация на '
                . tools()->cost_normalize(round($ret['amount'])) . ' ' . $symbol . ' не разложена по платежам';
        } elseif (abs($ret['diff_payments']) > static::TOLERANCE) {
            $ret['reasons']['payments'] = 'Платежи расходятся со спецификацией на '
                . ($ret['diff_payments'] > 0 ? '+' : '−')
                . tools()->cost_normalize(round(abs($ret['diff_payments']))) . ' ' . $symbol;
        }

        if ($ret['diff_proposals'] !== null && abs($ret['diff_proposals']) > static::TOLERANCE && $ret['amount'] > 0) {
            $ret['reasons']['proposals'] = 'Сумма прикреплённых КП расходится на '
                . ($ret['diff_proposals'] > 0 ? '+' : '−')
                . tools()->cost_normalize(round(abs($ret['diff_proposals']))) . ' ' . $symbol;
        }

        $ret['ok'] = empty($ret['reasons']);

        $base = max($ret['amount'], $ret['payments']);
        $ret['hard'] = !$ret['ok'] && $base > 0
            && abs($ret['diff_payments']) / $base > static::HARD_SHARE;

        return $ret;
    }

    /**
     * Сумма прикреплённых КП в валюте спецификации.
     * null — прикреплённых КП нет либо валюты не совпадают.
     *
     * @param ContractSpecification $spec
     * @return float|null
     */
    public static function proposalsSum(ContractSpecification $spec): ?float
    {
        $proposals = SpecProposalService::attached($spec);
        if ($proposals->isEmpty()) return null;

        $spec_currency = CurrencyService::slug($spec->currency_slug);
        $sum = 0.0;

        foreach ($proposals as $proposal) {
            if (CurrencyService::slug($proposal->currency_slug) !== $spec_currency) return null;

            $variant = $proposal->variants->sortBy('id')->last();
            $sum += (float) ($variant->cost_total ?? 0);
        }

        return $sum;
    }

    /**
     * Сверка списка спецификаций
     *
     * @param Collection $specs
     * @return Collection ключ — id спецификации
     */
    public static function map(Collection $specs): Collection
    {
        return $specs->mapWithKeys(fn($spec) => [$spec->id => static::check($spec)]);
    }

    /**
     * Сколько спецификаций не сходится
     *
     * @param Collection $checks результат map()
     * @return int
     */
    public static function alerts(Collection $checks): int
    {
        return $checks->filter(fn($check) => empty($check['skip']) && empty($check['ok']))->count();
    }
}
