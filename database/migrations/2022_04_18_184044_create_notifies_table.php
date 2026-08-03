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
        Schema::create('notifies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('link', 250)->nullable();
            $table->string('icon', 32)->nullable();
            $table->string('title', 100)->nullable();
            $table->mediumText('message')->nullable();
            $table->boolean('toastr')->default(0);
            $table->boolean('showed')->default(0);
            $table->timestamp('toastr_showed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id', 'foreign__notifies__user_id')->references('id')->on('users')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifies');
    }
};
