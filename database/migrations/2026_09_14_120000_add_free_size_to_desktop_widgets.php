<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Свободный размер виджета (patch v30).
 *
 * free_size — на блоке отжат замок: размер задан вручную и не приводится к списку
 * размеров виджета ни при сохранении, ни при отрисовке.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('desktop_widgets') || Schema::hasColumn('desktop_widgets', 'free_size')) {
            return;
        }

        Schema::table('desktop_widgets', function (Blueprint $table) {
            $table->boolean('free_size')->default(false)->after('h');
        });
    }

    public function down()
    {
        if (Schema::hasTable('desktop_widgets') && Schema::hasColumn('desktop_widgets', 'free_size')) {
            Schema::table('desktop_widgets', function (Blueprint $table) {
                $table->dropColumn('free_size');
            });
        }
    }
};
