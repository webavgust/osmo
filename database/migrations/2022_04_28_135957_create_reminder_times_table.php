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
        Schema::create('reminder_times', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('reminder_id')->unsigned();
            $table->json('notificators');
            $table->datetime('notify_at');
            $table->bigInteger('job_id')->unsigned();
            $table->boolean('notified')->default(0);
            $table->timestamps();


            $table->foreign('reminder_id', 'foreign__reminder_times__reminder_id')->references('id')->on('reminders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminder_times');
    }
};
