<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accesses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('access_group_id')->unsigned();
            $table->tinyInteger('protected')->default(0);
            $table->string('name', 128);
            $table->string('code', 128);
            $table->mediumText('description')->nullable();
            $table->smallInteger('sort')->default(0);
            $table->string('class', 500);
            $table->string('method', 128);
            $table->tinyInteger('admin_invert')->default(0);
            $table->timestamps();


        });


        /*
         *  ОБЩИЕ
         */

        DB::table('accesses')->insert([
            'access_group_id' => 1,
            'protected' => 1,
            'name' => 'Доступ в ХАЛ',
            'code' => 'general_access',
            'class' => \App\Modules\Pub\Access\Policies\AccessGroupPolicy::class,
            'method' => 'access_view'
        ]);


        /*
         *  ДОСТУПЫ
         */

            DB::table('accesses')->insert([
                'access_group_id' => 2,
                'protected' => 1,
                'name' => 'Cоздание доступов',
                'description' => 'Базовое создание доступов для последующего назначения. В основном нужно только для администратора',
                'code' => 'access_create',
                'class' => \App\Modules\Pub\Access\Policies\AccessPolicy::class,
                'method' => 'access_create'
            ]);
            DB::table('accesses')->insert([
                'access_group_id' => 2,
                'protected' => 1,
                'name' => 'Просмотр доступов',
                'description' => 'Возможность просматривать созданные доступы',
                'code' => 'access_view',
                'class' => \App\Modules\Pub\Access\Policies\AccessPolicy::class,
                'method' => 'access_view',
                'sort' => 100
            ]);


//        DB::table('accesses')->insert([
//            'access_group_id' => 2,
//            'protected' => 1,
//            'name' => 'Назначенные доступы для групп (просмотр)',
//            'code' => 'access_groups_view',
//            'class' => \App\Modules\Pub\Access\Policies\AccessPolicy::class,
//            'method' => 'access_groups_view',
//            'sort' => 200
//        ]);
//        DB::table('accesses')->insert([
//            'access_group_id' => 2,
//            'protected' => 1,
//            'name' => 'Назначенные доступы для пользователей (просмотр)',
//            'code' => 'access_users_view',
//            'class' => \App\Modules\Pub\Access\Policies\AccessPolicy::class,
//            'method' => 'access_users_view',
//            'sort' => 300
//        ]);
        /*
         *  ДОСТУПЫ
         */

            DB::table('accesses')->insert([
                'access_group_id' => 3,
                'protected' => 1,
                'name' => 'Управление меню',
                'description' => 'Возможность создания, редактирования и удаления разделов меню и их пунктов',
                'code' => 'menu_control',
                'class' => \App\Modules\Pub\Menu\Policies\MenuPolicy::class,
                'method' => 'menu_control'
            ]);
            DB::table('accesses')->insert([
                'access_group_id' => 3,
                'protected' => 1,
                'name' => 'Просмотр меню',
                'description' => 'Возможность просматривать дерево меню',
                'code' => 'menu_view',
                'class' => \App\Modules\Pub\Menu\Policies\MenuPolicy::class,
                'method' => 'menu_view',
                'sort' => 100
            ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accesses');
    }
};
