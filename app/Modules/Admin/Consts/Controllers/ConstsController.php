<?php

namespace App\Modules\Admin\Consts\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Consts\Services\AdminConstService;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Constant\Models\Constant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Админ-панель: константы портала (patch v28, этап C).
 *
 * Доступ — Gate admin_panel на всю группу Admin (config/modular.php).
 * Сохранение и удаление — AJAX в Api\ApiConstsController.
 */
class ConstsController extends Controller
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('admin.index'), 'Админ-панель');
    }

    /**
     * Список констант
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $this->breadcrumb_add(route('admin.consts.index'), 'Константы');

        $params = AdminConstService::params($request->all());

        return View::make('admin.consts.index', [
            'breadcrumbs' => $this->breadcrumb,
            'params' => $params,
            'consts' => AdminConstService::list($params),
            'total' => Constant::count(),
        ]);
    }

    /**
     * Попап создания / редактирования константы
     *
     * @param Constant|null $constant null — новая константа
     * @return \Illuminate\Contracts\View\View
     */
    public function box_form(Constant $constant = null)
    {
        return View::make('admin.consts.boxes.form', [
            // заголовок попапа выводится без экранирования — экранируем здесь
            'title' => $constant ? 'Константа ' . e($constant->key) : 'Новая константа',
            'constant' => $constant,
            'is_json' => $constant && AdminConstService::isJson($constant->value),
        ]);
    }
}
