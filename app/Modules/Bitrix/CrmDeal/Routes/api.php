<?php

use App\Modules\Bitrix\CrmDeal\Controllers\Api\ApiCrmDealController;

/**
 * AJAX реестра сделок (patch v24): привязка КП к сделке прямо из списка.
 * Middleware ajax.api — как у соседних модулей: запрос обязан нести _token.
 */
Route::group(['prefix' => 'crm-deal', 'middleware' => ['ajax.api']], function () {
    Route::get('/proposal/search/{deal}', [ApiCrmDealController::class, 'proposalSearch'])->name('api.crm_deal.proposal_search');
    Route::post('/proposal/attach/{deal}', [ApiCrmDealController::class, 'proposalAttach'])->name('api.crm_deal.proposal_attach');
    Route::post('/proposal/detach/{deal}', [ApiCrmDealController::class, 'proposalDetach'])->name('api.crm_deal.proposal_detach');
});
