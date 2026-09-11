<?php
use \App\Modules\Bitrix\CrmDeal\Controllers\CrmDealBoxController;
use \App\Modules\Bitrix\CrmDeal\Controllers\CrmDealController;

Route::group(['prefix' => 'deal', 'middleware' => []], function () {
    // реестр сделок (patch v22)
    Route::get('/', [CrmDealController::class, 'index'])->name('crm-deal.index');
    Route::post('/export', [CrmDealController::class, 'export'])->name('crm-deal.export');

    // boxes
    Route::group(['prefix' => 'box'], function() {
        Route::get('/issues', [CrmDealBoxController::class, 'issues'])->name('crm-deal.box.issues');
        Route::get('/export', [CrmDealBoxController::class, 'export'])->name('crm-deal.box.export');
        Route::get('/proposal/{deal}', [CrmDealBoxController::class, 'proposal'])->name('crm-deal.box.proposal');
    });
});
