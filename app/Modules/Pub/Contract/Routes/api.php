<?php

Route::group(['prefix' => 'contracts', 'middleware' => ['ajax.api']], function () {
    Route::put('/create/{partner}', [\App\Modules\Pub\Contract\Controllers\Api\ApiContractController::class, 'store'])->name('api.contract.create');
    Route::post('/update/{contract}', [\App\Modules\Pub\Contract\Controllers\Api\ApiContractController::class, 'update'])->name('api.contract.update');
    Route::delete('/delete/{contract}', [\App\Modules\Pub\Contract\Controllers\Api\ApiContractController::class, 'delete'])->name('api.contract.delete');
});
