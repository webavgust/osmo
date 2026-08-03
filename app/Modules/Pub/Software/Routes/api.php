<?php

Route::group(['prefix' => 'software', 'middleware' => ['ajax.api']], function () {
    Route::put('/store', [\App\Modules\Pub\Software\Controllers\Api\ApiSoftwareController::class, 'store'])->name('api.software.store');
    Route::post('/update/{software}', [\App\Modules\Pub\Software\Controllers\Api\ApiSoftwareController::class, 'update'])->name('api.software.update');
    Route::delete('/delete/{software?}', [\App\Modules\Pub\Software\Controllers\Api\ApiSoftwareController::class, 'delete'])->name('api.software.delete');




    Route::get('/list_table', [\App\Modules\Pub\Software\Controllers\Api\ApiSoftwareController::class, 'list_table'])->name('api.softwares.list_table');
    Route::post('/filter', [\App\Modules\Pub\Software\Controllers\Api\ApiSoftwareController::class, 'filter'])->name('api.softwares.filter');
    Route::get('/filter', [\App\Modules\Pub\Software\Controllers\Api\ApiSoftwareController::class, 'filterRemove'])->name('api.softwares.filter.remove');

});
