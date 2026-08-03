<?php

Route::group(['prefix' => 'neuroservice', 'middleware' => ['ajax.api']], function () {
    Route::middleware(['can:neuroservice_set'])->group(function() {
//        Route::post('/set/department/{department}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceUserDepartmentController::class, 'set_department'])->name('api.neuroservice_set.department');
//        Route::post('/set/group/{group}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceUserGroupController::class, 'set_group'])->name('api.neuroservice_set.group');
//        Route::post('/set/user/{user}', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceUserController::class, 'set_user'])->name('api.neuroservice_set.user');
    });

//    Route::post('/refresh', [\App\Modules\Pub\Neuroservice\Controllers\NeuroserviceUserController::class, 'refresh'])->name('api.neuroservice.refresh');
});



