<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Виджеты на рабочем столе (patch v30).
 *
 * uid — постоянный ключ виджета внутри стола (его выдаёт браузер при добавлении,
 * по нему сохраняется раскладка). widget — id класса виджета (WidgetRegistry).
 * x, y, w, h — позиция и размер в ячейках сетки на 32 колонки.
 * settings — настройки виджета по его схеме.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('desktop_widgets')) {
            return;
        }

        Schema::create('desktop_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('desktop_id')->index();
            $table->string('uid', 32);
            $table->string('widget', 64);
            $table->unsignedSmallInteger('x')->default(0);
            $table->unsignedSmallInteger('y')->default(0);
            $table->unsignedSmallInteger('w')->default(4);
            $table->unsignedSmallInteger('h')->default(2);
            // размер задан вручную (отжат замок), к размерам виджета не приводится
            $table->boolean('free_size')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['desktop_id', 'uid']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('desktop_widgets');
    }
};
