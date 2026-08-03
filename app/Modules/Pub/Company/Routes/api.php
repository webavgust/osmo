<?php

Route::group(['prefix' => 'company', 'middleware' => ['ajax.api']], function () {
    Route::put('/store', [\App\Modules\Pub\Company\Controllers\Api\ApiCompanyController::class, 'store'])->name('api.company.store');
    Route::post('/update/{company}', [\App\Modules\Pub\Company\Controllers\Api\ApiCompanyController::class, 'update'])->name('api.company.update');
    Route::delete('/delete/{company?}', [\App\Modules\Pub\Company\Controllers\Api\ApiCompanyController::class, 'delete'])->name('api.company.delete');




    Route::get('/list_table', [\App\Modules\Pub\Company\Controllers\Api\ApiCompanyController::class, 'list_table'])->name('api.companies.list_table');
    Route::post('/filter', [\App\Modules\Pub\Company\Controllers\Api\ApiCompanyController::class, 'filter'])->name('api.companies.filter');
    Route::get('/filter', [\App\Modules\Pub\Company\Controllers\Api\ApiCompanyController::class, 'filterRemove'])->name('api.companies.filter.remove');

});
