<?php


Route::group(['prefix' => 'works'], function () {
    Route::group([], function () {
        Route::get('/', [\App\Modules\Pub\Work\Controllers\WorkController::class, 'index'])->name('work.index');

        Route::get('/edit/{work?}', [\App\Modules\Pub\Work\Controllers\WorkController::class, 'edit'])->name('work.edit');
        Route::post('/edit/{work}', [\App\Modules\Pub\Work\Controllers\WorkController::class, 'edit_save'])->name('work.edit_save');

        Route::get('/create', [\App\Modules\Pub\Work\Controllers\WorkController::class, 'create'])->name('work.create');


        // boxes
        Route::get('/box/extended/{work?}', [\App\Modules\Pub\Work\Controllers\WorkController::class, 'box_extended'])->name('work.box_extended');
    });
});
