<?php

Route::group(['prefix' => 'reminders', 'middleware' => []], function () {
    Route::get('/', [\App\Modules\Pub\Reminder\Controllers\ReminderController::class, 'index'])->name('reminder.index');
    Route::get('/filter/{module}/{id}', [\App\Modules\Pub\Reminder\Controllers\ReminderController::class, 'filter'])->name('reminder.filter');

    Route::post('/sidebar/add', [\App\Modules\Pub\Reminder\Controllers\ReminderController::class, 'sidebar_add'])->name('reminder.sidebar_add');

    Route::get('/sidebar/edit/{reminder}', [\App\Modules\Pub\Reminder\Controllers\ReminderController::class, 'sidebar_edit'])->name('reminder.sidebar_edit');
    Route::get('/component/time', [\App\Modules\Pub\Reminder\Controllers\ReminderController::class, 'component_time'])->name('reminder.component_time');
});
