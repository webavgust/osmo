<?php

use App\Modules\Pub\User\Controllers\UserController;

Route::group(['prefix' => 'user', 'middleware' => ['auth']], function () {
    Route::get('/list', [\App\Modules\Pub\User\Controllers\UserController::class, 'view'])->name('users.list');
    Route::get('/{user}/work_calendar/{year?}', [\App\Modules\Pub\User\Controllers\UserController::class, 'work_calendar_show'])->name('users.work_calendar');
    Route::get('/unmask/{token}', [\App\Modules\Pub\User\Controllers\UserController::class, 'unmask'])->name('users.unmask');
    Route::get('/mask/{user}', [\App\Modules\Pub\User\Controllers\UserController::class, 'mask'])->name('users.mask');

    Route::get('/analytics/{user?}', [UserController::class, 'lab_object_bind'])->middleware(['can:lab_objects_bind'])->name('users.lab_object_bind');
    Route::get('/{user?}', 'UserController@detail')->name('users.view');
    // boxes
    Route::get('/box/mask', [\App\Modules\Pub\User\Controllers\UserController::class, 'box_mask'])->name('users.box_mask');

    // sidebars
    Route::get('/sidebar/groups/{user?}', [UserController::class, 'sidebar_groups'])->name('users.sidebar_groups');
    Route::get('/sidebar/departments/{user?}', [UserController::class, 'sidebar_departments'])->name('users.sidebar_departments');
    Route::get('/{user}/sidebar/work_calendar_set_time/{date?}', [\App\Modules\Pub\User\Controllers\UserController::class, 'work_calendar_set_time'])->name('users.sidebar_work_calendar_set_time');
    Route::group(['middleware' => ['can:users_sub_users_control']], function () {
        Route::get('/{user}/sidebar/sub_users/sub', [UserController::class, 'sidebar_sub_users_sub'])->name('users.sidebar_sub_users_sub');
        Route::get('/{user}/sidebar/sub_users/parent', [UserController::class, 'sidebar_sub_users_parent'])->name('users.sidebar_sub_users_parent');
    });
});


