<?php

use App\Modules\Pub\DealProject\Controllers\Api\ApiDealProjectController;

/**
 * AJAX проектов по сделкам (patch v24). Middleware ajax.api — как у соседних
 * модулей: запрос обязан нести _token текущего пользователя.
 */
Route::group(['prefix' => 'deal-project', 'middleware' => ['ajax.api']], function () {
    Route::post('/partner/{deal}', [ApiDealProjectController::class, 'partner'])->name('api.deal_project.partner');
    Route::post('/store/{deal}', [ApiDealProjectController::class, 'store'])->name('api.deal_project.store');
    Route::post('/update/{project}', [ApiDealProjectController::class, 'update'])->name('api.deal_project.update');
    Route::post('/attach/{project}/{deal}', [ApiDealProjectController::class, 'attach'])->name('api.deal_project.attach');
    Route::post('/detach/{deal}', [ApiDealProjectController::class, 'detach'])->name('api.deal_project.detach');
    Route::post('/archive/{project}', [ApiDealProjectController::class, 'archive'])->name('api.deal_project.archive');
    Route::post('/unarchive/{project}', [ApiDealProjectController::class, 'unarchive'])->name('api.deal_project.unarchive');
});
