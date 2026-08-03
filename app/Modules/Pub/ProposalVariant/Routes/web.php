<?php

Route::group(['prefix' => 'proposal-variant'], function () {
    // boxes
    Route::get('/box/edit/{variant}', [\App\Modules\Pub\ProposalVariant\Controllers\ProposalVariantController::class, 'box_edit'])->name('proposal-variant.box_edit');
});
