<?php

use App\Modules\Pub\Analytics\Controllers\AnalyticsController;

Route::group(['prefix' => 'analytics'], function () {
    Route::get('/discounts', [AnalyticsController::class, 'discounts'])->name('analytics.discounts');
    Route::get('/partners', [AnalyticsController::class, 'partners'])->name('analytics.partners');
});
