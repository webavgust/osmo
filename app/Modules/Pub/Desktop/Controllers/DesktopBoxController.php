<?php

namespace App\Modules\Pub\Desktop\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Desktop\Models\Desktop;
use App\Modules\Pub\Desktop\Services\DesktopService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Попапы рабочего стола (patch v30, этап A4c1): новый стол, переименование,
 * копия, сохранение как системного пресета.
 *
 * Отправку формы делает Desk.desks.submit() (public/metronic/js/osmo-desktop-desks.js),
 * запросы — Api\ApiDesktopController (store, update, copy).
 */
class DesktopBoxController extends Controller
{
    /** Режимы попапа: заголовок и подпись кнопки */
    protected const MODES = [
        'create' => ['title' => 'Новый рабочий стол', 'button' => 'Создать'],
        'rename' => ['title' => 'Переименовать стол', 'button' => 'Сохранить'],
        'copy' => ['title' => 'Копировать стол', 'button' => 'Создать'],
        'system' => ['title' => 'Сохранить как системный пресет', 'button' => 'Создать'],
    ];

    /**
     * Попап стола: ?mode=create|rename|copy|system
     *
     * @param Request $request
     * @param Desktop|null $desktop текущий стол (для create и system — необязательно)
     * @return \Illuminate\Contracts\View\View
     */
    public function desktop(Request $request, ?Desktop $desktop = null)
    {
        $mode = (string) $request->query('mode', 'create');
        if (!isset(static::MODES[$mode])) {
            abort(404);
        }

        $user = auth()->user();

        try {
            switch ($mode) {
                case 'rename':
                    $desktop ?? abort(404);
                    DesktopService::assertEdit($desktop, $user);
                    $name = $desktop->name;
                    break;

                case 'copy':
                    $desktop ?? abort(404);
                    DesktopService::assertView($desktop, $user);
                    $name = $desktop->name . ' (копия)';
                    break;

                case 'system':
                    if (!$user->isPanelAdmin()) {
                        abort(403, 'Системные пресеты создаёт только администратор');
                    }
                    if ($desktop) {
                        DesktopService::assertView($desktop, $user);
                    }
                    $name = $desktop ? $desktop->name : '';
                    break;

                default:
                    $name = '';
            }
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return View::make('pub.desktop.boxes.desktop', [
            'title' => static::MODES[$mode]['title'],
            'button' => static::MODES[$mode]['button'],
            'mode' => $mode,
            'desktop' => $desktop,
            'name' => mb_substr($name, 0, 100),
            // «Скопировать текущий стол» при создании — если открыт стол, который можно смотреть
            'can_copy' => $mode === 'create' && $desktop && $desktop->canView($user),
        ]);
    }
}
