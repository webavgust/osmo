<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Привязка КП к спецификации рамочного договора.
 *
 * Связь много-ко-многим: в одну спецификацию может лечь несколько КП,
 * а одно КП — попасть в спецификации по услугам и по ПО одновременно.
 *
 * Храним группу КП, а не id редакции: прикрепляется КП целиком, а не одна
 * его редакция. Дата прикрепления нужна для показателя «КП → договор».
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('contract_specification_proposals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contract_specification_id')->unsigned();
            $table->uuid('proposal_group');
            $table->timestamp('attached_at')->nullable();
            $table->bigInteger('attached_by')->unsigned()->nullable();

            $table->unique(['contract_specification_id', 'proposal_group'], 'spec_proposal_unique');
            $table->index('proposal_group');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_specification_proposals');
    }
};
