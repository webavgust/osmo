<?php

Route::group(['prefix' => 'calendar'], function () {
    Route::get('/', [\App\Modules\Pub\Calendar\Controllers\CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/schedule_pdf', [\App\Modules\Pub\Calendar\Controllers\CalendarController::class, 'schedule_pdf'])->name('calendar.schedule.pdf');

    // sidebars
    Route::get('/sidebar/add', [\App\Modules\Pub\Calendar\Controllers\CalendarController::class, 'sidebar_add'])->name('calendar.sidebar_add');
    Route::get('/sidebar/show/{id?}', [\App\Modules\Pub\Calendar\Controllers\CalendarController::class, 'sidebar_show'])->name('calendar.sidebar_show');
    Route::get('/sidebar/edit/{id?}', [\App\Modules\Pub\Calendar\Controllers\CalendarController::class, 'sidebar_edit'])->name('calendar.sidebar_edit');
});
