<?php

namespace App\Modules\Pub\PaymentCalendar\Controllers;

use App\Http\Controllers\Controller;
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
            'state' => $request->input('state'),
            'q' => $request->input('q'),
            'partner' => $request->input('partner'),
        ];

        // показатели считаем по всем платежам, а не по отфильтрованному году:
        // просрочка прошлых лет тоже требует внимания
        $all = PaymentCalendarService::rows(['q' => $params['q'], 'partner' => $params['partner']]);
        $rows = PaymentCalendarService::rows($params);

        return View::make('pub.payment_calendar.index', [
            'title' => 'Платёжный календарь',
            'year' => $year,
            'years' => $years,
            'params' => $params,
            'rows' => $rows,
            'summary' => PaymentCalendarService::summary($all),
            'months' => PaymentCalendarService::months($all, $year),
            'states' => PaymentCalendarService::states(),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
