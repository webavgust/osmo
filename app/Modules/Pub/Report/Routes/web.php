<?php

Route::group(['prefix' => 'report'], function () {
    Route::get('/popular_scenario', [\App\Modules\Pub\Report\Controllers\ReportController::class, 'popular_scenario']);

    Route::get('/payments', [\App\Modules\Pub\Report\Controllers\ReportController::class, 'payments']);

    Route::get('/specs', [\App\Modules\Pub\Report\Controllers\ReportController::class, 'specs'])->name('report.specs');

    Route::get('/scenarios_specs', [\App\Modules\Pub\Report\Controllers\ReportController::class, 'scenarios_specs']);
    Route::get('/scenarios', [\App\Modules\Pub\Report\Controllers\ReportController::class, 'scenarios']);

    Route::get('/license_keys', [\App\Modules\Pub\Report\Controllers\ReportController::class, 'license_keys']);

    Route::get('/china', [\App\Modules\Pub\Report\Controllers\ReportController::class, 'china']);

    Route::group(['prefix' => 'download'], function() {
        Route::post('/china_1', [\App\Modules\Pub\Report\Controllers\ReportDownloadController::class, 'china1'])->name('report-download.china1');

        Route::get('/specs/{mode}', [\App\Modules\Pub\Report\Controllers\ReportDownloadController::class, 'specs'])->name('report-download.specs');
        Route::get('/payments/{mode}', [\App\Modules\Pub\Report\Controllers\ReportDownloadController::class, 'payments'])->name('report-download.payments');
        Route::get('/tbl_industry_name/{mode}', [\App\Modules\Pub\Report\Controllers\ReportDownloadController::class, 'tbl_industry_name'])->name('report-download.tbl_industry_name');
        Route::get('/tbl_country_status__quarter/{mode}', [\App\Modules\Pub\Report\Controllers\ReportDownloadController::class, 'tbl_country_status__quarter'])->name('report-download.tbl_country_status__quarter');
        Route::get('/tbl_manager_status__quarter/{mode}', [\App\Modules\Pub\Report\Controllers\ReportDownloadController::class, 'tbl_manager_status__quarter'])->name('report-download.tbl_manager_status__quarter');

        Route::get('/tbl_country_status__month/{mode}', [\App\Modules\Pub\Report\Controllers\ReportDownloadController::class, 'tbl_country_status__month'])->name('report-download.tbl_country_status__month');
        Route::get('/tbl_status_country__month/{mode}', [\App\Modules\Pub\Report\Controllers\ReportDownloadController::class, 'tbl_status_country__month'])->name('report-download.tbl_status_country__month');
    });

});
