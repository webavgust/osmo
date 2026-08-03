<?php


Route::group(['prefix' => 'companies'], function () {
    Route::group([], function () {
        Route::get('/', [\App\Modules\Pub\Company\Controllers\CompanyController::class, 'index'])->name('company.index');
        Route::get('/detail/{company}', [\App\Modules\Pub\Company\Controllers\CompanyController::class, 'detail'])->name('company.detail');

        Route::get('/edit/{company?}', [\App\Modules\Pub\Company\Controllers\CompanyController::class, 'edit'])->name('company.edit');
        Route::post('/edit/{company}', [\App\Modules\Pub\Company\Controllers\CompanyController::class, 'edit_save'])->name('company.edit_save');

        Route::get('/create', [\App\Modules\Pub\Company\Controllers\CompanyController::class, 'create'])->name('company.create');

    });
});
