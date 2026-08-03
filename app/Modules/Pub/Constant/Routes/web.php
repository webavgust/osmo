<?php

Route::group(['prefix' => 'constants', 'middleware' => 'can:constant_control'], function() {
    Route::get('/', [\App\Modules\Pub\Constant\Controllers\ConstantController::class, 'index'])->name('constants.index');
    Route::post('/', [\App\Modules\Pub\Constant\Controllers\ConstantController::class, 'update'])->name('constants.update');
});

