<?php
use \App\Modules\Bitrix\Sync\Controllers\SyncController;

Route::group(['prefix' => 'sync', 'middleware' => []], function () {
    Route::get('/', [SyncController::class, 'index'])->name('sync.index');

    // boxes
    Route::group(['prefix' => 'box'], function() {
        Route::get('/refresh/{table}', [\App\Modules\Bitrix\Sync\Controllers\SyncBoxController::class, 'refresh'])->name('sync.box.sync');
    });
});

