<?php

namespace App\Modules\Bitrix\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Bitrix\Dashboard\Services\DealMovementExportService;
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
        $data = $this->tableData($request, $service);

        $this->breadcrumb_add(null, 'Движение сделок');

        return view('bitrix.dashboard.movement', array_merge($data, [
            'breadcrumbs' => $this->breadcrumb,
            // вкладка: table — плитки и таблицы, gantt — диаграмма Ганта
            'view' => $request->query('view') === 'gantt' ? 'gantt' : 'table',
            // диаграмма Ганта; ссылки на сделки — в Битрикс24, как в реестре сделок
            'gantt' => $service->gantt($data['year']),
            'deal_url' => CrmDealRegistryService::dealUrlTemplate(),
        ]));
    }

    /**
     * Выгрузка вкладки «Таблица» (patch v51): ?format=xlsx|pdf&year=&at=.
     * Данные те же, что на странице (tableData); менеджеры — только администратору.
     */
    public function exportTable(Request $request, DealMovementService $service, DealMovementExportService $export)
    {
        $data = $this->tableData($request, $service);

        return $request->query('format') === 'pdf'
            ? $export->pdf($data)
            : $export->xlsx($data);
    }

    /**
     * Выгрузка вкладки «Диаграмма» (patch v51), POST: format, year, at, range (year|all), start/end (Y-m-d),
     * ids — видимые сделки в порядке экрана, open — раскрытые, sort, conditions — подписи условий для шапки.
     * Сделки берутся из gantt($year) — того же набора, что на странице; неизвестные id отбрасываются.
     */
    public function exportGantt(Request $request, DealMovementService $service, DealMovementExportService $export)
    {
        $years = $service->years();
        $year = $this->tableYear($request, $years);
        $at = $request->input('at') === DealMovementService::AT_YEAR_END
            ? DealMovementService::AT_YEAR_END
            : DealMovementService::AT_NOW;

        $params = [
            'year' => $year,
            'at' => $at,
            'is_current_year' => $year === (int) Carbon::now()->year,
            'range' => $request->input('range') === 'all' ? 'all' : 'year',
            'start' => $this->dateParam($request->input('start')),
            'end' => $this->dateParam($request->input('end')),
            'ids' => $this->idsParam($request->input('ids')),
            'open' => $this->idsParam($request->input('open')),
            'sort' => (string) $request->input('sort', ''),
            'conditions' => mb_substr(trim((string) $request->input('conditions', '')), 0, 1000),
        ];

        $data = $export->ganttData($service->gantt($year), $params, CrmDealRegistryService::dealUrlTemplate());

        return $request->input('format') === 'pdf'
            ? $export->ganttPdf($data)
            : $export->ganttXlsx($data);
    }

    /**
     * Старый адрес /bitrix/dashboard/movement (отчёт был частью воронки) — 301 на /bitrix/movement с тем же query.
     */
    public function legacy(Request $request)
    {
        return redirect()->route('bitrix.movement', $request->query(), 301);
    }

    /**
     * Данные вкладки «Таблица»: год, срез, когорта, движения, менеджеры — одни для страницы и выгрузки.
     */
    private function tableData(Request $request, DealMovementService $service): array
    {
        $years = $service->years();
        $year = $this->tableYear($request, $years);

        $at = $request->query('at') === DealMovementService::AT_YEAR_END
            ? DealMovementService::AT_YEAR_END
            : DealMovementService::AT_NOW;

        $by_manager = is_admin();
        $cohort = $service->cohort($year, $at, $by_manager);
        $movements = $service->movements($year, $by_manager);

        return [
            'years' => $years,
            'year' => $year,
            'at' => $at,
            'is_current_year' => $year === (int) Carbon::now()->year,
            'cohort' => $cohort['total'],
            'movements' => $movements['total'],
            'managers' => $by_manager ? $this->managerRows($cohort['managers'], $movements['managers']) : [],
            'by_manager' => $by_manager,
        ];
    }

    /**
     * Год отчёта из ?year; неизвестный год — последний из доступных.
     */
    private function tableYear(Request $request, array $years): int
    {
        $year = (int) $request->query('year', Carbon::now()->year);

        return in_array($year, $years, true) ? $year : (int) end($years);
    }

    /**
     * Дата Y-m-d из запроса или null
     */
    private function dateParam($value): ?string
    {
        $value = (string) $value;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))
            ? $value
            : null;
    }

    /**
     * Список id сделок из строки «1,2,3» (или массива): только целые, без повторов, не больше 1000
     */
    private function idsParam($value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $ids = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if (ctype_digit($item) && (int) $item > 0) $ids[(int) $item] = (int) $item;
            if (count($ids) >= 1000) break;
        }

        return array_values($ids);
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
