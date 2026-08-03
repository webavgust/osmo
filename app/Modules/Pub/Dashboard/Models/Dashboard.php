<?php

namespace App\Modules\Pub\Dashboard\Models;

use App\Models\ModuleModel;

class Dashboard extends ModuleModel
{
    public static $module_name = 'Рабочий стол';
    const MODES = [
        'self' => [
            'name' => 'Только себя',
            'button' => 'Свои данные'
        ],
        'all' => [
            'name' => 'Всех подчинённых',
            'button' => 'Все подчинённые'
        ],
        'select' => [
            'name' => 'Выбрать из списка',
            'button' => 'Выбрано '
        ]
    ];

}
