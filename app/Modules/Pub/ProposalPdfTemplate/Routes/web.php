<?php

Route::group(['prefix' => 'proposal_pdf_template'], function () {
    // boxes
    Route::get('/box/templates/{proposal}/{iteration?}', [\App\Modules\Pub\ProposalPdfTemplate\Controllers\ProposalPdfTemplateController::class, 'box_templates'])->name('proposal_pdf_template.box_templates');

});
