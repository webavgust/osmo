<?php

Route::group(['prefix' => 'proposal_pdf_template', 'middleware' => ['ajax.api']], function () {
    Route::post('/store/{proposal}/{iteration?}', [\App\Modules\Pub\ProposalPdfTemplate\Controllers\Api\ApiProposalPdfTemplateController::class, 'store'])->name('api.proposal_pdf_template.store');


});
