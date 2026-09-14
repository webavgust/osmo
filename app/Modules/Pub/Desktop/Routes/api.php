<?php

use App\Modules\Pub\Desktop\Controllers\Api\ApiDesktopController;
use App\Modules\Pub\Desktop\Controllers\Api\ApiDesktopSettingsController;

// рабочий стол (patch v30): раскладка, отрисовка виджетов, столы и пресеты
Route::group(['prefix' => 'desktop', 'middleware' => ['ajax.api']], function () {
    Route::post('/render/{desktop}', [ApiDesktopController::class, 'render'])->name('api.desktop.render');
    Route::post('/render_batch/{desktop}', [ApiDesktopController::class, 'renderBatch'])->name('api.desktop.render_batch');
    Route::post('/save/{desktop}', [ApiDesktopController::class, 'save'])->name('api.desktop.save');
    Route::post('/context/{desktop}', [ApiDesktopController::class, 'context'])->name('api.desktop.context');
    Route::post('/store', [ApiDesktopController::class, 'store'])->name('api.desktop.store');
    Route::post('/update/{desktop}', [ApiDesktopController::class, 'update'])->name('api.desktop.update');
    Route::post('/delete/{desktop}', [ApiDesktopController::class, 'delete'])->name('api.desktop.delete');
    Route::post('/default/{desktop}', [ApiDesktopController::class, 'makeDefault'])->name('api.desktop.default');
    Route::post('/copy/{desktop}', [ApiDesktopController::class, 'copy'])->name('api.desktop.copy');
    Route::post('/apply_source/{desktop}', [ApiDesktopController::class, 'applySource'])->name('api.desktop.apply_source');

    // попап настроек виджета (HTML) и поиск объектов для полей типа entity (этап A4)
    Route::post('/settings_form/{desktop}', [ApiDesktopSettingsController::class, 'form'])->name('api.desktop.settings_form');
    Route::get('/search', [ApiDesktopSettingsController::class, 'search'])->name('api.desktop.search');
});
