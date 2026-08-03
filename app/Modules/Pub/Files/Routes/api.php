<?php

Route::group(['prefix' => 'files'], function() {
    Route::get('generate_pdf_from_file', [\App\Modules\Pub\Files\Controllers\Api\ApiFilesController::class, 'generatePdfFromFile'])->name('api.files.generate_pdf_from_file');
});

