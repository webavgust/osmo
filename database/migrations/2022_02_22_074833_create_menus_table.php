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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(1);
            $table->bigInteger('parent_id')->unsigned()->default(0)->index();
            $table->tinyInteger('protected')->default(0);
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('icon', 64)->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });


        $item = new \App\Modules\Pub\Menu\Models\Menu();
        $item->fill([
            'active' => 1,
            'parent_id' => 0,
            'name' => 'Настройки',
        ])
        ->save();
        $item->accesses()->sync([2,3]);
        $parent_id = $item->id;

                $item = new \App\Modules\Pub\Menu\Models\Menu();
                $item->fill([
                    'active' => 1,
                    'parent_id' => $parent_id,
                    'name' => 'Меню',
                    'icon' => 'mdi mdi-file-tree',
                    'url' => '/menu'
                ])
                ->save();
                $item->accesses()->sync([4,5]);


                $item = new \App\Modules\Pub\Menu\Models\Menu();
                $item->fill([
                    'active' => 1,
                    'parent_id' => $parent_id,
                    'name' => 'Доступы',
                    'sort' => 100,
                    'icon' => 'mdi mdi-lock'
                ])
                ->save();
                $item->accesses()->sync([3]);
                $parent_id = $item->id;

                        $item = new \App\Modules\Pub\Menu\Models\Menu();
                        $item->fill([
                            'active' => 1,
                            'parent_id' => $parent_id,
                            'name' => 'Список',
                            'icon' => 'mdi mdi-format-list-bulleted',
                            'url' => '/access'
                        ])
                        ->save();
                        $item->accesses()->sync([3]);



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        Schema::dropIfExists('menus');
    }
};
