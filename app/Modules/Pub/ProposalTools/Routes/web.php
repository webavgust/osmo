<?php

use App\Modules\Pub\ProposalTools\Controllers\ProposalToolsController;

Route::group(['prefix' => 'proposal-tools'], function () {
    Route::get('/price-history/{proposal}', [ProposalToolsController::class, 'price_history'])
        ->name('proposal_tools.price_history');

    // boxes
    Route::get('/box/clone/{proposal}/{iteration?}', [ProposalToolsController::class, 'box_clone'])
        ->name('proposal_tools.box_clone');
});
