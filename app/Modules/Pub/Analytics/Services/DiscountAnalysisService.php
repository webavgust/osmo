<?php

namespace App\Modules\Pub\Analytics\Services;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Анализ скидок по коммерческим предложениям.
 *
 * Скидка в КП двухступенчатая и живёт по позициям: сначала процент заказчику,
 * потом процент партнёру от уже уменьшенной цены. Считается это в четырёх
 * блоках — платформа, ПО, нейросервисы, работы, — и в каждом проценты лежат
 * по-своему: у платформы, ПО и нейросервисов процент партнёра общий на весь
 * вариант, у работ — свой на каждой позиции.
 *
 * Сервис повторяет формулу из карточки КП один в один, чтобы цифры сходились,
 * и собирает её по всем КП разом.
 *
 * Сверяется ПОСЛЕДНИЙ СОЗДАННЫЙ вариант последней редакции — тот, что ушёл
 * заказчику.
 */
class DiscountAnalysisService
{
    /**
     * Блоки расчёта: где лежат позиции и откуда берутся проценты.
     *
     * discount  — поле процента скидки заказчику на позиции
     * partner_p — поле процента скидки партнёру на варианте
     *             (null — процент лежит на самой позиции, в discount_partner)
     */
    public const BLOCKS = [
        'platform' => [
            'label' => 'Платформа',
            'relation' => 'proposal_platforms',
            'discount' => 'discount',
            'partner_p' => 'platform_discount_partner_p',
        ],
        'soft' => [
            'label' => 'ПО',
            'relation' => 'proposal_software',
            'discount' => 'discount_customer',
            'partner_p' => 'soft_discount_partner_p',
        ],
        'neuro' => [
            'label' => 'Нейросервисы',
            'relation' => 'proposal_scenarios',
            'discount' => 'discount',
            'partner_p' => 'neuro_discount_partner_p',
        ],
        'work' => [
            'label' => 'Работы',
            'relation' => 'proposal_works',
            'discount' => 'discount_customer',
            'partner_p' => null,
        ],
    ];

    /** На сколько процентных пунктов скидка должна превысить средний уровень грейда, чтобы попасть в отбор */
    public const GRADE_ALERT_PP = 5;

    /** Совокупная скидка выше этого процента считается исключением при любом грейде */
    public const HARD_LIMIT_P = 40;

