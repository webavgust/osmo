<?php

Route::group(['prefix' => 'payments', 'middleware' => ['ajax.api']], function () {
    Route::put('/create/{spec}', [\App\Modules\Pub\Payment\Controllers\Api\ApiPaymentController::class, 'store'])->name('api.payment.create');
});
