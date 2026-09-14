<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Косвенные изменения в журнале (patch v31).
 *
 * derived — строку посчитал сам портал следом за правкой пользователя (итоги,
 * суммы НДС, цены со скидкой). В ленте такие строки уходят под основные.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('entity_log_changes') || Schema::hasColumn('entity_log_changes', 'derived')) {
            return;
        }

        Schema::table('entity_log_changes', function (Blueprint $table) {
            $table->boolean('derived')->default(false)->after('label');
        });
    }

    public function down()
    {
        if (Schema::hasTable('entity_log_changes') && Schema::hasColumn('entity_log_changes', 'derived')) {
            Schema::table('entity_log_changes', function (Blueprint $table) {
                $table->dropColumn('derived');
            });
        }
    }
};
