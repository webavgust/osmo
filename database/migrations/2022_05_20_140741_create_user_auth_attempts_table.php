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
        Schema::create('user_auth_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('login',64);
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->boolean('success');
            $table->ipAddress('ip');
            $table->uuid('unique_token');
            $table->string('user_agent', 250);
            $table->dateTime('attempted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_auth_attempts');
    }
};
