
<?php

Route::group(['prefix' => 'proposal-variant', 'middleware' => ['ajax.api']], function () {
    Route::post('/task/{variant}', [\App\Modules\Pub\ProposalVariant\Models\Api\ApiProposalVariantController::class, 'update'])->name('api.proposal-variant.update');
});
