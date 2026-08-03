<?php

Route::group(['prefix' => 'scenario'], function () {
    Route::get('/', [\App\Modules\Pub\Scenario\Controllers\ScenarioController::class, 'index'])->name('scenario.index');
    Route::group([], function() {
        Route::get('/create/{group?}', [\App\Modules\Pub\Scenario\Controllers\ScenarioController::class, 'create'])->name('scenario.create');
        Route::post('/{group}', [\App\Modules\Pub\Scenario\Controllers\ScenarioController::class, 'store'])->name('scenario.store');
        Route::get('/edit/{scenario}', [\App\Modules\Pub\Scenario\Controllers\ScenarioController::class, 'edit'])->name('scenario.edit');
        Route::put('/edit/{scenario}', [\App\Modules\Pub\Scenario\Controllers\ScenarioController::class, 'update'])->name('scenario.update');
        Route::delete('/{scenario}', [\App\Modules\Pub\Scenario\Controllers\ScenarioController::class, 'destroy'])->name('scenario.delete');
    });
});
