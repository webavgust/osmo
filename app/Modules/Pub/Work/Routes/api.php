<?php

Route::group(['prefix' => 'work', 'middleware' => ['ajax.api']], function () {
    Route::put('/store', [\App\Modules\Pub\Work\Controllers\Api\ApiWorkController::class, 'store'])->name('api.work.store');
    Route::post('/update/{work}', [\App\Modules\Pub\Work\Controllers\Api\ApiWorkController::class, 'update'])->name('api.work.update');
    Route::delete('/delete/{work?}', [\App\Modules\Pub\Work\Controllers\Api\ApiWorkController::class, 'delete'])->name('api.work.delete');




    Route::get('/list_table', [\App\Modules\Pub\Work\Controllers\Api\ApiWorkController::class, 'list_table'])->name('api.works.list_table');
    Route::post('/filter', [\App\Modules\Pub\Work\Controllers\Api\ApiWorkController::class, 'filter'])->name('api.works.filter');
    Route::get('/filter', [\App\Modules\Pub\Work\Controllers\Api\ApiWorkController::class, 'filterRemove'])->name('api.works.filter.remove');

});
