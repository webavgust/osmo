<?php

use App\Modules\Pub\DealCard\Controllers\DealCardController;

Route::group(['prefix' => 'deal-card'], function () {
    Route::get('/{proposal}', [DealCardController::class, 'index'])->name('deal_card.index');
});
