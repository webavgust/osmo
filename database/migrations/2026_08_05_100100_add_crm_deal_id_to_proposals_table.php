<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Связь КП со сделкой Битрикса.
     *
     * Связь 1:1 — одна сделка принадлежит одному КП (группе). Индекс не
     * уникальный, потому что итерации одного КП — это несколько строк
     * с одинаковым group и одним и тем же crm_deal_id. Уникальность
     * «одна сделка — одно КП» проверяет ProposalDealService.
     *
     * Внешнего ключа нет: crm_deal живёт в отдельной БД (соединение bitrix).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->unsignedBigInteger('crm_deal_id')->nullable()->after('company_id');
            $table->timestamp('crm_deal_linked_at')->nullable()->after('crm_deal_id');
            $table->unsignedBigInteger('crm_deal_linked_by')->nullable()->after('crm_deal_linked_at');

            $table->index('crm_deal_id', 'proposals_crm_deal_id_index');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropIndex('proposals_crm_deal_id_index');
            $table->dropColumn(['crm_deal_id', 'crm_deal_linked_at', 'crm_deal_linked_by']);
        });
    }
};
