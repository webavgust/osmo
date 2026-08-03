<?php

Route::middleware(['ajax.api'])->group(function() {
    Route::group(['prefix' => 'project', 'middleware' => []], function () {
        Route::put('/create/{company}', [\App\Modules\Pub\Project\Controllers\Api\ApiProjectController::class, 'add'])->name('api.project.add');
        Route::post('/update/{project}', [\App\Modules\Pub\Project\Controllers\Api\ApiProjectController::class, 'update'])->name('api.project.update');
        Route::delete('/delete/{project}', [\App\Modules\Pub\Project\Controllers\Api\ApiProjectController::class, 'delete'])->name('api.project.delete');
    });
});
