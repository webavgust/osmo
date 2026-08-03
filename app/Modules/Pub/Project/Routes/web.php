<?php
use \App\Modules\Pub\Project\Controllers\ProjectController;

Route::group(['prefix' => 'project', 'middleware' => []], function () {
    Route::get('/', [ProjectController::class, 'index'])->name('projects.index');

    // boxes
    Route::group(['prefix' => 'box'], function() {
        Route::get('/add/{company}', [\App\Modules\Pub\Project\Controllers\ProjectBoxController::class, 'add'])->name('project.box.add');
        Route::get('/edit/{project}', [\App\Modules\Pub\Project\Controllers\ProjectBoxController::class, 'edit'])->name('project.box.edit');
    });

    // components
    Route::group(['prefix' => 'components'], function() {   });
});

