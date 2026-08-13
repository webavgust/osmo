<?php

namespace App\Modules\Pub\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Analytics\Services\DiscountAnalysisService;
use App\Modules\Pub\Analytics\Services\PartnerScoringService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AnalyticsController extends Controller
{
    /**
     * Анализ скидок
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function discounts(Request $request)
    {
        $years = DiscountAnalysisService::years();

        // без года выборка тянет все КП со всеми позициями — по умолчанию последний год
        $params = [
            'year' => $request->has('year')
                ? ((int) $request->input('year') ?: null)
                : ($years[0] ?? null),
            'partner' => (int) $request->input('partner') ?: null,
            'status' => $request->input('status'),
            'only_alert' => $request->boolean('only_alert'),
            'q' => trim((string) $request->input('q')),
        ];

        $rows = DiscountAnalysisService::rows($params);

        return View::make('pub.analytics.discounts', [
            'title' => 'Анализ скидок',
            'params' => $params,
            'rows' => $rows,
            'totals' => DiscountAnalysisService::totals($rows),
            'blocks' => DiscountAnalysisService::BLOCKS,
            'years' => $years,
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'statuses' => ProposalStatus::getDecorated(),
        ]);
    }

    /**
     * Скоринг партнёров
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function partners(Request $request)
    {
        $years = DiscountAnalysisService::years();

        $params = [
            'year' => $request->has('year')
                ? ((int) $request->input('year') ?: null)
                : ($years[0] ?? null),
            'grade' => $request->input('grade'),
            'q' => trim((string) $request->input('q')),
        ];

        $rows = PartnerScoringService::rows($params);

        return View::make('pub.analytics.partners', [
            'title' => 'Скоринг партнёров',
            'params' => $params,
            'rows' => $rows,
            'totals' => PartnerScoringService::totals($rows),
            'years' => $years,
            'grades' => PartnerGrade::cases(),
        ]);
    }
}
