<?php

Route::group(['prefix' => 'report', 'middleware' => ['ajax.api']], function () {

    Route::group(['prefix' => 'payment'], function() {
        Route::post('/filter', [\App\Modules\Pub\Report\Controllers\Api\ApiReportPaymentController::class, 'filter'])->name('api.report.payment.filter');
        Route::get('/filter', [\App\Modules\Pub\Report\Controllers\Api\ApiReportPaymentController::class, 'filterRemove'])->name('api.report.payment.filter_remove');
    });

    Route::group(['prefix' => 'license_keys'], function() {
        Route::post('/filter', [\App\Modules\Pub\Report\Controllers\Api\ApiReportLicenseKeysController::class, 'filter'])->name('api.report.license_keys.filter');
        Route::get('/filter', [\App\Modules\Pub\Report\Controllers\Api\ApiReportLicenseKeysController::class, 'filterRemove'])->name('api.report.license_keys.filter_remove');
    });


    Route::group(['prefix' => 'specs'], function() {
       Route::post('active', [\App\Modules\Pub\Report\Controllers\Api\ApiReportSpecsController::class, 'active'])->name('api.report.specs.active');
    });
});
