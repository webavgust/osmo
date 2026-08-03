<?php

Route::group(['prefix' => 'files'], function() {
    Route::post('upload_temporary', [\App\Modules\Pub\Files\Controllers\FilesController::class, 'upload_temporary'])->name('files.upload_temporary');
    Route::post('files.upload_trash', [\App\Modules\Pub\Files\Controllers\FilesController::class, 'upload_trash'])->name('files.upload_trash');
    Route::post('delete_temporary', [\App\Modules\Pub\Files\Controllers\FilesController::class, 'delete_temporary'])->name('files.delete_temporary');
    Route::get('block_redraw', [\App\Modules\Pub\Files\Controllers\FilesController::class, 'block_redraw'])->name('files.block_redraw');
});

