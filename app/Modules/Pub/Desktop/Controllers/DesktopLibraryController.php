<?php

namespace App\Modules\Pub\Desktop\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Desktop\Models\Desktop;
use App\Modules\Pub\Desktop\Services\DesktopService;
use App\Modules\Pub\Desktop\Services\WidgetRegistry;
use App\Support\UiTheme;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\View;

/**
 * Библиотека виджетов рабочего стола (patch v30).
 *
 * Отдаёт разметку панели фрагментом: категории, карточки виджетов с превью
 * в размере по умолчанию на образцовых данных (Widget::sample()). Поведение
 * панели (поиск, вкладки, добавление, перетаскивание на сетку) —
 * public/metronic/js/osmo-desktop-library.js.
 */
class DesktopLibraryController extends Controller
{
    /** Ширина и высота области превью в карточке нижней панели (карточка от 320 px), px */
    protected const PREVIEW_WIDTH = 290;
    protected const PREVIEW_HEIGHT = 170;

    /**
     * Разметка библиотеки для стола
     *
     * @param Desktop $desktop
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Desktop $desktop)
    {
        $user = auth()->user();

        try {
            DesktopService::assertView($desktop, $user);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        // библиотека есть только в Metronic
        if (!UiTheme::is('metronic')) {
            UiTheme::use('metronic');
            View::getFinder()->prependLocation(resource_path('views/themes/metronic'));
            View::getFinder()->flush();
        }

        $ctx = DesktopService::context($desktop, $user);
        $categories = WidgetRegistry::forUser($user);
        $previews = [];

        foreach ($categories as $category) {
            foreach ($category['widgets'] as $meta) {
                $class = WidgetRegistry::find($meta['id']);
                [$w, $h] = $class::parseSize($meta['default_size']);

                // ячейка превью: блок целиком помещается в карточку; маленькие виджеты
                // крупнее (до 40 px — как на широком экране), но не мельче 8 px
                $cell = max(8, min(40, intdiv(static::PREVIEW_WIDTH, max(1, $w)), intdiv(static::PREVIEW_HEIGHT, max(1, $h))));

                $previews[$meta['id']] = [
                    'w' => $w,
                    'h' => $h,
                    'cell' => $cell,
                    'html' => app($class)->html($w, $h, $class::previewSettings(), $ctx, true),
                ];
            }
        }

        return View::make('pub.desktop.library', [
            'desktop' => $desktop,
            'categories' => $categories,
            'previews' => $previews,
            'total' => array_sum(array_map(fn($category) => count($category['widgets']), $categories)),
        ]);
    }
}
