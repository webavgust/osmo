<?php

use App\Modules\Admin\Users\Controllers\UsersController;

/**
 * Админ-панель: пользователи (patch v28). Префикс адреса /admin и middleware
 * auth + can:admin_panel добавляет ModularProvider для группы Admin,
 * префикс имён `admin.` — здесь.
 *
 * withTrashed: карточка и попап открываются и для мягко удалённого пользователя.
 */
Route::group(['prefix' => 'users', 'as' => 'admin.users.'], function () {
    Route::get('/', [UsersController::class, 'index'])->name('index');
    Route::get('/box/form/{user?}', [UsersController::class, 'box_form'])->whereNumber('user')->withTrashed()->name('box_form');
    Route::get('/{user}', [UsersController::class, 'show'])->whereNumber('user')->withTrashed()->name('show');
});
