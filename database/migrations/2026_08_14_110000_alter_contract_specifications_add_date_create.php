<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Дата создания спецификации.
 *
 * До этого у спецификации своей даты не было, и всё, что требовало даты
 * (срок сделки, разбивка по годам), считалось по дате рамочного договора либо
 * по дате прикрепления КП. Теперь дата есть у самой спецификации.
 *
 * Существующим записям ставим дату договора — ближайшее к правде, что есть.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('contract_specifications', function (Blueprint $table) {
            $table->date('date_create')->nullable()->after('name');
        });

        // проставим дату рамочного договора там, где своей нет
        \DB::statement('
            UPDATE contract_specifications s
            JOIN contracts c ON c.id = s.contract_id
            SET s.date_create = c.date
            WHERE s.date_create IS NULL AND c.date IS NOT NULL
        ');
    }

    public function down()
    {
        Schema::table('contract_specifications', function (Blueprint $table) {
            $table->dropColumn('date_create');
        });
    }
};
