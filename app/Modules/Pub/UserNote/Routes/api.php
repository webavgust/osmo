<?php

// работа с таблицей
Route::group(['prefix' => 'user-notes', 'middleware' => ['ajax.api']], function () {
    Route::post('/create', [\App\Modules\Pub\UserNote\Controllers\Api\ApiUserNoteController::class, 'create'])->name('api.user-notes.create');
    Route::post('/edit/{note}', [\App\Modules\Pub\UserNote\Controllers\Api\ApiUserNoteController::class, 'edit'])->name('api.user-notes.edit');
    Route::post('/delete/{note?}', [\App\Modules\Pub\UserNote\Controllers\Api\ApiUserNoteController::class, 'delete'])->name('api.user-notes.delete');
    Route::post('/favorite/{note?}', [\App\Modules\Pub\UserNote\Controllers\Api\ApiUserNoteController::class, 'favorite'])->name('api.user-notes.favorite');
});

//
