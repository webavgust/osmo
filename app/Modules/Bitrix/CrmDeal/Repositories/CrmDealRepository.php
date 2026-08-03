<?php

namespace App\Modules\Bitrix\CrmDeal\Repositories;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Models\CrmDealIssues;
use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Bitrix\Dashboard\Services\DashboardFilterService;
use Illuminate\Support\Facades\DB;

class CrmDealRepository
{
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
                switch ($field) {
                    case "assigned_by":
                        $rows = $rows->whereIn('assigned_by', $value);
                        break;
                    case "stage_name":
                        $rows = $rows->whereIn('stage_name', $value);
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
