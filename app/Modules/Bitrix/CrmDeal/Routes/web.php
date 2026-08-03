<?php
use \App\Modules\Bitrix\Dashboard\Controllers\DashboardController;

Route::group(['prefix' => 'deal', 'middleware' => []], function () {
    // boxes
    Route::group(['prefix' => 'box'], function() {
        Route::get('/issues', [\App\Modules\Bitrix\CrmDeal\Controllers\CrmDealBoxController::class, 'issues'])->name('crm-deal.box.issues');
    });
});

