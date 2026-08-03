<?php

Route::group(['prefix' => 'partner', 'middleware' => ['ajax.api']], function () {
    Route::put('/store', [\App\Modules\Pub\Partner\Controllers\Api\ApiPartnerController::class, 'store'])->name('api.partner.store');
    Route::post('/update/{partner}', [\App\Modules\Pub\Partner\Controllers\Api\ApiPartnerController::class, 'update'])->name('api.partner.update');
    Route::delete('/delete/{partner?}', [\App\Modules\Pub\Partner\Controllers\Api\ApiPartnerController::class, 'delete'])->name('api.partner.delete');




    Route::get('/list_table', [\App\Modules\Pub\Partner\Controllers\Api\ApiPartnerController::class, 'list_table'])->name('api.partners.list_table');
    Route::post('/filter', [\App\Modules\Pub\Partner\Controllers\Api\ApiPartnerController::class, 'filter'])->name('api.partners.filter');
    Route::get('/filter', [\App\Modules\Pub\Partner\Controllers\Api\ApiPartnerController::class, 'filterRemove'])->name('api.partners.filter.remove');

});
