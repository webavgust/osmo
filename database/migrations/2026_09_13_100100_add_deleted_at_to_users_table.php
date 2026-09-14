<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Мягкое удаление пользователей (patch v28, админ-панель).
 *
 * На пользователя ссылаются КП, платежи, проекты, напоминания и журналы без
 * внешних ключей, поэтому удаление из админ-панели только мягкое: строка
 * остаётся, имена в истории не пропадают, пользователя можно восстановить.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('users', 'deleted_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('users', 'deleted_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
