<?php

use App\Modules\Admin\Consts\Controllers\ConstsController;

/**
 * Админ-панель: константы (patch v28, этап C). Префикс адреса /admin и
 * middleware auth + can:admin_panel добавляет ModularProvider для группы Admin,
 * префикс имён `admin.` — здесь.
 */
Route::group(['prefix' => 'consts', 'as' => 'admin.consts.'], function () {
    Route::get('/', [ConstsController::class, 'index'])->name('index');
    Route::get('/box/form/{constant?}', [ConstsController::class, 'box_form'])->whereNumber('constant')->name('box_form');
});
