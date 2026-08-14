<?php

namespace App\Modules\Pub\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Analytics\Services\DiscountAnalysisService;
use App\Modules\Pub\Analytics\Services\LicenseRegistryService;
use App\Modules\Pub\Analytics\Services\PartnerScoringService;
use App\Modules\Pub\Analytics\Services\PartnerStatsService;
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
        $years = PartnerScoringService::years();

        $params = [
            'year' => $request->has('year')
                ? ((int) $request->input('year') ?: null)
                : ($years[0] ?? null),
            'grade' => $request->input('grade'),
            'q' => trim((string) $request->input('q')),
        ];

        $rows = PartnerScoringService::rows($params);

        // график места в рейтинге по годам — в подсказке к баллу
        $history = $rows->mapWithKeys(fn($row) => [
            (int) $row['partner']->id => PartnerScoringService::history((int) $row['partner']->id),
        ]);

        return View::make('pub.analytics.partners', [
            'title' => 'Скоринг партнёров',
            'params' => $params,
            'rows' => $rows,
            'totals' => PartnerScoringService::totals($rows),
            'years' => $years,
            'grades' => PartnerGrade::cases(),
            'legend' => PartnerScoringService::grades(),
            'history' => $history,
        ]);
    }

    /**
     * Реестр лицензий со сроками истечения
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function licenses(Request $request)
    {
        $params = [
            'horizon' => $request->has('horizon') ? $request->input('horizon') : 90,
            'partner' => (int) $request->input('partner') ?: null,
            'only_active' => !$request->has('horizon') || $request->boolean('only_active'),
            'q' => trim((string) $request->input('q')),
        ];

        $rows = LicenseRegistryService::rows($params);

        return View::make('pub.analytics.licenses', [
            'title' => 'Реестр лицензий',
            'params' => $params,
            'rows' => $rows,
            'totals' => LicenseRegistryService::totals($rows),
            'horizons' => LicenseRegistryService::HORIZONS,
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Попап детальной статистики партнёра.
     *
     * Один попап с закладками: разбивка по годам и расшифровки трёх колонок —
     * объёма, договоров и платежей. Из таблицы каждая цифра открывает свою.
     *
     * @param Request $request
     * @param Partner $partner
     * @param string $tab stats|volume|contracts|payments
     * @return \Illuminate\Contracts\View\View
     */
    public function box_partner(Request $request, Partner $partner, string $tab = 'stats')
    {
        $year = (int) $request->input('year') ?: null;

        return View::make('pub.analytics.boxes.partner', [
            'title' => 'Статистика: ' . $partner->name,
            'partner' => $partner,
            'tab' => in_array($tab, ['stats', 'volume', 'contracts', 'payments']) ? $tab : 'stats',
            'year' => $year,
            'years' => PartnerStatsService::years((int) $partner->id),
            'volume' => PartnerStatsService::volume((int) $partner->id, $year),
            'contracts' => PartnerStatsService::contracts((int) $partner->id, $year),
            'payments' => PartnerStatsService::payments((int) $partner->id, $year),
            'links' => PartnerStatsService::links((int) $partner->id, $year),
        ]);
    }
}
