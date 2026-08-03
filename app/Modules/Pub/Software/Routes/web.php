<?php


Route::group(['prefix' => 'softwares'], function () {
    Route::group([], function () {
        Route::get('/', [\App\Modules\Pub\Software\Controllers\SoftwareController::class, 'index'])->name('software.index');

        Route::get('/edit/{software?}', [\App\Modules\Pub\Software\Controllers\SoftwareController::class, 'edit'])->name('software.edit');
        Route::post('/edit/{software}', [\App\Modules\Pub\Software\Controllers\SoftwareController::class, 'edit_save'])->name('software.edit_save');

        Route::get('/create', [\App\Modules\Pub\Software\Controllers\SoftwareController::class, 'create'])->name('software.create');


        // boxes
        Route::get('/box/extended/{software?}', [\App\Modules\Pub\Software\Controllers\SoftwareController::class, 'box_extended'])->name('software.box_extended');
    });
});
