<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Связка КП «главное / второстепенное» (patch v33).
 *
 * Два КП логически объединяются, не склеиваясь: главное участвует во всех
 * расчётах, второстепенное — только просмотр и история. Связка живёт на группах
 * (proposals.group), а не на отдельных редакциях.
 *
 * Второстепенное бывает только у одного главного — отсюда UNIQUE на secondary_group.
 * Charset/collation колонок группы — как у proposals.group, иначе join и
 * подзапросы ругаются на смешение collation.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('proposal_links')) return;

        Schema::create('proposal_links', function (Blueprint $table) {
            $table->id();
            $table->char('main_group', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->index();
            $table->char('secondary_group', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->unique();
            $table->string('comment', 500)->nullable();
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->dateTime('linked_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('proposal_links');
    }
};
