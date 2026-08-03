<?php

namespace App\Modules\Bitrix\Sync\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\Sync\Services\SyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class SyncBoxController extends Controller
{

    public function refresh(string $table)
    {
//        if(!DB::connection('bitrix')->table($table)->exists()) abort(404);

        $query = SyncService::generateQuery($table);

        return View::make('bitrix.sync.box.refresh', [
            'title' => 'Обновление данных в таблице',
            'table' => $table,
            'query' => $query,
        ]);
    }
}
