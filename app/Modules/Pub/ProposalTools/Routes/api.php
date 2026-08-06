<?php

use App\Modules\Pub\ProposalTools\Controllers\Api\ApiProposalToolsController;

Route::group(['prefix' => 'proposal-tools', 'middleware' => ['ajax.api']], function () {
    Route::post('/clone/{proposal}/{iteration}', [ApiProposalToolsController::class, 'clone'])
        ->name('api.proposal_tools.clone');
});
