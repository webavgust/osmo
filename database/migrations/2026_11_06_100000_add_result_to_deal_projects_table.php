<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Итог закрытого проекта (patch v48).
 *
 * Закрытие проекта — это архив (archived_at/archived_by) плюс итог:
 * success — успешно, fail — нет. Итог обязателен при закрытии и
 * очищается при возврате проекта в работу. Комментарий итога — по желанию.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('deal_projects', function (Blueprint $table) {
            $table->string('result', 16)->nullable()->after('archived_by');
            $table->text('result_comment')->nullable()->after('result');
        });
    }

    public function down()
    {
        Schema::table('deal_projects', function (Blueprint $table) {
            $table->dropColumn(['result', 'result_comment']);
        });
    }
};
