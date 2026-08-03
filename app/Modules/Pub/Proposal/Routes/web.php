<?php


Route::group(['prefix' => 'proposals'], function () {
    Route::get('/', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'index'])->name('proposal.index');

    Route::get('/edit/{proposal}/{iteration}', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'edit'])->name('proposal.edit');
    Route::post('/edit/{proposal}/{iteration}', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'edit_save'])->name('proposal.edit_save');

    Route::get('/create', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'create'])->name('proposal.create');
    Route::get('/detail/{proposal}/{iteration?}', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'detail'])->name('proposal.detail');

    // report
    Route::get('/report/{proposal}/{iteration?}', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'report_get'])->name('proposal.report_get');
    Route::post('/report/{proposal}/{iteration?}', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'report'])->name('proposal.report');


    // boxes
    Route::get('/sidebar/iterations/{proposal?}', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'sidebar_iterations'])->name('proposal.sidebar_iterations');
    Route::get('/box/generate_pdf/{proposal}/{iteration?}', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'box_generate_pdf'])->name('proposal.box_generate_pdf');
    Route::get('/box/convert/{proposal}/{iteration?}', [\App\Modules\Pub\Proposal\Controllers\ProposalController::class, 'box_convert'])->name('proposal.box_convert');
});