    /**
     * КП со скидками
     *
     * @param array $params [
     *     'year' => int|null — год отправки КП,
     *     'partner' => int|null,
     *     'status' => string|null,
     *     'only_alert' => bool — только выделенные,
     *     'q' => string|null,
     * ]
     * @return Collection
     */
    public static function rows(array $params = []): Collection
    {
        $builder = Proposal::query()
            ->latestIteration()
            ->with([
                'partner', 'company',
                'variants.proposal_platforms', 'variants.proposal_software',
                'variants.proposal_scenarios', 'variants.proposal_works',
            ]);

        if (!empty($params['year'])) {
            $builder->whereYear('sended_at', (int) $params['year']);
        }

        if (!empty($params['status'])) {
            $builder->where('status', $params['status']);
        }

        if (!empty($params['q'])) {
            $like = '%' . trim($params['q']) . '%';
            $builder->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('number', 'like', $like)
                    ->orWhereHas('company', fn($builder) => $builder->where('name', 'like', $like));
            });
        }

        $rows = $builder->get()
            ->map(fn($proposal) => static::proposal($proposal))
            ->filter(fn($row) => $row['list'] > 0)
            ->values();

        // партнёр отбирается уже по собранным строкам: у части КП он приходит
        // через компанию, а не своим полем
        if (!empty($params['partner'])) {
            $rows = $rows->filter(fn($row) => (int) ($row['partner']->id ?? 0) === (int) $params['partner'])->values();
        }

        // средний уровень по грейду считается по всей выборке, до отбора «только выделенные»
        $averages = static::gradeAverages($rows);
        $rows = $rows->map(fn($row) => static::flag($row, $averages));

        if (!empty($params['only_alert'])) {
            $rows = $rows->filter(fn($row) => !empty($row['alerts']))->values();
        }

        return $rows->sortByDesc('total_p')->values();
    }

    /**
     * Скидки одного КП по последнему созданному варианту
     *
     * @param Proposal $proposal
     * @return array
     */
    public static function proposal(Proposal $proposal): array
    {
        $variant = $proposal->variants->sortBy('id')->last();
        $blocks = static::variant($variant);

        $list = array_sum(array_column($blocks, 'list'));
        $customer = array_sum(array_column($blocks, 'customer'));
        $partner = array_sum(array_column($blocks, 'partner'));
        $currency = CurrencyService::slug($proposal->currency_slug);

        $grade = PartnerGrade::tryFrom((string) ($proposal->partner->grade ?? ''))?->data();

        return [
            'proposal' => $proposal,
            'variant' => $variant,
            'partner' => $proposal->partner,
            'company' => $proposal->company,
            'grade' => $grade,
            'grade_key' => (string) ($proposal->partner->grade ?? ''),
            'currency' => $currency,
            'status' => ProposalStatus::tryFrom((string) $proposal->status),
            'blocks' => $blocks,
            'list' => $list,
            'customer' => $customer,
            'partner_amount' => $partner,
            'total' => $list - $customer - $partner,
            'customer_p' => $list > 0 ? $customer / $list * 100 : 0,
            // процент партнёра берётся от цены, уже уменьшенной для заказчика
            'partner_p' => ($list - $customer) > 0 ? $partner / ($list - $customer) * 100 : 0,
            'total_p' => $list > 0 ? ($customer + $partner) / $list * 100 : 0,
            'alerts' => [],
        ];
    }

    /**
     * Скидки варианта по блокам
     *
     * @param mixed $variant
     * @return array
     */
    public static function variant($variant): array
    {
        $ret = [];

        foreach (static::BLOCKS as $code => $block) {
            $list = $customer = $partner = 0.0;
            $items = $variant?->{$block['relation']} ?? collect();

            foreach ($items as $item) {
                $count = (float) ($item->count ?? 0);
                if ($count <= 0) continue;

                $price = (float) ($item->cost ?? 0);

                $pct_customer = (float) ($item->{$block['discount']} ?? 0);
                $amount_customer = $pct_customer > 0 ? $price / 100 * $pct_customer : 0.0;

                // у работ процент партнёра лежит на позиции, у остальных блоков — на варианте
                $pct_partner = $block['partner_p']
                    ? (float) ($variant->{$block['partner_p']} ?? 0)
                    : (float) ($item->discount_partner ?? 0);

                $amount_partner = $pct_partner > 0 ? ($price - $amount_customer) / 100 * $pct_partner : 0.0;

                $list += $price * $count;
                $customer += $amount_customer * $count;
                $partner += $amount_partner * $count;
            }

            $ret[$code] = [
                'label' => $block['label'],
                'list' => $list,
                'customer' => $customer,
                'partner' => $partner,
                'total' => $list - $customer - $partner,
                'total_p' => $list > 0 ? ($customer + $partner) / $list * 100 : 0,
            ];
        }

        return $ret;
    }

    /**
     * Средняя совокупная скидка по каждому грейду.
     * Средневзвешенная по прайсу: одно мелкое КП с большой скидкой не должно
     * задирать планку целого грейда.
     *
     * @param Collection $rows
     * @return array
     */
    public static function gradeAverages(Collection $rows): array
    {
        $ret = [];

        foreach ($rows->groupBy('grade_key') as $key => $group) {
            $list = (float) $group->sum('list');
            $discount = (float) $group->sum(fn($row) => $row['customer'] + $row['partner_amount']);

            $ret[(string) $key] = $list > 0 ? $discount / $list * 100 : 0;
        }

        return $ret;
    }

    /**
     * Пометки к строке: чем эта скидка выделяется
     *
     * @param array $row
     * @param array $averages
     * @return array
     */
    public static function flag(array $row, array $averages): array
    {
        $average = $averages[$row['grade_key']] ?? 0;
        $row['grade_average'] = $average;
        $row['grade_diff'] = $row['total_p'] - $average;

        if ($row['total_p'] > static::HARD_LIMIT_P) {
            $row['alerts']['limit'] = 'Совокупная скидка ' . round($row['total_p'], 1)
                . '% — выше потолка ' . static::HARD_LIMIT_P . '%';
        }

        if ($average > 0 && $row['grade_diff'] > static::GRADE_ALERT_PP) {
            $row['alerts']['grade'] = 'На ' . round($row['grade_diff'], 1)
                . ' п.п. выше среднего по грейду (' . round($average, 1) . '%)';
        }

        // скидка заказчику есть, а партнёрской нет (или наоборот) — обычно недозаполнение
        if ($row['customer_p'] > 0 && $row['partner_p'] <= 0) {
            $row['alerts']['no_partner'] = 'Скидка заказчику есть, партнёрской нет';
        }

        return $row;
    }

    /**
     * Итоги по выборке. Суммы приводятся к рублям — в выборке разные валюты.
     *
     * @param Collection $rows
     * @return array
     */
    public static function totals(Collection $rows): array
    {
        $list = $customer = $partner = 0.0;
        $unknown = 0;

        foreach ($rows as $row) {
            $rate = CurrencyService::getConvertRateForDate(now(), $row['currency'], null);

            if ($rate === null) {
                $rate = 1;
                $unknown++;
            }

            $list += $row['list'] * $rate;
            $customer += $row['customer'] * $rate;
            $partner += $row['partner_amount'] * $rate;
        }

        return [
            'count' => $rows->count(),
            'list' => $list,
            'customer' => $customer,
            'partner' => $partner,
            'total' => $list - $customer - $partner,
            'customer_p' => $list > 0 ? $customer / $list * 100 : 0,
            'partner_p' => ($list - $customer) > 0 ? $partner / ($list - $customer) * 100 : 0,
            'total_p' => $list > 0 ? ($customer + $partner) / $list * 100 : 0,
            'alerts' => $rows->filter(fn($row) => !empty($row['alerts']))->count(),
            'rate_unknown' => $unknown,
        ];
    }

    /**
     * Годы, за которые есть КП
     *
     * @return array
     */
    public static function years(): array
    {
        return collect(DB::table('proposals')
            ->selectRaw('YEAR(sended_at) as year')
            ->whereNotNull('sended_at')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year'))
            ->filter()
            ->map(fn($year) => (int) $year)
            ->values()
            ->all();
    }
}
