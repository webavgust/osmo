<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Статус коммерческого предложения.
     *
     * Статус хранится на каждой строке proposals, но по смыслу принадлежит
     * всей группе (КП целиком, все итерации). Запись во все строки группы
     * делает ProposalStatusService — так любой запрос к любой итерации
     * возвращает актуальный статус без join.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('status', 32)->default('in_work')->after('sended_at');
            $table->string('status_reason', 32)->nullable()->after('status');
            $table->string('status_comment', 500)->nullable()->after('status_reason');
            $table->timestamp('status_changed_at')->nullable()->after('status_comment');
            $table->unsignedBigInteger('status_changed_by')->nullable()->after('status_changed_at');

            $table->index('status', 'proposals_status_index');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropIndex('proposals_status_index');
            $table->dropColumn([
                'status',
                'status_reason',
                'status_comment',
                'status_changed_at',
                'status_changed_by',
            ]);
        });
    }
};
