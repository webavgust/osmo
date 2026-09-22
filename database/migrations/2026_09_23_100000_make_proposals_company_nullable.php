<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Компания в КП необязательна (решение владельца 23.09.2026).
 *
 * Пока заказчик неизвестен — или это собственный запрос партнёра, — компания в КП не указывается.
 * Раньше поле было обязательным, и под такие КП заводили карточки-заглушки с именем партнёра.
 * Внешнего ключа на companies нет, doctrine/dbal в проекте нет — меняем колонку SQL.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE proposals MODIFY company_id BIGINT UNSIGNED NULL');
    }

    public function down()
    {
        // откат возможен, только когда у всех КП указана компания
        DB::statement('ALTER TABLE proposals MODIFY company_id BIGINT UNSIGNED NOT NULL');
    }
};
