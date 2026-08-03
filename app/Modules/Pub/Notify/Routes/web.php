<?php

Route::group(['prefix' => 'notify'], function () {
    Route::get('/toast/{notify?}', [\App\Modules\Pub\Notify\Controllers\NotifyController::class, 'toast'])->name('notify.toast');
    Route::get('/header', [\App\Modules\Pub\Notify\Controllers\NotifyController::class, 'header'])->name('notify.header');
    Route::get('/delete/{notify?}', [\App\Modules\Pub\Notify\Controllers\NotifyController::class, 'delete'])->name('notify.delete');
    Route::get('/clear', [\App\Modules\Pub\Notify\Controllers\NotifyController::class, 'clear'])->name('notify.clear');

    Route::get('/list', [\App\Modules\Pub\Notify\Controllers\NotifyController::class, 'list'])->name('notify.list');
});
