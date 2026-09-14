<?php

namespace App\Modules\Pub\Desktop\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Desktop\Models\Desktop;
use App\Modules\Pub\Desktop\Services\DesktopService;
use App\Modules\Pub\Desktop\Services\WidgetRegistry;
use App\Support\UiTheme;
use Illuminate\Support\Facades\View;

/**
 * Рабочий стол (patch v30): страница стола — сетка виджетов GridStack.
 *
 * Стол свёрстан только в Metronic, в старой теме вместо него воронка продаж
 * (решение владельца). Раскладка, отрисовка виджетов и столы — AJAX
 * в Api\ApiDesktopController, фронт — public/metronic/js/osmo-desktop.js.
 */
class DesktopController extends Controller
{
    use HasBreadcrumb;

    /**
     * Страница стола: без номера — стол пользователя по умолчанию
     *
     * @param Desktop|null $desktop
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(?Desktop $desktop = null)
    {
        if (!UiTheme::is('metronic')) {
            return redirect()->route('dashboard.index');
        }

        $user = auth()->user();
        $desktop ??= DesktopService::home($user);

        if (!$desktop->canView($user)) {
            abort(403, 'Этот стол доступен только его владельцу');
        }

        $this->breadcrumb_add(null, 'Рабочий стол');

        // описания виджетов, доступных пользователю: для ресайза, контекста и библиотеки
        $meta = [];
        foreach (WidgetRegistry::all() as $id => $class) {
            if ($class::available($user)) {
                $meta[$id] = $class::meta();
            }
        }

        return View::make('pub.desktop.index', [
            'breadcrumbs' => $this->breadcrumb,
            'desktop' => $desktop,
            'summary' => DesktopService::summary($desktop, $user),
            'items' => DesktopService::gridItems($desktop, $user),
            'desktops' => DesktopService::forUser($user),
            'systems' => DesktopService::systems(),
            'context' => DesktopService::context($desktop, $user)->toArray(),
            'meta' => $meta,
            'config' => [
                'columns' => (int) config('desktop.columns', 32),
                'narrow_columns' => (int) config('desktop.narrow_columns', 16),
                'narrow_width' => (int) config('desktop.narrow_width', 1200),
                'list_width' => (int) config('desktop.list_width', 768),
                'min_cell' => (int) config('desktop.min_cell', 46),
                'gridstack' => (string) config('desktop.gridstack', '11.1.2'),
            ],
        ]);
    }
}
