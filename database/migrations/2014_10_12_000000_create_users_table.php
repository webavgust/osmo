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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('login', 64);
            $table->string('password');
            $table->tinyInteger('active')->unsigned()->default(0);
            $table->string('last_name', 64)->nullable();
            $table->string('second_name', 64)->nullable();
            $table->tinyInteger('personal_gender')->unsigned()->nullable(); // 0 - женщина, 1 - мужчина
            $table->string('personal_photo', 1024)->nullable();
            $table->string('personal_mobile', 64)->nullable();
            $table->string('work_department', 100)->nullable();
            $table->string('work_position', 100)->nullable();
            $table->string('work_phone', 20)->nullable();
            $table->date('personal_birthday')->nullable();
            $table->tinyInteger('is_sync')->unsigned()->default(0);
            $table->tinyInteger('is_admin')->unsigned()->default(0);
            $table->string('api_token', 60)->nullable();
            $table->string('ajax_token', 60)->nullable();
            $table->integer('telegram_id')->nullable();
            $table->string('initials', 8)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
