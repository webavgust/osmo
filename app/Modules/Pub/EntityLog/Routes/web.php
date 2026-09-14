<?php

use App\Modules\Pub\EntityLog\Controllers\EntityLogController;

// Журнал изменений сущностей (patch v29): лента объекта — /timeline/{type}/{key}
Route::get('/timeline/{type}/{key}', [EntityLogController::class, 'index'])
    ->middleware('can:entity_log_view')
    ->name('entity_log.index');
