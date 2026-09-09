<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КП из внешних систем (patch v21).
 *
 * Первый источник — OSMOVIEW CP Generator Алексея (`source = osmoview_cp`).
 * Строка появляется при синхронизации списка или ручном импорте JSON,
 * `payload` — полный ответ detail. После переноса в наше КП запоминаем
 * `proposal_group`: связь живёт здесь, в `proposals` колонок не добавляем.
 */
return new class extends Migration {
    public function up()
    {
        Schema::create('external_proposals', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32)->default('osmoview_cp');
            $table->string('external_id', 64);                 // doc-id из списка (аргумент detail)
            $table->string('external_number', 32)->nullable(); // номер КП в его системе (AK528)
            $table->string('name', 255)->nullable();
            $table->string('customer', 255)->nullable();
            $table->integer('cameras')->nullable();
            $table->date('created_at_remote')->nullable();
            $table->dateTime('updated_at_remote')->nullable();
            $table->json('list_payload')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->char('proposal_group', 36)->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->bigInteger('transferred_by')->unsigned()->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index('proposal_group');
            $table->index('external_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_proposals');
    }
};
