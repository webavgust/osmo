<?php

Route::group(['prefix' => 'user', 'middleware' => ['ajax.api']], function () {
    Route::get('/list_table', 'Api\UserController@list_table')->name('api.users.list');
    Route::post('/sync/all', 'Api\UserController@sync_all')->name('api.user.sync_all');

    Route::group(['middleware' => ['can:users_sub_users_control']], function() {
        Route::post('/{user}/sub_users/parent', [\App\Modules\Pub\User\Controllers\Api\UserController::class, 'parent_users_set'])->name('users.parent_users_set');
        Route::post('/{user}/sub_users/sub', [\App\Modules\Pub\User\Controllers\Api\UserController::class, 'sub_users_set'])->name('users.sub_users_set');
    });

    Route::post('/{user}/work_calendar/{year}/set',  [\App\Modules\Pub\User\Controllers\Api\UserController::class, 'work_calendar_set'])->name('api.users.work_calendar.set');
    Route::post('/{user}/work_calendar/{year}/copy',  [\App\Modules\Pub\User\Controllers\Api\UserController::class, 'work_calendar_copy'])->name('api.users.work_calendar.copy');
    Route::post('/{user}/work_calendar/set_time/{date}',  [\App\Modules\Pub\User\Controllers\Api\UserController::class, 'work_calendar_set_time'])->name('api.users.work_calendar.set_time');


    Route::post('/analytic_bind/{user?}',  [\App\Modules\Pub\User\Controllers\Api\UserController::class, 'analytic_bind'])->name('api.users.analytic_bind');
});

