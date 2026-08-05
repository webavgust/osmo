<?php

use App\Modules\Pub\PaymentCalendar\Controllers\PaymentCalendarController;

Route::group(['prefix' => 'payment-calendar'], function () {
    Route::get('/', [PaymentCalendarController::class, 'index'])->name('payment_calendar.index');
});
