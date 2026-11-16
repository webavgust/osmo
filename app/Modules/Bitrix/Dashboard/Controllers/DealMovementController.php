<?php

namespace App\Modules\Bitrix\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Bitrix\Dashboard\Services\DealMovementService;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Отчёт «Движение сделок за год» (patch v50)
 */
class DealMovementController extends Controller
{
    use HasBreadcrumb;

    /**
     * Страница отчёта: ?year=2025&at=now|year_end&view=table|gantt.
     * Разбивка по менеджерам считается и показывается только администратору.
     */
    public function index(Request $request, DealMovementService $service)
    {
        $years = $service->years();
        $year = (int) $request->query('year', Carbon::now()->year);
        if (!in_array($year, $years, true)) $year = end($years);

        $at = $request->query('at') === DealMovementService::AT_YEAR_END
            ? DealMovementService::AT_YEAR_END
            : DealMovementService::AT_NOW;

        $by_manager = is_admin();
        $cohort = $service->cohort($year, $at, $by_manager);
        $movements = $service->movements($year, $by_manager);

        $this->breadcrumb_add(null, 'Движение сделок');

        return view('bitrix.dashboard.movement', [
            'breadcrumbs' => $this->breadcrumb,
            'years' => $years,
            'year' => $year,
            'at' => $at,
            'is_current_year' => $year === (int) Carbon::now()->year,
            'cohort' => $cohort['total'],
            'movements' => $movements['total'],
            'managers' => $by_manager ? $this->managerRows($cohort['managers'], $movements['managers']) : [],
            'by_manager' => $by_manager,
            // вкладка: table — плитки и таблицы, gantt — диаграмма Ганта
            'view' => $request->query('view') === 'gantt' ? 'gantt' : 'table',
            // диаграмма Ганта; ссылки на сделки — в Битрикс24, как в реестре сделок
            'gantt' => $service->gantt($year),
            'deal_url' => CrmDealRegistryService::dealUrlTemplate(),
        ]);
    }

    /**
     * Старый адрес /bitrix/dashboard/movement (отчёт был частью воронки) — 301 на /bitrix/movement с тем же query.
     */
    public function legacy(Request $request)
    {
        return redirect()->route('bitrix.movement', $request->query(), 301);
    }

    /**
     * Строки таблицы по менеджерам: когорта + движения; менеджеры только с движениями идут в конец.
     */
    private function managerRows(array $cohort, array $movements): array
    {
        $rows = [];

        foreach (array_unique(array_merge(array_keys($cohort), array_keys($movements))) as $name) {
            $rows[$name] = [
                'cohort' => $cohort[$name] ?? null,
                'movements' => $movements[$name] ?? null,
            ];
        }

        return $rows;
    }
}
