<?php

use App\Modules\Bitrix\Sync\Controllers\Api\ApiSyncController;
use \App\Modules\Bitrix\Sync\Controllers\SyncController;

Route::middleware(['ajax.api'])->group(function() {
    Route::group(['prefix' => 'dashboard', 'middleware' => []], function () {
        Route::post('/set_currency', [\App\Modules\Bitrix\Dashboard\Controllers\Api\ApiDashboardController::class, 'set_currency'])->name('api.bitrix.dashboard.set_currency');
        Route::post('/set_filter', [\App\Modules\Bitrix\Dashboard\Controllers\Api\ApiDashboardController::class, 'set_filter'])->name('api.bitrix.dashboard.set_filter');
        Route::post('/remove_filter', [\App\Modules\Bitrix\Dashboard\Controllers\Api\ApiDashboardController::class, 'remove_filter'])->name('api.bitrix.dashboard.remove_filter');
    });
});
