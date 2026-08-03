<?php


Route::group(['prefix' => 'hardware'], function () {

    // boxes
    Route::get('/box/add/{variant}', [\App\Modules\Pub\Hardware\Controllers\HardwarelController::class, 'box_add'])->name('hardware.box_add');
    Route::get('/box/edit/{hardware}', [\App\Modules\Pub\Hardware\Controllers\HardwarelController::class, 'box_edit'])->name('hardware.box_edit');
});
