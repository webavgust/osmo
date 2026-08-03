<?php


Route::group(['prefix' => 'license_keys'], function () {
    Route::group([], function () {
        // boxes
        Route::get('/box/add/{company}', [\App\Modules\Pub\LicenseKey\Controllers\LicenseKeyController::class, 'box_add'])->name('license-keys.box_add');
        Route::get('/box/edit/{license_key}', [\App\Modules\Pub\LicenseKey\Controllers\LicenseKeyController::class, 'box_edit'])->name('license-keys.box_edit');
    });
});
