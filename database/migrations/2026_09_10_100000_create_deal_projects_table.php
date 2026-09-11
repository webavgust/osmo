<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Проекты по сделкам Битрикса (patch v24).
 *
 * Сущность «Проект» в интерфейсе, но таблицы и модуль зовутся deal_projects /
 * DealProject: модуль Project в портале уже занят «проектами компании» с
 * конфигурациями для спецификаций.
 *
 * Проект принадлежит партнёру и (почти всегда) одной компании-заказчику.
 * Сделки Битрикса живут в другой базе, поэтому внешнего ключа на них нет —
 * связь лежит в deal_project_deals.
 *
 * Архив — это archived_at, а не удаление: проекты нужны в скоринге (v26)
 * и за прошлые годы тоже.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('deal_projects', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('partner_id')->unsigned();
            $table->bigInteger('company_id')->unsigned()->nullable();
            $table->date('date_start');
            $table->boolean('is_pilot')->default(false);
            $table->date('deadline')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->bigInteger('archived_by')->unsigned()->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->timestamps();

            $table->index('partner_id');
            $table->index('company_id');
            $table->index('date_start');
            $table->index('archived_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deal_projects');
    }
};
