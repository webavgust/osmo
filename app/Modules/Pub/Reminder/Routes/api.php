<?php

// работа с таблицей
Route::group(['prefix' => 'reminder', 'middleware' => ['ajax.api']], function () {
    Route::post('/create', [\App\Modules\Pub\Reminder\Controllers\Api\ApiReminderController::class, 'create'])->name('api.reminder.create');
    Route::post('/full_edit/{reminder}', [\App\Modules\Pub\Reminder\Controllers\Api\ApiReminderController::class, 'full_edit'])->name('api.reminder.full_edit');
    Route::post('/edit/{reminder}', [\App\Modules\Pub\Reminder\Controllers\Api\ApiReminderController::class, 'edit'])->name('api.reminder.edit');

    Route::post('/delete/{group?}', [\App\Modules\Pub\Reminder\Controllers\Api\ApiReminderController::class, 'delete'])->name('api.reminder.delete');
});

//
