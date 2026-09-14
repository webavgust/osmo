<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Флаг «видит журнал изменений» (patch v29).
 *
 * Логируются изменения для всех, а смотреть ленту могут администраторы
 * панели и те, кому в админ-панели поставили эту галочку
 * (Gate entity_log_view, User::canViewEntityLog()).
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('users', 'log_view')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('log_view')->default(false)->after('ui_theme_switch');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('users', 'log_view')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('log_view');
        });
    }
};
