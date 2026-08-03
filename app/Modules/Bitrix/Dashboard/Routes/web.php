<?php
use \App\Modules\Bitrix\Dashboard\Controllers\DashboardController;

Route::group(['prefix' => 'dashboard', 'middleware' => []], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // boxes
    Route::group(['prefix' => 'box'], function() {
        Route::get('/currency', [\App\Modules\Bitrix\Dashboard\Controllers\DashboardBoxController::class, 'currency'])->name('dashboard.box.currency');
        Route::get('/filter', [\App\Modules\Bitrix\Dashboard\Controllers\DashboardBoxController::class, 'filter'])->name('dashboard.box.filter');
        Route::get('/table/{mode}', [\App\Modules\Bitrix\Dashboard\Controllers\DashboardBoxController::class, 'table'])->name('dashboard.box.table');
        Route::get('/industry_name/{row}/{column}', [\App\Modules\Bitrix\Dashboard\Controllers\DashboardBoxController::class, 'industry_name'])->name('dashboard.box.industry_name');
        Route::get('/country_status_quarter/{r1}/{r2}/{column}', [\App\Modules\Bitrix\Dashboard\Controllers\DashboardBoxController::class, 'country_status_quarter'])->name('dashboard.box.country_status_quarter');
        Route::get('/manager_status_quarter/{r1}/{r2}/{column}', [\App\Modules\Bitrix\Dashboard\Controllers\DashboardBoxController::class, 'manager_status_quarter'])->name('dashboard.box.manager_status_quarter');
        Route::get('/country_status_month/{r1}/{r2}/{column}', [\App\Modules\Bitrix\Dashboard\Controllers\DashboardBoxController::class, 'country_status_month'])->name('dashboard.box.country_status_month');
        Route::get('/status_country_month/{r1}/{r2}/{column}', [\App\Modules\Bitrix\Dashboard\Controllers\DashboardBoxController::class, 'status_country_month'])->name('dashboard.box.status_country_month');
    });

    // components
    Route::group(['prefix' => 'components'], function() {   });
});

