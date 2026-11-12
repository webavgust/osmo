<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Периоды действия лицензионного ключа (patch v49, часть C).
 *
 * Ключ продлевается, а не выдаётся заново: у одного ключа несколько периодов,
 * у каждого своя спецификация и даты. Поля license_keys.contract_specification_id /
 * active_from / active_to остаются и всегда равны текущему (последнему по active_to)
 * периоду — LicenseKey::syncFromPeriods().
 *
 * Каждый существующий ключ переносится в один период с его текущими спецификацией и датами.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('license_key_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_key_id')->constrained('license_keys')->cascadeOnDelete();
            $table->bigInteger('contract_specification_id')->unsigned()->nullable()->index();
            $table->date('active_from');
            $table->date('active_to');
            $table->timestamps();
        });

        // перенос: один ключ — один период
        DB::statement('
            INSERT INTO license_key_periods (license_key_id, contract_specification_id, active_from, active_to, created_at, updated_at)
            SELECT id, contract_specification_id, active_from, active_to, NOW(), NOW()
            FROM license_keys
            ORDER BY id
        ');
    }

    public function down()
    {
        Schema::dropIfExists('license_key_periods');
    }
};
