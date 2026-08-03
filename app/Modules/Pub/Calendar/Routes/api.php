<?php

Route::group(['prefix' => 'calendar', 'middleware' => ['ajax.api']], function () {
    Route::post('/add',  [\App\Modules\Pub\Calendar\Controllers\Api\ApiCalendarController::class, 'add'])->name('api.calendar.add');
    Route::post('/edit/{event}',  [\App\Modules\Pub\Calendar\Controllers\Api\ApiCalendarController::class, 'edit'])->name('api.calendar.edit');
    Route::post('/set/{event?}',  [\App\Modules\Pub\Calendar\Controllers\Api\ApiCalendarController::class, 'set'])->name('api.calendar.set');
});
