<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_groups', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('protected')->default(0);
            $table->string('name', 100);
            $table->string('prefix', 64)->nullable();
            $table->string('icon', 64)->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        DB::table('access_groups')->insert(
            [
                'id' => 1,
                'protected' => 1,
                'name' => 'Общие',
                'prefix' => 'general_',
                'sort' => 0,
                'icon' => 'mdi mdi-settings'
            ]
        );

        DB::table('access_groups')->insert(
            [
                'id' => 2,
                'protected' => 1,
                'name' => 'Доступы',
                'prefix' => 'access_',
                'sort' => 0,
                'icon' => 'mdi mdi-lock'
            ]
        );

        DB::table('access_groups')->insert(
            [
                'id' => 3,
                'protected' => 1,
                'name' => 'Работа с меню',
                'prefix' => 'menu_',
                'sort' => 100,
                'icon' => 'mdi mdi-file-tree'
            ]
        );


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('access_groups');
    }
};
