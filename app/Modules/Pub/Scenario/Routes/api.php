<?php

//Route::group(['prefix' => 'scenario', 'middleware' => ['ajax.api']], function () {
//    Route::middleware(['can:scenario_set'])->group(function() {
//        Route::post('/set/department/{department}', [\App\Modules\Pub\Scenario\Controllers\ScenarioUserDepartmentController::class, 'set_department'])->name('api.scenario_set.department');
//        Route::post('/set/group/{group}', [\App\Modules\Pub\Scenario\Controllers\ScenarioUserGroupController::class, 'set_group'])->name('api.scenario_set.group');
//        Route::post('/set/user/{user}', [\App\Modules\Pub\Scenario\Controllers\ScenarioUserController::class, 'set_user'])->name('api.scenario_set.user');
//    });
//
//    Route::post('/refresh', [\App\Modules\Pub\Scenario\Controllers\ScenarioUserController::class, 'refresh'])->name('api.scenario.refresh');
//});
//
//
//
