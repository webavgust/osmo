<?php

use App\Modules\Pub\Analytics\Controllers\AnalyticsController;

Route::group(['prefix' => 'analytics'], function () {
    Route::get('/discounts', [AnalyticsController::class, 'discounts'])->name('analytics.discounts');
    Route::get('/partners', [AnalyticsController::class, 'partners'])->name('analytics.partners');
    Route::get('/licenses', [AnalyticsController::class, 'licenses'])->name('analytics.licenses');

    // boxes
    Route::get('/box/partner/{partner}/{tab?}', [AnalyticsController::class, 'box_partner'])->name('analytics.box_partner');
});
