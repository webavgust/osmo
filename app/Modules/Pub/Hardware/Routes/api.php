<?php

Route::group(['prefix' => 'hardware', 'middleware' => ['ajax.api']], function () {
    Route::put('/store', [\App\Modules\Pub\Hardware\Controllers\Api\ApiHardwareController::class, 'store'])->name('api.hardware.store');
    Route::post('/update/{hardware}', [\App\Modules\Pub\Hardware\Controllers\Api\ApiHardwareController::class, 'update'])->name('api.hardware.update');

    Route::delete('/delete/{proposal}/{iteration}', [\App\Modules\Pub\Hardware\Controllers\Api\ApiHardwareController::class, 'delete'])->name('api.hardware.delete');
});
