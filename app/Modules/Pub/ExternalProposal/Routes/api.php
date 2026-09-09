<?php

use App\Modules\Pub\ExternalProposal\Controllers\Api\ApiExternalProposalController;

Route::group(['prefix' => 'external-proposal', 'middleware' => ['ajax.api']], function () {
    Route::get('/list_table', [ApiExternalProposalController::class, 'list_table'])->name('api.external_proposal.list_table');
    Route::post('/sync', [ApiExternalProposalController::class, 'sync'])->name('api.external_proposal.sync');
    Route::post('/import', [ApiExternalProposalController::class, 'import'])->name('api.external_proposal.import');
    Route::post('/fetch/{external}', [ApiExternalProposalController::class, 'fetch'])->name('api.external_proposal.fetch');
    Route::post('/transfer/{external}', [ApiExternalProposalController::class, 'transfer'])->name('api.external_proposal.transfer');
});
