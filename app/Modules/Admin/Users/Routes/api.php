<?php

use App\Modules\Admin\Users\Controllers\Api\ApiUsersController;

/**
 * AJAX админ-панели: пользователи (patch v28).
 *
 * ModularProvider подключает api.php всех групп одинаково — префикс /api и
 * группа middleware `api`, без groupMiddleware из config/modular.php. Поэтому
 * can:admin_panel навешивается здесь явно, а ajax.api — как у соседних модулей:
 * запрос обязан нести _token текущего пользователя (csrf_token() в JS).
 */
Route::group([
    'prefix' => 'admin/users',
    'as' => 'admin.api.users.',
    'middleware' => ['can:admin_panel', 'ajax.api'],
], function () {
    Route::post('/store', [ApiUsersController::class, 'store'])->name('store');
    Route::post('/update/{user}', [ApiUsersController::class, 'update'])->withTrashed()->name('update');
    Route::post('/theme/{user}', [ApiUsersController::class, 'theme'])->withTrashed()->name('theme');
    Route::post('/log_view/{user}', [ApiUsersController::class, 'logView'])->withTrashed()->name('log_view'); // patch v29
    Route::post('/delete/{user}', [ApiUsersController::class, 'delete'])->name('delete');
    Route::post('/restore/{user}', [ApiUsersController::class, 'restore'])->withTrashed()->name('restore');
});
