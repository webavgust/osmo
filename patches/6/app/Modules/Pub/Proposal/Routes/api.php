<?php

Route::group(['prefix' => 'proposal', 'middleware' => ['ajax.api']], function () {
    Route::put('/store', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalController::class, 'store'])->name('api.proposal.store');
    Route::post('/update/{proposal}/{iteration}', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalController::class, 'update'])->name('api.proposal.update');
    Route::delete('/delete/{proposal}/{iteration}', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalController::class, 'delete'])->name('api.proposal.delete');
    Route::post('/convert/{proposal}/{iteration}', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalController::class, 'convert'])->name('api.proposal.convert');




    Route::get('/list_table/{manager?}', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalController::class, 'list_table'])->name('api.proposals.list_table');
    Route::post('/filter', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalController::class, 'filter'])->name('api.proposals.filter');
    Route::get('/filter', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalController::class, 'filterRemove'])->name('api.proposals.filter.remove');

    Route::post('/company', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalController::class, 'company'])->name('api.proposals.company');


    // статус КП
    Route::post('/status/{proposal}/{iteration}', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalStatusController::class, 'status'])->name('api.proposal.status');

    // привязка к сделке Битрикса
    Route::get('/deal/search/{proposal}', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalStatusController::class, 'dealSearch'])->name('api.proposal.deal_search');
    Route::post('/deal/attach/{proposal}/{iteration}', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalStatusController::class, 'dealAttach'])->name('api.proposal.deal_attach');
    Route::delete('/deal/detach/{proposal}/{iteration}', [\App\Modules\Pub\Proposal\Controllers\Api\ApiProposalStatusController::class, 'dealDetach'])->name('api.proposal.deal_detach');
});
