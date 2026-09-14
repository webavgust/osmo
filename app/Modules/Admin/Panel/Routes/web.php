<?php

use App\Modules\Admin\Panel\Controllers\PanelController;

/**
 * Админ-панель (patch v28). ModularProvider сам кладёт web-маршруты группы
 * Admin под префикс адреса /admin и middleware из config/modular.php
 * (auth + can:admin_panel), а вот префикс имён не добавляет — имена `admin.*`
 * пишутся здесь явно.
 */
Route::get('/', [PanelController::class, 'index'])->name('admin.index');
