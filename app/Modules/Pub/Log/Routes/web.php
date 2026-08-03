<?php


Route::group(['prefix' => 'log'], function () {
    Route::get('/all', [\App\Modules\Pub\Log\Controllers\LogController::class, 'all'])->name('log.all');
    Route::get('/{period?}', [\App\Modules\Pub\Log\Controllers\LogController::class, 'index'])->name('log.index');
    Route::get('/day/{period}', [\App\Modules\Pub\Log\Controllers\LogController::class, 'day'])->name('log.day');

    // boxes
    Route::get('/box/detail/{log}', [\App\Modules\Pub\Log\Controllers\LogController::class, 'box_detail'])->name('log.box_detail');
    Route::get('/box/fast/{proposal}', [\App\Modules\Pub\Log\Controllers\LogController::class, 'box_fast'])->name('log.box_fast');
});
