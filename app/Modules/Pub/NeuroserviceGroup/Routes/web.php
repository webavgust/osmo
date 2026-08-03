<?php

//Route::prefix('neuroservice')->name('neuroservice')->middleware(['can:neuroservice_view'])->group(function() {
//    Route::get('list', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceController::class, 'list'])->name('.list');
//});

Route::group(['prefix' => 'neuroservice_group'], function () {
    Route::get('/create', [\App\Modules\Pub\NeuroserviceGroup\Controllers\NeuroserviceGroupController::class, 'create'])->name('neuroservice_group.create');
    Route::post('/', [\App\Modules\Pub\NeuroserviceGroup\Controllers\NeuroserviceGroupController::class, 'store'])->name('neuroservice_group.store');
    Route::get('/edit/{group?}', [\App\Modules\Pub\NeuroserviceGroup\Controllers\NeuroserviceGroupController::class, 'edit'])->name('neuroservice_group.edit');
    Route::put('/{group}', [\App\Modules\Pub\NeuroserviceGroup\Controllers\NeuroserviceGroupController::class, 'update'])->name('neuroservice_group.update');
    Route::delete('/{group}', [\App\Modules\Pub\NeuroserviceGroup\Controllers\NeuroserviceGroupController::class, 'destroy'])->name('neuroservice_group.delete');
});
