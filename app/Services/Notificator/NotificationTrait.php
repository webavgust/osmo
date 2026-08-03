<?php

namespace App\Services\Notificator;

use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use function collect;
use function str;

trait NotificationTrait
{
    private int $seconds = 0;
    private $arParams;

    public function __construct(array $arParams = [])
    {
        $this->arParams = collect($arParams);
        if (!empty($this->arParams['release_time'])) {
            if (is_numeric($arParams['release_time']) && $arParams['release_time'] > 0) {
                $this->seconds = $this->arParams['release_time'];
            } elseif ($this->arParams['release_time'] instanceof Carbon && $arParams['release_time']->isFuture()) {
                $this->seconds = $this->arParams['release_time']->diffInSeconds() + 1;
            } elseif (is_string($this->arParams['release_time']) && strtotime($this->arParams['release_time']) > 0 && strtotime($this->arParams['release_time']) > time()) {
                $this->seconds = strtotime($arParams['release_time']) - time();
            }
        }

        if (!empty($this->arParams['template'])) {
            $template = $this->arParams['template'];
            if (View::exists('notifications.' . $template)) {
                $view = View::make('notifications.' . $template, $arParams['template_data'] ?? []);

                $sections = $view->renderSections();

                if (!empty($sections[self::$info['type'] . '_message']))
                    $this->arParams['message'] = trim($sections[self::$info['type'] . '_message']);

                if (!empty($sections[self::$info['type'] . '_title']))
                    $this->arParams['title'] = trim($sections[self::$info['type'] . '_title']);
            }
        }
    }
}
