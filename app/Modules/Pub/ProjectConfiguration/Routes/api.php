<?php
use \App\Modules\Pub\ProjectConfiguration\Controllers\ProjectConfigurationController;

Route::middleware(['ajax.api'])->group(function() {
    Route::group(['prefix' => 'project_configuration', 'middleware' => []], function () {
        Route::put('create/{project}', [\App\Modules\Pub\ProjectConfiguration\Controllers\Api\ApiProjectConfigurationController::class, 'create'])->name('api.project_configuration.add');
        Route::post('update/{configuration}', [\App\Modules\Pub\ProjectConfiguration\Controllers\Api\ApiProjectConfigurationController::class, 'update'])->name('api.project_configuration.update');
    });
});
