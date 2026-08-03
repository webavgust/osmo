<?php


Route::group(['prefix' => 'partners'], function () {
    Route::group([], function () {
        Route::get('/', [\App\Modules\Pub\Partner\Controllers\PartnerController::class, 'index'])->name('partner.index');

        Route::get('/edit/{partner?}', [\App\Modules\Pub\Partner\Controllers\PartnerController::class, 'edit'])->name('partner.edit');
        Route::post('/edit/{partner}', [\App\Modules\Pub\Partner\Controllers\PartnerController::class, 'edit_save'])->name('partner.edit_save');

        Route::get('/create', [\App\Modules\Pub\Partner\Controllers\PartnerController::class, 'create'])->name('partner.create');
        Route::get('/detail/{partner?}', [\App\Modules\Pub\Partner\Controllers\PartnerController::class, 'detail'])->name('partner.detail');
    });
});
