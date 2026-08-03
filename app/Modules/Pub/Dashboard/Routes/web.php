<?php

Route::group(['prefix' => 'dashboard'], function () {
    Route::get('/{mode?}', [\App\Modules\Pub\Dashboard\Controllers\DashboardController::class, 'index'])->name('dashboard.index');
    Route::post('/set/dates', [\App\Modules\Pub\Dashboard\Controllers\DashboardController::class, 'set_dates'])->name('dashboard.set_dates');

});

Route::group(['prefix' => 'order_task', 'middleware' => ['can:users_have_sub']], function () {
    Route::get('/sidebar/sub_user_select', [\App\Modules\Pub\Dashboard\Controllers\DashboardController::class, 'sidebar_sub_user_select'])->name('dashboard.sidebar_sub_user_select');
    Route::get('/sidebar/user_select', [\App\Modules\Pub\Dashboard\Controllers\DashboardController::class, 'sidebar_user_select'])->name('dashboard.sidebar_user_select');
});
