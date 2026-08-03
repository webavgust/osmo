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
        Schema::create('calendar', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->smallInteger('duration')->nullable();
            $table->string('mode', 64);
            $table->boolean('all_day')->default(false);
            $table->string("title_icon", 64);
            $table->string("title", 100);
            $table->mediumText("text")->nullable();
            $table->string('color', 32)->default('secondary');
            $table->bigInteger('notify_id')->unsigned()->nullable();
            $table->boolean('editable')->default(true);
            $table->bigInteger('target_id')->unsigned();
            $table->string('target_type', 64);
            $table->string('target_sub', 64);
            $table->softDeletes();
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
        Schema::dropIfExists('calendar');
    }
};
