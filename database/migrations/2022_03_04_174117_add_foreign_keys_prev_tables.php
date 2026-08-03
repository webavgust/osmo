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
//        Schema::table('accesses', function (Blueprint $table) {
//            $table->foreign('access_group_id')->references('id')->on('access_groups')->onDelete('cascade');
//        });
//
//        Schema::table('user_user_group', function (Blueprint $table) {
//            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
//            $table->foreign('user_group_id')->references('id')->on('user_groups')->onDelete('cascade');
//        });
//
//        Schema::table('user_user_department', function (Blueprint $table) {
//            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
//            $table->foreign('user_department_id')->references('id')->on('user_departments')->onDelete('cascade');
//        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
//        Schema::table('accesses', function (Blueprint $table) {
//            $table->dropForeign('accesses_access_group_id_foreign');
//            $table->dropIndex('accesses_access_group_id_foreign');
//        });
    }
};
