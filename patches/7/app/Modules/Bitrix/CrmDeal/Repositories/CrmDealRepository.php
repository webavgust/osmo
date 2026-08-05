<?php

namespace App\Modules\Bitrix\CrmDeal\Repositories;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Models\CrmDealIssues;
use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Bitrix\Dashboard\Services\DashboardFilterService;
use Illuminate\Support\Facades\DB;

class CrmDealRepository
{
    /** Поле компании «Страна» в Битрикс24 */
    public const UF_COUNTRY = 'uf_crm_1719404976291';

    public static function getAll()
    {
        return CrmDeal::all();
    }

    public static function getFiltered()
    {
        $filter = DashboardFilterService::getFilter();
        $builder = CrmDeal::query();
        $builder->join('crm_deal_uf', 'crm_deal.id', '=', 'crm_deal_uf.deal_id');

        $rows = $builder->get();

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
                        $rows = $rows->filter(function ($deal) use ($value) {
                            $country = $deal->crm_company?->companyUf?->{static::UF_COUNTRY} ?? "Неизвестно";
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

        return [
            'assigned_by' => $deals->pluck('assigned_by')->filter()->unique()->sort()->values(),
            'stage_name' => $deals->pluck('stage_name')->filter()->unique()->sort()->values(),

            'probability' => $deals->pluck('probability')
                ->filter(fn($item) => $item !== null && $item !== '')
                ->map(fn($item) => (string) (int) $item)
                ->unique()
                ->sort(fn($a, $b) => (int) $a <=> (int) $b)
                ->values(),

            'country' => $deals
                ->map(fn($deal) => $deal->crm_company?->companyUf?->{static::UF_COUNTRY} ?? "Неизвестно")
                ->filter()
                ->unique()
                ->sort()
                ->values(),
        ];
    }

    public static function getDealWithIssues()
    {
        $dealIssues = CrmDealIssues::cases();

        $deals = CrmDeal::all();
        $deals = DashboardDataService::scopeStatuses($deals);

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
