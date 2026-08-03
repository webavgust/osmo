<?php

Route::group(['prefix' => 'neuroservice'], function () {
    Route::get('/', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceController::class, 'index'])->name('neuroservice.index');
    Route::group([], function() {
        Route::get('/create/{group?}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceController::class, 'create'])->name('neuroservice.create');
        Route::post('/{group}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceController::class, 'store'])->name('neuroservice.store');
        Route::get('/edit/{neuroservice}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceController::class, 'edit'])->name('neuroservice.edit');
        Route::put('/edit/{neuroservice}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceController::class, 'update'])->name('neuroservice.update');
        Route::delete('/{neuroservice}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceController::class, 'destroy'])->name('neuroservice.delete');
    });


    // boxes
    Route::get('/box/scenarios/{neuroservice}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceController::class, 'box_scenarios'])->name('neuroservice.box_scenarios');
});
