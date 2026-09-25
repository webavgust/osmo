<?php

Route::group(['prefix' => 'user', 'middleware' => ['ajax.api']], function () {
    Route::get('/list_table', 'Api\UserController@list_table')->name('api.users.list');
    Route::post('/sync/all', 'Api\UserController@sync_all')->name('api.user.sync_all');

    Route::group(['middleware' => ['can:users_sub_users_control']], function() {
        Route::post('/{user}/sub_users/parent', [\App\Modules\Pub\User\Controllers\Api\UserController::class, 'parent_users_set'])->name('users.parent_users_set');
        Route::post('/{user}/sub_users/sub', [\App\Modules\Pub\User\Controllers\Api\UserController::class, 'sub_users_set'])->name('users.sub_users_set');
    });
    // patch v38: API рабочего графика и привязки аналитика удалены вместе со страницами
});

