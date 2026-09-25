<?php

use App\Modules\Pub\User\Controllers\UserController;

Route::group(['prefix' => 'user', 'middleware' => ['auth']], function () {
    Route::get('/list', [\App\Modules\Pub\User\Controllers\UserController::class, 'view'])->name('users.list');
    Route::get('/unmask/{token}', [\App\Modules\Pub\User\Controllers\UserController::class, 'unmask'])->name('users.unmask');
    Route::get('/mask/{user}', [\App\Modules\Pub\User\Controllers\UserController::class, 'mask'])->name('users.mask');

    // patch v38: «Рабочий график» и «Привязка аналитика к объектам» удалены — остатки прошлого проекта
    Route::get('/{user?}', 'UserController@detail')->name('users.view');
    // boxes
    Route::get('/box/mask', [\App\Modules\Pub\User\Controllers\UserController::class, 'box_mask'])->name('users.box_mask');

    // sidebars
    Route::group(['middleware' => ['can:users_sub_users_control']], function () {
        Route::get('/{user}/sidebar/sub_users/sub', [UserController::class, 'sidebar_sub_users_sub'])->name('users.sidebar_sub_users_sub');
        Route::get('/{user}/sidebar/sub_users/parent', [UserController::class, 'sidebar_sub_users_parent'])->name('users.sidebar_sub_users_parent');
    });
});


