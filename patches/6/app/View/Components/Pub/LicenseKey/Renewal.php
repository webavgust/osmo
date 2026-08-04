<?php

namespace App\View\Components\Pub\LicenseKey;

use App\Modules\Pub\LicenseKey\Services\LicenseRenewalService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Виджет «Продление лицензий» на дашборде.
 *
 * <x-pub.license-key.renewal />
 */
class Renewal extends Component
{
    public function render(): View
    {
        return view('components.pub.license_key.renewal', [
            'data' => LicenseRenewalService::summary(),
        ]);
    }
}
