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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->boolean('hide')->default(0);
            $table->string('title', 100);
            $table->mediumText('message')->nullable();
            $table->bigInteger('target_id')->unsigned();
            $table->string('target_type', 128);
            $table->string('group', 64);
            $table->bigInteger('created_by')->unsigned();
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
        Schema::dropIfExists('reminders');
    }
};
