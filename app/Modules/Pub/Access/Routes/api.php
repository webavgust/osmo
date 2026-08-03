<?php

Route::group(['prefix' => 'access', 'middleware' => ['ajax.api']], function () {
    Route::middleware(['can:access_set'])->group(function() {
        Route::post('/set/department/{department}', [\App\Modules\Pub\Access\Controllers\AccessUserDepartmentController::class, 'set_department'])->name('api.access_set.department');
        Route::post('/set/group/{group}', [\App\Modules\Pub\Access\Controllers\AccessUserGroupController::class, 'set_group'])->name('api.access_set.group');
        Route::post('/set/user/{user}', [\App\Modules\Pub\Access\Controllers\AccessUserController::class, 'set_user'])->name('api.access_set.user');
    });

    Route::post('/refresh', [\App\Modules\Pub\Access\Controllers\AccessUserController::class, 'refresh'])->name('api.access.refresh');
});



