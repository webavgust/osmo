<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Журнал изменений сущностей (patch v29): слепки агрегатов.
 *
 * Одна строка — одно событие по корню (КП, партнёр, компания): baseline
 * (первый слепок), created, updated, deleted. В data лежит JSON-слепок
 * агрегата целиком (у deleted — null), в changes_count — число строк
 * в entity_log_changes.
 *
 * type — слаг корня (proposal / partner / company), group_key — ключ ленты
 * (у КП — group, общий для всех редакций; у остальных — id), model_id — id
 * конкретной строки (у КП — редакции).
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('entity_logs')) {
            return;
        }

        Schema::create('entity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);
            $table->string('group_key', 64);
            $table->unsignedBigInteger('model_id');
            $table->string('event', 16);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title', 255);
            $table->longText('data')->nullable();
            $table->unsignedInteger('changes_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'group_key', 'created_at']);
            $table->index(['type', 'model_id']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('entity_logs');
    }
};
