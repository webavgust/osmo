<?php

use App\Modules\Pub\ExternalProposal\Controllers\ExternalProposalController;

Route::group(['prefix' => 'external-proposals'], function () {
    Route::get('/', [ExternalProposalController::class, 'index'])->name('external_proposal.index');

    // boxes
    Route::get('/box/import', [ExternalProposalController::class, 'box_import'])->name('external_proposal.box_import');
    Route::get('/box/detail/{external}', [ExternalProposalController::class, 'box_detail'])->name('external_proposal.box_detail');
    Route::get('/box/transfer/{external}', [ExternalProposalController::class, 'box_transfer'])->name('external_proposal.box_transfer');
});
