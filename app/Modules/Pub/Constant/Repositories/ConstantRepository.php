<?php

namespace App\Modules\Pub\Constant\Repositories;

use App\Modules\Pub\Constant\Models\Constant;

class ConstantRepository
{
    /**
     * Получить все константы
     *
     * @return \App\Models\ModuleModel[]|\LaravelIdea\Helper\App\Models\_IH_ModuleModel_C
     */
    public function getAll()
    {
        return Constant::all();
    }
}
