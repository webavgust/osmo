<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Сделки проекта (patch v24).
 *
 * UNIQUE по crm_deal_id и есть правило «у сделки не больше одного проекта»:
 * к проекту сделок можно прикрепить несколько, но каждая принадлежит ровно
 * одному проекту. Внешнего ключа на crm_deal нет и быть не может — сделки
 * лежат в зеркале Битрикса (база avgbitrix), которое перезаливается целиком.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('deal_project_deals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('deal_project_id')->unsigned();
            $table->bigInteger('crm_deal_id')->unsigned();
            $table->timestamp('attached_at')->nullable();
            $table->bigInteger('attached_by')->unsigned()->nullable();

            $table->unique('crm_deal_id');
            $table->index('deal_project_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deal_project_deals');
    }
};
