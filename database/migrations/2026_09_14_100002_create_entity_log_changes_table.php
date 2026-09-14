<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Строки изменений журнала (patch v29): результат диффа двух слепков.
 *
 * kind — changed (поле поменялось), added / removed (появился или исчез
 * дочерний объект — одна строка на объект). path — цепочка подписей
 * родителей («Вариант 2 (1 год) → Нейросервис «X»»), у полей корня null.
 * old_value / new_value — сырые значения, old_label / new_label —
 * человеческие, сформированные в момент записи.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('entity_log_changes')) {
            return;
        }

        Schema::create('entity_log_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_log_id')->index();
            $table->string('kind', 16);
            $table->string('model_class', 255);
            $table->string('model_key', 64);
            $table->string('path', 500)->nullable();
            $table->string('field', 64)->nullable();
            $table->string('label', 128)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('old_label')->nullable();
            $table->text('new_label')->nullable();

            $table->index(['model_class', 'field']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('entity_log_changes');
    }
};
