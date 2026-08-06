<?php

use App\Modules\Pub\CrmMonitor\Controllers\CrmMonitorController;

Route::group(['prefix' => 'crm-monitor'], function () {
    Route::get('/', [CrmMonitorController::class, 'index'])->name('crm_monitor.index');
});
