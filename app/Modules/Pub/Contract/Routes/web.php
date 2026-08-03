<?php


Route::group(['prefix' => 'contracts'], function () {
    Route::group([], function () {
        // boxes
        Route::get('/box/add/{partner}', [\App\Modules\Pub\Contract\Controllers\ContractController::class, 'box_add'])->name('contract.box_add');
        Route::get('/box/edit/{contract}', [\App\Modules\Pub\Contract\Controllers\ContractController::class, 'box_edit'])->name('contract.box_edit');
    });
});
