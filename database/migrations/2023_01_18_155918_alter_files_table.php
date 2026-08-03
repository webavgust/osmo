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
        //
        Schema::table('files', function (Blueprint $table) {
            $table->string('disk', 100)->after('id')->default('public');
            $table->string('target_block', 100)->after('target_type')->nullable();
            $table->bigInteger('target_block_id')->after('target_block')->nullable();
            $table->boolean('is_locked')->after('target_block_id')->defult(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('disk');
            $table->dropColumn('target_block');
            $table->dropColumn('target_block_id');
            $table->dropColumn('is_locked');
        });
    }
};
