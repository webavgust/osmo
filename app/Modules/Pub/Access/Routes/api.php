<?php

Route::group(['prefix' => 'access', 'middleware' => ['ajax.api']], function () {
    Route::middleware(['can:access_set'])->group(function() {
        Route::post('/set/user/{user}', [\App\Modules\Pub\Access\Controllers\AccessUserController::class, 'set_user'])->name('api.access_set.user');
    });

    Route::post('/refresh', [\App\Modules\Pub\Access\Controllers\AccessUserController::class, 'refresh'])->name('api.access.refresh');
});



