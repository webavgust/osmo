<?php

namespace App\Modules\Pub\LicenseKey\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\LicenseKey\Services\LicenseRenewalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class LicenseKeyBoxController extends Controller
{
    /**
     * Подробности по продлению лицензий
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function renewal(Request $request)
    {
        $days = (int) $request->input('days', 90);
        if (!in_array($days, LicenseRenewalService::HORIZONS)) {
            $days = max(LicenseRenewalService::HORIZONS);
        }

        return View::make('pub.license_key.boxes.renewal', [
            'title' => 'Продление лицензий',
            'days' => $days,
            'summary' => LicenseRenewalService::summary(),
            'keys' => LicenseRenewalService::expiring($days),
        ]);
    }
}
