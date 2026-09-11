<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Спецификации проекта (patch v24).
 *
 * from_proposal = 1 — спецификация приехала из КП сделок проекта
 * (contract_specification_proposals). Такие в попапе отмечены и не снимаются:
 * связь «сделка → КП → спецификация» уже существует, руками её не отменить.
 * Остальные ставятся вручную.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('deal_project_specifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('deal_project_id')->unsigned();
            $table->bigInteger('contract_specification_id')->unsigned();
            $table->boolean('from_proposal')->default(false);

            $table->unique(['deal_project_id', 'contract_specification_id'], 'deal_project_spec_unique');
            $table->index('contract_specification_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deal_project_specifications');
    }
};
