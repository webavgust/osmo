<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Константы портала (patch v28, админ-панель, этап C).
 *
 * - `note` — примечание: за что отвечает константа, где читается, в каких единицах;
 * - уникальный индекс на `key` (в интерфейсе — «Код»): код читают по нему,
 *   дубль сделал бы результат `Constant::get()` непредсказуемым.
 *
 * Перед созданием индекса проверяем, что дублей нет, — иначе останавливаемся
 * с понятным списком, а не с ошибкой MySQL.
 */
return new class extends Migration
{
    /** Имя уникального индекса */
    protected const INDEX = 'consts_key_unique';

    public function up()
    {
        if (!Schema::hasColumn('consts', 'note')) {
            Schema::table('consts', function (Blueprint $table) {
                $table->text('note')->nullable()->after('value');
            });
        }

        if ($this->hasIndex()) {
            return;
        }

        $duplicates = DB::table('consts')
            ->select('key', DB::raw('COUNT(*) as cnt'))
            ->groupBy('key')
            ->having('cnt', '>', 1)
            ->pluck('cnt', 'key');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('В consts есть повторяющиеся коды, уникальный индекс не создан: '
                . $duplicates->map(fn($count, $key) => $key . ' ×' . $count)->implode(', '));
        }

        Schema::table('consts', function (Blueprint $table) {
            $table->unique('key', self::INDEX);
        });
    }

    public function down()
    {
        if ($this->hasIndex()) {
            Schema::table('consts', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        }

        if (Schema::hasColumn('consts', 'note')) {
            Schema::table('consts', function (Blueprint $table) {
                $table->dropColumn('note');
            });
        }
    }

    /**
     * Есть ли уникальный индекс на `key`
     *
     * @return bool
     */
    protected function hasIndex(): bool
    {
        return !empty(DB::select('SHOW INDEX FROM `consts` WHERE `Key_name` = ?', [self::INDEX]));
    }
};
