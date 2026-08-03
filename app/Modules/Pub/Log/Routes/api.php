<?php

Route::group(['prefix' => 'log', 'middleware' => ['ajax.api']], function () {
    Route::post('/create', [\App\Modules\Pub\Log\Controllers\Api\LogController::class, 'create'])->name('api.log.create');
    Route::post('/story', [\App\Modules\Pub\Log\Controllers\Api\LogController::class, 'story'])->name('api.log.story');
    Route::post('/fast', [\App\Modules\Pub\Log\Controllers\Api\LogController::class, 'fast'])->name('api.log.fast');

});
