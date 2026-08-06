<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Привязка КП к сделкам Битрикса — много ко многим.
 *
 * Раньше связь жила в proposals.crm_deal_id (одна сделка на КП). На практике
 * у одного КП бывает несколько сделок: колонка остаётся как «главная сделка»
 * (её читает старый код и фильтры), а полный список — здесь.
 *
 * Ключ — group, а не proposals.id: привязка относится к КП целиком,
 * а не к отдельной итерации.
 */
return new class extends Migration {
    public function up()
    {
        Schema::create('proposal_crm_deals', function (Blueprint $table) {
            $table->id();
            $table->uuid('proposal_group');
            $table->bigInteger('crm_deal_id')->unsigned();
            $table->boolean('is_main')->default(false);
            $table->string('comment', 500)->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->bigInteger('linked_by')->unsigned()->nullable();

            $table->unique(['proposal_group', 'crm_deal_id']);
            $table->index('crm_deal_id');
        });

        // переносим то, что уже привязано
        $rows = DB::table('proposals')
            ->whereNotNull('crm_deal_id')
            ->orderBy('iteration')
            ->get(['group', 'crm_deal_id', 'crm_deal_linked_at', 'crm_deal_linked_by']);

        $insert = [];
        foreach ($rows as $row) {
            $key = $row->group . ':' . $row->crm_deal_id;
            if (isset($insert[$key])) continue;

            $insert[$key] = [
                'proposal_group' => $row->group,
                'crm_deal_id' => $row->crm_deal_id,
                'is_main' => 1,
                'linked_at' => $row->crm_deal_linked_at,
                'linked_by' => $row->crm_deal_linked_by,
            ];
        }

        foreach (array_chunk(array_values($insert), 500) as $chunk) {
            DB::table('proposal_crm_deals')->insert($chunk);
        }
    }

    public function down()
    {
        Schema::dropIfExists('proposal_crm_deals');
    }
};
