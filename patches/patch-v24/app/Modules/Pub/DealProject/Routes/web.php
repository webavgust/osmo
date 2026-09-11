<?php

use App\Modules\Pub\DealProject\Controllers\DealProjectController;

/**
 * Проекты по сделкам (patch v24): только попапы — своей страницы у проектов
 * нет, они живут в реестре сделок и во вкладках карточки партнёра.
 */
Route::group(['prefix' => 'deal-projects'], function () {
    Route::get('/box/form/{deal}', [DealProjectController::class, 'box_form'])->name('deal_project.box_form');
    Route::get('/box/edit/{project}', [DealProjectController::class, 'box_edit'])->name('deal_project.box_edit');
    Route::get('/box/info/{project}', [DealProjectController::class, 'box_info'])->name('deal_project.box_info');
});
