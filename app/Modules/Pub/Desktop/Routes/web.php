<?php

use App\Modules\Pub\Desktop\Controllers\DesktopController;
use App\Modules\Pub\Desktop\Controllers\DesktopLibraryController;
use App\Modules\Pub\Desktop\Controllers\DesktopBoxController;

/**
 * Рабочий стол (patch v30): страница стола. Без номера — стол пользователя
 * по умолчанию (DesktopService::home). AJAX — Routes/api.php.
 */
Route::get('/desktop/{desktop?}', [DesktopController::class, 'index'])->whereNumber('desktop')->name('desktop.index');

// библиотека виджетов: разметка панели с превью (этап A4)
Route::get('/desktop/library/{desktop}', [DesktopLibraryController::class, 'index'])->whereNumber('desktop')->name('desktop.library');

// попапы столов: создать, переименовать, сохранить как пресет (этап A4)
Route::get('/desktop/box/desktop/{desktop?}', [DesktopBoxController::class, 'desktop'])->whereNumber('desktop')->name('desktop.box_desktop');
