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
        Schema::create('softwares', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->mediumText('extended')->nullable();
            $table->mediumText('notice')->nullable();
            $table->float('count')->default(0);
            $table->enum('type', ['once', 'periodical'])->default('once');
            $table->boolean('cb_nds')->default(false);
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
        Schema::dropIfExists('softwares');
    }
};
