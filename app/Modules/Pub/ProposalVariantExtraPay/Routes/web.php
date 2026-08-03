<?php
Route::group(['prefix' => 'proposal-variant-extra-pay'], function () {

    // boxes
    Route::get('/box/edit/{variant}', [\App\Modules\Pub\ProposalVariantExtraPay\Controllers\ProposalVariantExtraPayController::class, 'box_edit'])->name('proposal-variant-extra-pay.box_edit');
});
