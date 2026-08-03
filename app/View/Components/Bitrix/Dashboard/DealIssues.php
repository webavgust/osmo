<?php

namespace App\View\Components\Bitrix\Dashboard;

use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DealIssues extends Component
{
    public function render(): View
    {

        $deals_issues = CrmDealRepository::getDealWithIssues();

        return view('components.bitrix.dashboard.deal_issues', [
            'deals' => $deals_issues,
        ]);
    }
}
