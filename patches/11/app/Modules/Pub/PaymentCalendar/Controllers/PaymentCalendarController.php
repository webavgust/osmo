<?php

namespace App\Modules\Pub\PaymentCalendar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\PaymentCalendar\Services\PaymentCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class PaymentCalendarController extends Controller
{
    /**
     * Платёжный календарь
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $years = PaymentCalendarService::years();
        $year = (int) $request->input('year', in_array((int) now()->year, $years) ? now()->year : ($years[0] ?? now()->year));

        $params = [
            'year' => $year,
            'month' => (int) $request->input('month') ?: null,
            // показатели ведут на выборку за все годы: просрочка прошлых лет
            // и платежи без дат в отбор по году не попадают
            'all_years' => $request->boolean('all_years'),
            'state' => $request->input('state'),
            'age' => $request->input('age'),
            'q' => trim((string) $request->input('q')),
            'partner' => (int) $request->input('partner') ?: null,
            'company' => (int) $request->input('company') ?: null,
            'spec' => (int) $request->input('spec') ?: null,
            'archive' => $request->boolean('archive'),
        ];

        // показатели и разбивка по месяцам считаются по всем платежам,
        // а не по отфильтрованному году
        $scope = [
            'q' => $params['q'],
            'partner' => $params['partner'],
            'company' => $params['company'],
            'archive' => $params['archive'],
        ];

        $all = PaymentCalendarService::rows($scope);
        $rows = PaymentCalendarService::rows($params);

        $months = PaymentCalendarService::months($all, $year, $params['month']);

        return View::make('pub.payment_calendar.index', [
            'title' => 'Платёжный календарь',
            'year' => $year,
            'years' => $years,
            'params' => $params,
            'rows' => $rows,
            'summary' => PaymentCalendarService::summary($all),
            'months' => $months,
            'year_total' => PaymentCalendarService::yearTotal($months),
            'states' => PaymentCalendarService::states(),
            'ages' => PaymentCalendarService::ages(),
            'chips' => static::chips($params),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'companies' => Company::whereIn('id', $all->pluck('company_id')->filter()->unique())
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Человеческое описание того, что сейчас отфильтровано.
     * Нужно, чтобы после клика по цифре было видно, что за выборка на экране.
     *
     * @param array $params
     * @return array
     */
    protected static function chips(array $params): array
    {
        $ret = [];

        if (!empty($params['state'])) {
            $ret[] = [
                'key' => 'state',
                'label' => PaymentCalendarService::states()[$params['state']]['label'] ?? $params['state'],
            ];
        }

        if (!empty($params['age'])) {
            $ret[] = [
                'key' => 'age',
                'label' => 'просрочка ' . (PaymentCalendarService::ages()[$params['age']]['label'] ?? $params['age']),
            ];
        }

        if (!empty($params['all_years'])) {
            $ret[] = ['key' => 'all_years', 'label' => 'за все годы'];
        }

        if (!empty($params['month'])) {
            $ret[] = [
                'key' => 'month',
                'label' => \Illuminate\Support\Carbon::create($params['year'], $params['month'], 1)
                    ->locale('ru')->isoFormat('MMMM YYYY'),
            ];
        }

        if (!empty($params['company'])) {
            $ret[] = [
                'key' => 'company',
                'label' => 'компания: ' . (Company::find($params['company'])?->name ?: $params['company']),
            ];
        }

        if (!empty($params['partner'])) {
            $ret[] = [
                'key' => 'partner',
                'label' => 'партнёр: ' . (Partner::find($params['partner'])?->name ?: $params['partner']),
            ];
        }

        if (!empty($params['spec'])) {
            $ret[] = ['key' => 'spec', 'label' => 'одна спецификация'];
        }

        if (!empty($params['q'])) {
            $ret[] = ['key' => 'q', 'label' => 'поиск: ' . $params['q']];
        }

        if (!empty($params['archive'])) {
            $ret[] = ['key' => 'archive', 'label' => 'с архивными договорами'];
        }

        return $ret;
    }
}
