<?php

use App\Modules\Bitrix\Sync\Controllers\Api\ApiSyncController;
use \App\Modules\Bitrix\Sync\Controllers\SyncController;

Route::middleware(['ajax.api'])->group(function() {
    Route::group(['prefix' => 'sync', 'middleware' => []], function () {
        Route::post('/refresh/{table}', [ApiSyncController::class, 'refresh'])->name('api.bitrix.sync.refresh');
    });
});
