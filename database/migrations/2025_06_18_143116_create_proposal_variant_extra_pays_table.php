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
        Schema::create('proposal_variant_extra_pays', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proposal_variant_id')->unsigned();
            $table->string('name', 200);
            $table->enum('block', ['all', 'software', 'work']);
            $table->enum('type', ['percent', 'fix']);
            $table->double('value');
            $table->double('base')->nullable();
            $table->double('calculated')->nullable();
            $table->double('total_software')->nullable();
            $table->double('total_work')->nullable();

            $table->string('currency')->nullable();

            $table->integer('sort')->default(0);

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
        Schema::dropIfExists('proposal_variant_extra_pays');
    }
};
