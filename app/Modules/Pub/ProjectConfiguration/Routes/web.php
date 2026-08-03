<?php
use \App\Modules\Pub\ProjectConfiguration\Controllers\ProjectConfigurationController;

Route::group(['prefix' => 'projectconfigurations', 'middleware' => []], function () {
    Route::get('/', [ProjectConfigurationController::class, 'index'])->name('projectconfigurations.index');

    // boxes
    Route::group(['prefix' => 'box'], function() {
        Route::get('/add/{project}', [\App\Modules\Pub\ProjectConfiguration\Controllers\ProjectConfigurationBoxController::class, 'add'])->name('project_configuration.box.add');
        Route::get('/edit/{configuration}', [\App\Modules\Pub\ProjectConfiguration\Controllers\ProjectConfigurationBoxController::class, 'edit'])->name('project_configuration.box.edit');
    });

    // components
    Route::group(['prefix' => 'components'], function() {   });
});

