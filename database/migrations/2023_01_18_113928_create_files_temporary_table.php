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
        Schema::create('files_temporary', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 32);
            $table->bigInteger('target_id')->unsigned()->nullable();
            $table->string('block', 32);
            $table->bigInteger('block_id')->unsigned()->nullable();
            $table->string('path', 250);
            $table->string('realname', 100);
            $table->string('extension', 10);
            $table->bigInteger('filesize')->unsigned();
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
        Schema::dropIfExists('files_temporary');
    }
};
