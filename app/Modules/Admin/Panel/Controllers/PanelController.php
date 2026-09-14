<?php

namespace App\Modules\Admin\Panel\Controllers;

use App\Http\Controllers\Controller;

/**
 * Админ-панель (patch v28, этап B).
 *
 * Весь раздел закрыт Gate admin_panel — middleware `can:admin_panel`
 * навешивается на группу Admin в config/modular.php.
 */
class PanelController extends Controller
{
    /**
     * Главная админ-панели: своей страницы нет, ведём в список пользователей
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        return redirect()->route('admin.users.index');
    }
}
