<?php

Route::group(['prefix' => 'license_keys', 'middleware' => ['ajax.api']], function () {
    Route::put('/create/{company}', [\App\Modules\Pub\LicenseKey\Controllers\Api\ApiLicenseKeyController::class, 'store'])->name('api.license-keys.create');
    Route::post('/update/{key}', [\App\Modules\Pub\LicenseKey\Controllers\Api\ApiLicenseKeyController::class, 'update'])->name('api.license-keys.update');

    Route::delete('/delete/{key}', [\App\Modules\Pub\LicenseKey\Controllers\Api\ApiLicenseKeyController::class, 'delete'])->name('api.license-keys.delete');
});
