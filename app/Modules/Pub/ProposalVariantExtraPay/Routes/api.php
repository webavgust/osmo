<?php

Route::group(['prefix' => 'proposal-variant-extra-pay', 'middleware' => ['ajax.api']], function () {
    Route::post('/store/{variant}', [\App\Modules\Pub\ProposalVariantExtraPay\Controllers\Api\ApiProposalVariantExtraPayController::class, 'store'])->name('api.proposal-variant-extra-pay.store');
});
