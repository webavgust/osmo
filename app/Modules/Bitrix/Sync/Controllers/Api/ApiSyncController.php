<?php

namespace App\Modules\Bitrix\Sync\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Constant\ConstantService;
use App\Modules\Pub\Constant\Models\Constant;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class ApiSyncController extends Controller
{
    public function refresh(string $table, HttpRequest $request)
    {
        $request->validate(['data' => 'string|required']);
        $query = $request->input('data');
        $query = Str::replace('\n', '', $query);
//        dd($query);
        if (!str_contains($query, $table)) return ['result' => 'error', 'answer' => 'Неправильный SQL-запрос'];

        $query = Str::replace('complete_dump', '', $query);
        $arQueries = collect(explode(");", $query))->map(function ($query) {
            return trim($query);
        });
        try {
            DB::connection('bitrix')->table($table)->truncate();
            $arQueries->each(function ($query) {
                if(!$query) return;
                $query  .= ');';

                DB::connection('bitrix')->insert($query);
            });
            DB::connection('bitrix')->commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // или обработайте ошибку по-другому
        }
        $count = DB::connection('bitrix')->table($table)->count();

        ConstantService::setBitrixSyncTime($table, now());

        return [
            'result' => 'success',
            'count' => $count,
            'date' => now()->format('d.m.Y H:i'),
            'answer' => 'Перенесена ' . tools()->num_rus($count, ["записи", "запись", "записей"], 1)
        ];
    }
}
