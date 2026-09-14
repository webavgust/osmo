<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Рабочие столы (patch v30).
 *
 * Стол пользователя — user_id заполнен; системный пресет — user_id = null и
 * is_system = 1 (создаёт админ). is_default: у пользователя — стол, который
 * открывается с домика и после входа; у системных — пресет, который копируется
 * новому пользователю. source_id / source_version — из какого системного
 * пресета и какой его версии скопирован стол (для предложения обновиться).
 * context — значения по умолчанию для виджетов стола: валюта и период.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('desktops')) {
            return;
        }

        Schema::create('desktops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name', 100);
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_default')->default(false);
            $table->integer('sort')->default(0);
            $table->json('context')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedInteger('source_version')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('desktops');
    }
};
