<?php

use App\Modules\Admin\Consts\Controllers\Api\ApiConstsController;

/**
 * AJAX админ-панели: константы (patch v28, этап C).
 *
 * ModularProvider подключает api.php всех групп одинаково — префикс /api и
 * группа middleware `api`, без groupMiddleware из config/modular.php. Поэтому
 * can:admin_panel навешивается здесь явно, а ajax.api — как у соседних модулей:
 * запрос обязан нести _token текущего пользователя (csrf_token() в JS).
 */
Route::group([
    'prefix' => 'admin/consts',
    'as' => 'admin.api.consts.',
    'middleware' => ['can:admin_panel', 'ajax.api'],
], function () {
    Route::post('/store', [ApiConstsController::class, 'store'])->name('store');
    Route::post('/update/{constant}', [ApiConstsController::class, 'update'])->whereNumber('constant')->name('update');
    Route::post('/delete/{constant}', [ApiConstsController::class, 'delete'])->whereNumber('constant')->name('delete');
});
