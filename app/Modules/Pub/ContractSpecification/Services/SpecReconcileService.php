<?php

namespace App\Modules\Pub\ContractSpecification\Services;

use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationProposal;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Сверка суммы спецификации, графика платежей и прикреплённых КП.
 *
 * Три числа должны сходиться: сумма спецификации, сумма плановых платежей по
 * ней и сумма КП, из которого спецификация выросла. Расходятся они обычно
 * не по злому умыслу — платёж забыли завести, спецификацию подписали в другой
 * редакции КП, — но узнаётся об этом сейчас только в момент сдачи отчётности.
 *
 * Отменённые спецификации не сверяем: у них расхождение — норма.
 *
 * patch v49: пустая сумма спецификации — не расхождение (сумма = плановые платежи,
 * ContractSpecification::amount_total); платежи сверяются с суммой, только если она
 * задана явно. КП сравнивается с суммой всех спецификаций, делящих его (proposalGroup).
 */
class SpecReconcileService
{
    /**
     * Расхождение до этой суммы — округление, а не ошибка.
     * По умолчанию; рабочее значение — consts.spec_reconcile_tolerance (читать через tolerance())
     */
    public const TOLERANCE = 1.0;

    /**
     * Расхождение выше этой доли считается грубым.
     * По умолчанию; рабочее значение — consts.spec_reconcile_hard_share (читать через hardShare())
     */
    public const HARD_SHARE = 0.05;

    /**
     * Допуск расхождения в единицах валюты спецификации
     * (consts.spec_reconcile_tolerance, по умолчанию TOLERANCE)
     *
     * @return float
     */
    public static function tolerance(): float
    {
        return max(0.0, Constant::float('spec_reconcile_tolerance', static::TOLERANCE));
    }

    /**
     * Доля расхождения, выше которой оно грубое (consts.spec_reconcile_hard_share, по умолчанию HARD_SHARE)
     *
     * @return float
     */
    public static function hardShare(): float
    {
        return max(0.0, Constant::float('spec_reconcile_hard_share', static::HARD_SHARE));
    }

    /**
     * Сверка одной спецификации
     *
     * @param ContractSpecification $spec
     * @return array [
     *     'skip' => bool, 'ok' => bool, 'hard' => bool,
     *     'amount' => float (сумма спецификации: amount либо плановые платежи), 'amount_set' => bool,
     *     'payments' => float, 'proposals' => float|null,
     *     'specs_total' => float|null (сумма спецификаций, с которой сравнивалось КП),
     *     'shared' => array<int,string> (спецификации, делящие КП, если их больше одной),
     *     'diff_payments' => float, 'diff_proposals' => float|null,
     *     'reasons' => array<string,string>,
     * ]
     */
    public static function check(ContractSpecification $spec): array
    {
        $payments = (float) $spec->payments->sum('amount_plan');

        $ret = [
            'skip' => (string) $spec->status === ContractSpecificationStatus::CANCELED->value,
            'ok' => true,
            'hard' => false,
            // patch v49: сумма спецификации — amount, а если он пуст, плановые платежи
            'amount' => ContractSpecification::amountTotal($spec->amount, $payments),
            // сумма задана явно — только тогда есть что сверять с платежами
            'amount_set' => (float) $spec->amount > 0,
            'payments' => $payments,
            'payments_count' => $spec->payments->count(),
            'proposals' => null,
            // сумма спецификаций, с которой сравнивалась сумма КП (patch v49)
            'specs_total' => null,
            // спецификации, делящие КП, id => название; пусто — КП только у этой
            'shared' => [],
            'diff_payments' => 0.0,
            'diff_proposals' => null,
            'reasons' => [],
        ];

        if ($ret['skip']) return $ret;

        $tolerance = static::tolerance();
        $symbol = $spec->currency->symbol ?? '';

        // patch v49: пустая сумма — это не расхождение, сумма по определению равна платежам
        if ($ret['amount_set']) {
            $ret['diff_payments'] = $ret['payments'] - $ret['amount'];

            if ($ret['payments_count'] === 0) {
                $ret['reasons']['no_payments'] = 'График платежей пуст: спецификация на '
                    . tools()->cost_normalize(round($ret['amount'])) . ' ' . $symbol . ' не разложена по платежам';
            } elseif (abs($ret['diff_payments']) > $tolerance) {
                $ret['reasons']['payments'] = 'Платежи расходятся со спецификацией на '
                    . ($ret['diff_payments'] > 0 ? '+' : '−')
                    . tools()->cost_normalize(round(abs($ret['diff_payments']))) . ' ' . $symbol;
            }
        }

        // patch v49: одно КП бывает разложено на несколько спецификаций (лицензии + услуги
        // в разных договорах) — КП сравниваем с суммой всей связной группы.
        // Валюты должны совпадать: приводить КП к валюте спецификации по сегодняшнему курсу нечестно
        $group = static::proposalGroup($spec);
        $proposals = static::proposalsSumByGroups($group['groups'], $spec->currency_slug);
        $specs_total = static::specsTotal($group['specs'], $spec->currency_slug);

        if ($proposals !== null && $specs_total !== null) {
            $ret['proposals'] = $proposals;
            $ret['specs_total'] = $specs_total;
            $ret['diff_proposals'] = $proposals - $specs_total;

            if ($group['specs']->count() > 1) {
                $ret['shared'] = static::shortNames($group['specs']);

                // группа не сходится, но сама спецификация со своими КП сходится — значит,
                // КП лишний раз прикреплено к соседней (обычно к спецификации прошлого
                // периода при продлении); ошибка там, а не здесь
                $own = static::proposalsSum($spec);
                if (abs($ret['diff_proposals']) > $tolerance && $own !== null
                    && abs($own - $ret['amount']) <= $tolerance) {
                    $ret['proposals'] = $own;
                    $ret['specs_total'] = $ret['amount'];
                    $ret['diff_proposals'] = $own - $ret['amount'];
                    unset($ret['shared']);
                }
            }
        }

        if ($ret['diff_proposals'] !== null && abs($ret['diff_proposals']) > $tolerance && $ret['specs_total'] > 0) {
            $ret['reasons']['proposals'] = 'Сумма прикреплённых КП расходится'
                . (!empty($ret['shared'])
                    ? ' с суммой ' . count($ret['shared']) . ' спецификаций (' . implode(', ', $ret['shared']) . ')'
                    : '')
                . ' на '
                . ($ret['diff_proposals'] > 0 ? '+' : '−')
                . tools()->cost_normalize(round(abs($ret['diff_proposals']))) . ' ' . $symbol;
        }

        $ret['ok'] = empty($ret['reasons']);

        $base = max($ret['amount'], $ret['payments']);
        $ret['hard'] = !$ret['ok'] && $base > 0
            && abs($ret['diff_payments']) / $base > static::hardShare();

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
        return static::proposalsSumByGroups(
            SpecProposalService::links($spec)->pluck('proposal_group')->all(),
            $spec->currency_slug
        );
    }

