<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Соответствие сценариев внешней системы нашему справочнику (patch v21).
 *
 * Справочник OSMOVIEW CP совпадает с нашим лишь частично, поэтому пары
 * запоминаются: первые известные засеваются здесь, остальные пользователь
 * выбирает в попапе переноса — выбор сохраняется сюда же.
 */
return new class extends Migration {
    public function up()
    {
        Schema::create('external_scenario_map', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32)->default('osmoview_cp');
            $table->string('external_scenario_id', 32);
            $table->string('external_name', 300)->nullable();
            $table->bigInteger('scenario_id')->unsigned()->nullable();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_scenario_id']);
        });

        // известные пары (проверка 2026-09-09 по образцам КП)
        $pairs = [
            3 => 3,
            4 => 4,
            25 => 28,
            39 => 42,
            36 => 39,
            31 => 34,
            23 => 26,
            14 => 98,
            28 => 115,
        ];

        $exists = DB::table('scenarios')->whereIn('id', array_values($pairs))->pluck('name', 'id');
        $now = now();

        foreach ($pairs as $external => $ours) {
            if (!$exists->has($ours)) continue;

            DB::table('external_scenario_map')->insert([
                'source' => 'osmoview_cp',
                'external_scenario_id' => (string) $external,
                'external_name' => null,
                'scenario_id' => $ours,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('external_scenario_map');
    }
};
