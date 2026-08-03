<?php

//Route::prefix('scenario')->name('scenario')->middleware(['can:scenario_view'])->group(function() {
//    Route::get('list', [\App\Modules\Pub\Scenario\Controllers\ScenarioController::class, 'list'])->name('.list');
//});

Route::group(['prefix' => 'scenario_group'], function () {
    Route::get('/create', [\App\Modules\Pub\ScenarioGroup\Controllers\ScenarioGroupController::class, 'create'])->name('scenario_group.create');
    Route::post('/', [\App\Modules\Pub\ScenarioGroup\Controllers\ScenarioGroupController::class, 'store'])->name('scenario_group.store');
    Route::get('/edit/{group?}', [\App\Modules\Pub\ScenarioGroup\Controllers\ScenarioGroupController::class, 'edit'])->name('scenario_group.edit');
    Route::put('/{group}', [\App\Modules\Pub\ScenarioGroup\Controllers\ScenarioGroupController::class, 'update'])->name('scenario_group.update');
    Route::delete('/{group}', [\App\Modules\Pub\ScenarioGroup\Controllers\ScenarioGroupController::class, 'destroy'])->name('scenario_group.delete');
});