    /**
     * Сумма КП (последние редакции) по группам КП в заданной валюте (patch v49).
     * null — групп нет либо валюта какого-то КП не совпадает.
     *
     * @param array $groups proposal_group
     * @param string|null $currency_slug валюта спецификации
     * @return float|null
     */
    public static function proposalsSumByGroups(array $groups, ?string $currency_slug): ?float
    {
        if (empty($groups)) return null;

        $proposals = Proposal::query()
            ->latestIteration()
            ->whereIn('group', $groups)
            ->with(['variants'])
            ->get();
        if ($proposals->isEmpty()) return null;

        $spec_currency = CurrencyService::slug($currency_slug);
        $sum = 0.0;

        foreach ($proposals as $proposal) {
            if (CurrencyService::slug($proposal->currency_slug) !== $spec_currency) return null;

            $variant = $proposal->variants->sortBy('id')->last();
            $sum += (float) ($variant->cost_total ?? 0);
        }

        return $sum;
    }

    /**
     * Связная группа «спецификации ↔ КП» вокруг спецификации (patch v49).
     *
     * Спецификации связаны, если делят хотя бы одно КП (contract_specification_proposals);
     * связь транзитивна. Отменённые спецификации в группу не входят и группу не связывают.
     * Спецификации — с загруженными платежами и договором.
     *
     * @param ContractSpecification $spec
     * @return array ['specs' => Collection<ContractSpecification>, 'groups' => array<string>]
     */
    public static function proposalGroup(ContractSpecification $spec): array
    {
        $spec_ids = [(int) $spec->id];
        $groups = [];
        $front = [(int) $spec->id];
        $canceled = ContractSpecificationStatus::CANCELED->value;

        while (!empty($front)) {
            $new_groups = ContractSpecificationProposal::whereIn('contract_specification_id', $front)
                ->whereNotIn('proposal_group', $groups ?: [''])
                ->distinct()
                ->pluck('proposal_group')
                ->all();
            if (empty($new_groups)) break;
            $groups = array_merge($groups, $new_groups);

            $candidates = ContractSpecificationProposal::whereIn('proposal_group', $new_groups)
                ->whereNotIn('contract_specification_id', $spec_ids)
                ->distinct()
                ->pluck('contract_specification_id')
                ->all();

            $front = empty($candidates) ? [] : ContractSpecification::whereIn('id', $candidates)
                ->where(fn($query) => $query->whereNull('status')->orWhere('status', '!=', $canceled))
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();
            $spec_ids = array_merge($spec_ids, $front);
        }

        $others = count($spec_ids) > 1
            ? ContractSpecification::with(['payments', 'contract'])
                ->whereIn('id', array_diff($spec_ids, [(int) $spec->id]))
                ->orderBy('id')
                ->get()
            : collect();

        return [
            'specs' => collect([$spec])->concat($others)->values(),
            'groups' => $groups,
        ];
    }

    /**
     * Сумма (amount_total) спецификаций группы; null — если валюты разные
     *
     * @param Collection $specs
     * @param string|null $currency_slug
     * @return float|null
     */
    protected static function specsTotal(Collection $specs, ?string $currency_slug): ?float
    {
        $currency = CurrencyService::slug($currency_slug);
        $sum = 0.0;

        foreach ($specs as $item) {
            if (CurrencyService::slug($item->currency_slug) !== $currency) return null;
            $sum += $item->amount_total;
        }

        return $sum;
    }

    /**
     * Короткие названия спецификаций группы; одинаковые названия различаем номером договора
     *
     * @param Collection $specs
     * @return array id => название
     */
    protected static function shortNames(Collection $specs): array
    {
        $names = $specs->mapWithKeys(fn($item) => [$item->id => Str::limit((string) ($item->name ?: '#' . $item->id), 30)]);
        $counts = array_count_values($names->all());

        return $names->map(function ($name, $id) use ($counts, $specs) {
            if ($counts[$name] < 2) return $name;

            $number = $specs->firstWhere('id', $id)?->contract?->number;

            return $name . ' — ' . ($number ? 'дог. ' . $number : '#' . $id);
        })->all();
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
