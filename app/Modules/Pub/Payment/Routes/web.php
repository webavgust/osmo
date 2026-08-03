<?php

Route::group(['prefix' => 'payments'], function () {
    Route::group([], function () {

        // boxes
        Route::get('/box/control/{spec}', [\App\Modules\Pub\Payment\Controllers\PaymentController::class, 'box_control'])->name('payment.box_control');
        Route::get('/box/past/{company}', [\App\Modules\Pub\Payment\Controllers\PaymentController::class, 'box_past'])->name('payment.box_past');
        Route::get('/box/future/{company}', [\App\Modules\Pub\Payment\Controllers\PaymentController::class, 'box_future'])->name('payment.box_future');
    });
});
