<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Флаг «может переключать тему оформления» (patch v28).
 *
 * Раньше переключатель показывался всем либо только администраторам
 * (config ui.switch_admin_only). Признак админа теперь означает доступ
 * в админ-панель, поэтому переключатель получил свой флаг: по умолчанию
 * включён (как было у всех), в админ-панели его можно снять — тогда
 * пользователь всегда работает в Metronic.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('users', 'ui_theme_switch')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('ui_theme_switch')->default(true)->after('is_admin');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('users', 'ui_theme_switch')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ui_theme_switch');
        });
    }
};
