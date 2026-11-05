<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ответственный менеджер договора (patch v46).
     *
     * Его инициалы входят в номер договора. У старых договоров поле остаётся пустым.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('contracts', 'manager_id')) return;

        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id')->nullable()->after('partner_id');
            $table->index('manager_id');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('contracts', 'manager_id')) return;

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['manager_id']);
            $table->dropColumn('manager_id');
        });
    }
};
