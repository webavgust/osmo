<?php

namespace App\Modules\Bitrix\CrmDeal\Repositories;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Models\CrmDealIssues;
use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Bitrix\Dashboard\Services\DashboardFilterService;
use App\Modules\Pub\Constant\Models\Constant;
use Illuminate\Support\Facades\DB;

class CrmDealRepository
{
    /**
     * Поле компании «Страна» в Битрикс24.
     * По умолчанию; рабочее значение — consts.bitrix_uf_country (читать через ufCountry())
     */
    public const UF_COUNTRY = 'uf_crm_1719404976291';

    /**
     * Поле crm_company_uf со страной компании (consts.bitrix_uf_country, по умолчанию UF_COUNTRY).
     * Имя подставляется в SQL как колонка, поэтому допускаются только латиница, цифры и `_`.
     *
     * @return string
     */
    public static function ufCountry(): string
    {
        $field = trim((string) Constant::value('bitrix_uf_country', static::UF_COUNTRY));

        return preg_match('/^[a-z0-9_]+$/i', $field) ? $field : static::UF_COUNTRY;
    }

    public static function getAll()
    {
        return CrmDeal::all();
    }

    /**
     * Сделки с полями crm_deal_uf, отобранные фильтром страницы воронки
     *
     * @param bool $apply_filter false — тот же запрос без фильтра страницы (patch v30: рабочий стол)
     * @return \Illuminate\Support\Collection
     */
    public static function getFiltered(bool $apply_filter = true)
    {
        // patch v30: без фильтра страницы — для виджетов рабочего стола
        $filter = $apply_filter ? DashboardFilterService::getFilter() : [];
        $builder = CrmDeal::query();
        $builder->join('crm_deal_uf', 'crm_deal.id', '=', 'crm_deal_uf.deal_id');

        $rows = $builder->get();
        $country_field = static::ufCountry();

        if(!empty($filter)) {
            foreach ($filter as $field => $value) {
                if(empty($value)) continue;

                switch ($field) {
                    case "assigned_by":
                        $rows = $rows->whereIn('assigned_by', $value);
                        break;
                    case "stage_name":
                        $rows = $rows->whereIn('stage_name', $value);
                        break;

                    // вероятность хранится числом, из формы приходит строкой
                    case "probability":
                        $value = collect($value)->map(fn($item) => (string) (int) $item)->all();
                        $rows = $rows->filter(
                            fn($deal) => in_array((string) (int) $deal->probability, $value, true)
                        );
                        break;

                    // страна получения средств — поле компании, а не сделки
                    case "country":
                        // ускорение 23.09: компании и их поля одним запросом, а не по запросу на сделку
                        $rows->load('crm_company.companyUf');
                        $rows = $rows->filter(function ($deal) use ($value, $country_field) {
                            $country = $deal->crm_company?->companyUf?->{$country_field} ?? "Неизвестно";
                            return in_array($country, $value, true);
                        });
                        break;
                }
            }
        }

        // приводим в порядок поля
        if($rows->isNotEmpty()) {
            $rows->map(function ($item) use ($filter) {
                $item->uf_crm_1718977752420 = tools()->parseNumberFromString($item->uf_crm_1718977752420);
                $item->uf_crm_1718977763677 = tools()->parseNumberFromString($item->uf_crm_1718977763677);
                $item->uf_crm_1723814702122 = tools()->parseNumberFromString($item->uf_crm_1723814702122);
                $item->uf_crm_1725019324602 = tools()->parseNumberFromString($item->uf_crm_1725019324602);
                return $item;
            });
        }

        return $rows;
    }

    /**
     * Значения для выпадающих списков фильтра дашборда
     *
     * @return array
     */
    public static function getFilterOptions(): array
    {
        $deals = CrmDeal::with('crm_company.companyUf')->get();
        $country_field = static::ufCountry();

        return [
            // patch v40: менеджеров, исключённых из воронки, в её фильтре нет
            'assigned_by' => $deals->pluck('assigned_by')->filter()->unique()
                ->reject(fn($item) => DashboardDataService::isExcluded($item))->sort()->values(),
            'stage_name' => $deals->pluck('stage_name')->filter()->unique()->sort()->values(),

            'probability' => $deals->pluck('probability')
                ->filter(fn($item) => $item !== null && $item !== '')
                ->map(fn($item) => (string) (int) $item)
                ->unique()
                ->sort(fn($a, $b) => (int) $a <=> (int) $b)
                ->values(),

            'country' => $deals
                ->map(fn($deal) => $deal->crm_company?->companyUf?->{$country_field} ?? "Неизвестно")
                ->filter()
                ->unique()
                ->sort()
                ->values(),
        ];
    }

    /**
     * Сделки активных стадий, не прошедшие проверки CrmDealIssues
     *
     * @param bool $exclude без сделок DashboardDataService::EXCLUDED_MANAGERS — для страницы воронки (patch v40);
     *                      рабочий стол вызывает без него и видит всех
     * @return \Illuminate\Support\Collection
     */
    public static function getDealWithIssues(bool $exclude = false)
    {
        $dealIssues = CrmDealIssues::cases();

        $deals = CrmDeal::all();
        // patch v40: для страницы воронки — без сделок DashboardDataService::EXCLUDED_MANAGERS
        if ($exclude)
            $deals = $deals->reject(fn($deal) => DashboardDataService::isExcluded($deal->assigned_by));
        $deals = DashboardDataService::scopeStatuses($deals);

        // ускорение 23.09: проверки читают dealUf и customer каждой сделки — грузим
        // связи двумя запросами на все сделки, а не двумя запросами на каждую
        $deals->load(['dealUf', 'customer']);

        $deals = $deals->map(function ($deal) use (&$dealIssues) {
            $issues = collect();

            foreach($dealIssues as $dealIssue) {
                if(!$dealIssue->validate($deal))
                    $issues[] = $dealIssue;
            }

            if($issues->count() > 0)
                $deal->setAttribute('issues', $issues);

            return $deal;
        });

        return $deals->whereNotNull('issues');
    }
}
