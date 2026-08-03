<?php

Route::group(['prefix' => 'user-notes', 'middleware' => []], function () {
    Route::get('/sidebar/add', [\App\Modules\Pub\UserNote\Controllers\UserNoteController::class, 'sidebar_add'])->name('user-notes.sidebar_add');
    Route::get('/sidebar/edit/{note}', [\App\Modules\Pub\UserNote\Controllers\UserNoteController::class, 'sidebar_edit'])->name('user-notes.sidebar_edit');
});
