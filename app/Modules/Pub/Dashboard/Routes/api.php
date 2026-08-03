<?php

Route::group(['prefix' => 'dashboard', 'middleware' => ['ajax.api', 'can:users_have_sub']], function () {
    Route::post('/set_user_mode', [\App\Modules\Pub\Dashboard\Controllers\Api\ApiDashboardController::class, 'set_user_mode'])->name('api.dashboard.set_user_mode');
    Route::post('/set_sub_user_mode', [\App\Modules\Pub\Dashboard\Controllers\Api\ApiDashboardController::class, 'set_sub_user_mode'])->middleware('can:users_have_sub')->name('api.dashboard.set_sub_user_mode');
});

