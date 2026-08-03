<?php

namespace App\Modules\Bitrix\Sync\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Bitrix\Sync\Services\SyncService;
use App\Modules\Pub\Constant\ConstantService;
use Illuminate\Support\Facades\DB;


class SyncController extends Controller
{
    use HasBreadcrumb;

    public function __construct(
        protected $service = new SyncService(),
    ) {
        $this->breadcrumb_add(null, 'Настройка CRM');
    }

    public function index()
    {
        $tables = DB::connection('bitrix')->select('SHOW TABLES');
        $tables = collect($tables)->map(function($table) {
            return array_values((array)$table)[0];
        })->toArray();


        $times = ConstantService::getBitrixSyncTime();

        return view('bitrix.sync.index', [
            'breadcrumbs' => $this->breadcrumb,
            'tables' => $tables,
            'times' => $times,
        ]);
    }
}
