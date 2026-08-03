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
        Schema::table('contract_specifications', function (Blueprint $table) {
            $table->string('currency_slug', 8)->after('id')->default('RUB');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_specifications', function (Blueprint $table) {
            $table->dropColumn('currency_slug');
        });
    }
};
