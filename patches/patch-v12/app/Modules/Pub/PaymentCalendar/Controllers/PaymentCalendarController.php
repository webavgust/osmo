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

        // Статус выбран руками — показываем строго его. Ничего не выбрано —
        // отбор по умолчанию: спецификации в работе плюс уже оплаченные платежи.
        $strict = $request->has('spec_status');
        $spec_status = $strict
            ? PaymentCalendarService::listParam($request->input('spec_status'), false)
            : PaymentCalendarService::SPEC_STATUS_DEFAULT;

        $params = [
            'year' => $year,
            'month' => (int) $request->input('month') ?: null,
            // показатели ведут на выборку за все годы: просрочка прошлых лет
            // и платежи без дат в отбор по году не попадают
            'all_years' => $request->boolean('all_years'),
            'state' => PaymentCalendarService::listParam($request->input('state'), false),
            'age' => PaymentCalendarService::listParam($request->input('age'), false),
            'spec_status' => $spec_status,
            'spec_status_strict' => $strict,
            'q' => trim((string) $request->input('q')),
            'partner' => PaymentCalendarService::listParam($request->input('partner')),
            'company' => PaymentCalendarService::listParam($request->input('company')),
            'spec' => (int) $request->input('spec') ?: null,
        ];

        // показатели и разбивка по месяцам считаются по всем платежам,
        // а не по отфильтрованному году и состоянию
        $scope = [
            'q' => $params['q'],
            'partner' => $params['partner'],
            'company' => $params['company'],
            'spec_status' => $params['spec_status'],
            'spec_status_strict' => $params['spec_status_strict'],
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
            'spec_statuses' => PaymentCalendarService::specStatuses(),
            'ages' => PaymentCalendarService::ages(),
            'chips' => static::chips($params),
            'partners' => PaymentCalendarService::partners(),
            'companies' => PaymentCalendarService::companies(),
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

        foreach ($params['state'] as $state) {
            $ret[] = [
                'key' => 'state',
                'value' => $state,
                'label' => PaymentCalendarService::states()[$state]['label'] ?? $state,
            ];
        }

        foreach ($params['age'] as $age) {
            $ret[] = [
                'key' => 'age',
                'value' => $age,
                'label' => 'просрочка ' . (PaymentCalendarService::ages()[$age]['label'] ?? $age),
            ];
        }

        // статус по умолчанию тоже показываем: иначе непонятно, почему части
        // платежей нет на экране
        foreach ($params['spec_status'] as $status) {
            if ($status === 'all') continue;

            $ret[] = [
                'key' => 'spec_status',
                'value' => $status,
                'label' => ($params['spec_status_strict'] ? 'только спец.: ' : 'спец.: ')
                    . (PaymentCalendarService::specStatuses()[$status] ?? $status)
                    . ($params['spec_status_strict'] ? '' : ' + оплаченные'),
            ];
        }

        if (!empty($params['all_years'])) {
            $ret[] = ['key' => 'all_years', 'value' => 1, 'label' => 'за все годы'];
        }

        if (!empty($params['month'])) {
            $ret[] = [
                'key' => 'month',
                'value' => $params['month'],
                'label' => \Illuminate\Support\Carbon::create($params['year'], $params['month'], 1)
                    ->locale('ru')->isoFormat('MMMM YYYY'),
            ];
        }

        foreach (Company::whereIn('id', $params['company'])->get(['id', 'name']) as $company) {
            $ret[] = ['key' => 'company', 'value' => $company->id, 'label' => 'компания: ' . $company->name];
        }

        foreach (Partner::whereIn('id', $params['partner'])->get(['id', 'name']) as $partner) {
            $ret[] = ['key' => 'partner', 'value' => $partner->id, 'label' => 'партнёр: ' . $partner->name];
        }

        if (!empty($params['spec'])) {
            $ret[] = ['key' => 'spec', 'value' => $params['spec'], 'label' => 'одна спецификация'];
        }

        if (!empty($params['q'])) {
            $ret[] = ['key' => 'q', 'value' => $params['q'], 'label' => 'поиск: ' . $params['q']];
        }

        return $ret;
    }
}
