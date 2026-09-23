<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Заметка как задача: отметка «выполнено» (виджет «Блокнот» на рабочем столе).
 *
 * done_at — когда задачу отметили выполненной; NULL — в работе. По времени отметки
 * виджет убирает выполненные задачи через сутки.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('user_notes', 'done_at')) return;

        Schema::table('user_notes', function (Blueprint $table) {
            $table->timestamp('done_at')->nullable()->after('favorite');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('user_notes', 'done_at')) return;

        Schema::table('user_notes', function (Blueprint $table) {
            $table->dropColumn('done_at');
        });
    }
};
