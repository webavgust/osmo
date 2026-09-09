<?php

Route::group(['prefix' => 'access', 'middleware' => ['can:access_view']], function () {
    Route::get('/', [\App\Modules\Pub\Access\Controllers\AccessController::class, 'index'])->name('access.index');

    Route::group(['middleware' => 'can:access_create'], function() {
        Route::get('/create/{group?}', [\App\Modules\Pub\Access\Controllers\AccessController::class, 'create'])->name('access.create');
        Route::post('/{group}', [\App\Modules\Pub\Access\Controllers\AccessController::class, 'store'])->name('access.store');
        Route::get('/edit/{access}', [\App\Modules\Pub\Access\Controllers\AccessController::class, 'edit'])->name('access.edit');
        Route::put('/edit/{access}', [\App\Modules\Pub\Access\Controllers\AccessController::class, 'update'])->name('access.update');
        Route::delete('/{access}', [\App\Modules\Pub\Access\Controllers\AccessController::class, 'destroy'])->name('access.delete');
    });

    Route::group(['middleware' => 'can:access_set'], function() {
        Route::get('/set/user', [\App\Modules\Pub\Access\Controllers\AccessUserController::class, 'view_user'])->name('access_set.user_list');
        Route::get('/set/user/{user?}', [\App\Modules\Pub\Access\Controllers\AccessUserController::class, 'view_user_detail'])->name('access_set.user');
    });
});
