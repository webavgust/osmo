<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Несколько причин проигрыша у КП (patch v44).
     *
     * status_reasons — json-массив кодов ProposalLostReason в порядке выбора.
     * status_reason остаётся и всегда равен первой (основной) причине —
     * для старых отчётов и обратной совместимости.
     * Перенос: у КП с заполненной причиной status_reasons = [status_reason].
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->json('status_reasons')->nullable()->after('status_reason');
        });

        DB::table('proposals')
            ->whereNotNull('status_reason')
            ->where('status_reason', '<>', '')
            ->update(['status_reasons' => DB::raw('JSON_ARRAY(status_reason)')]);
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('status_reasons');
        });
    }
};
