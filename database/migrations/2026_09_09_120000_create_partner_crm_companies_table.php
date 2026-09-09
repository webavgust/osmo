<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Сопоставление партнёров портала с компаниями Битрикс24.
 *
 * Зеркало Битрикса (база avgbitrix) перезаписывается дампом целиком, поэтому
 * колонок туда не добавить — связь живёт здесь, в avgmom, и хранит только id
 * компании. Внешнего ключа на crm_company нет и быть не может: другая база.
 *
 * Одна компания Битрикса принадлежит не более чем одному партнёру
 * (UNIQUE по crm_company_id), у партнёра компаний может быть несколько:
 * «Trafcoo (Taraf AI-Bader)» ↔ «Trafcoo», «Bifu» ↔ «Shanghai Bifu Testing
 * Technology Co., Lt» и т.п.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('partner_crm_companies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('partner_id')->unsigned();
            $table->bigInteger('crm_company_id')->unsigned();
            $table->timestamp('created_at')->nullable();

            $table->unique('crm_company_id');
            $table->index('partner_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('partner_crm_companies');
    }
};
