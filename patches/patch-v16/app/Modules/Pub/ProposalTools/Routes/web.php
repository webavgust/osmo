<?php

use App\Modules\Pub\ProposalTools\Controllers\ProposalExcelController;
use App\Modules\Pub\ProposalTools\Controllers\ProposalToolsController;

Route::group(['prefix' => 'proposal-tools'], function () {
    Route::get('/price-history/{proposal}', [ProposalToolsController::class, 'price_history'])
        ->name('proposal_tools.price_history');

    // выгрузка в Excel (patch v16)
    Route::post('/excel/{proposal}/{iteration?}', [ProposalExcelController::class, 'download'])
        ->name('proposal_tools.excel');

    // boxes
    Route::get('/box/clone/{proposal}/{iteration?}', [ProposalToolsController::class, 'box_clone'])
        ->name('proposal_tools.box_clone');
    Route::get('/box/excel/{proposal}/{iteration?}', [ProposalExcelController::class, 'box'])
        ->name('proposal_tools.box_excel');
});
