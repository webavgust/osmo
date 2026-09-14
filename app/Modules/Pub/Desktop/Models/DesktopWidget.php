<?php

namespace App\Modules\Pub\Desktop\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Desktop\Services\WidgetRegistry;

/**
 * Виджет на рабочем столе (patch v30): позиция в сетке на 32 колонки и настройки.
 *
 * uid — постоянный ключ внутри стола (выдаёт браузер), widget — id класса
 * виджета из WidgetRegistry.
 */
class DesktopWidget extends ModuleModel
{
    public static $detail_route = null;

    protected $table = 'desktop_widgets';
    protected $fillable = ['desktop_id', 'uid', 'widget', 'x', 'y', 'w', 'h', 'free_size', 'settings'];
    protected $casts = [
        'settings' => 'array',
        'x' => 'int',
        'y' => 'int',
        'w' => 'int',
        'h' => 'int',
        'free_size' => 'bool',
    ];

    public function desktop()
    {
        return $this->belongsTo(Desktop::class);
    }

    /**
     * Класс виджета; null — виджет удалён из кода
     *
     * @return string|null
     */
    public function widgetClass(): ?string
    {
        return WidgetRegistry::find($this->widget);
    }

    /**
     * Данные для сетки на странице
     *
     * @return array
     */
    public function toGrid(): array
    {
        return [
            'uid' => $this->uid,
            'widget' => $this->widget,
            'x' => $this->x,
            'y' => $this->y,
            'w' => $this->w,
            'h' => $this->h,
            'free_size' => (bool) $this->free_size,
            'settings' => (array) $this->settings,
        ];
    }
}
