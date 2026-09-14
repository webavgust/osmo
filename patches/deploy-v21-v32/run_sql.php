<?php
/*
 * Выполнить SQL-файлы патчей через подключение портала (настройки из .env через Laravel,
 * пароль нигде не печатается). Запускать из корня сайта.
 *
 *   php patches/deploy-v21-v32/run_sql.php --dry-run файл.sql [файл.sql …]  — выполнить и откатить
 *   php patches/deploy-v21-v32/run_sql.php файл.sql [файл.sql …]            — выполнить
 *
 * Строки-комментарии («-- …») вырезаются до разбиения, поэтому запрос сразу после шапки файла
 * не теряется. Запросы делятся по «;» в конце строки. Каждый файл — в своей транзакции:
 * ошибка в середине файла откатывает весь файл, следующие файлы не выполняются. В файлах
 * патчей только UPDATE / INSERT / DELETE (без DDL), поэтому --dry-run честно показывает, сколько
 * строк изменит каждый запрос на этих данных, и откатывает всё.
 *
 * Запросы идут через PDO::exec, а не через подготовку: в строках-значениях есть JSON вида
 * {"0":75}, и подготовка PDO приняла бы «:75» за именованный параметр.
 */

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$args = array_slice($argv, 1);
$dry = in_array('--dry-run', $args, true);
$files = array_values(array_filter($args, fn($arg) => $arg !== '--dry-run'));

if (!$files) {
    exit("Укажите SQL-файлы: php patches/deploy-v21-v32/run_sql.php [--dry-run] файл.sql …\n");
}

printf("База: %s%s\n", config('database.connections.' . config('database.default') . '.database'), $dry ? ' (dry-run: выполняется и откатывается)' : '');
$pdo = DB::connection()->getPdo();

foreach ($files as $file) {
    if (!is_file($file)) {
        exit("Файл не найден: {$file}\n");
    }

    // комментарии целыми строками — прочь; внутри строк-значений «--» не встречается в начале строки
    $sql = preg_replace('~^\s*--[^\n]*$~m', '', file_get_contents($file));
    $queries = array_values(array_filter(array_map('trim', preg_split('~;\s*(\n|$)~', $sql)), fn($q) => $q !== ''));

    printf("\n== %s: запросов %d\n", $file, count($queries));

    try {
        DB::beginTransaction();
        foreach ($queries as $i => $query) {
            $short = mb_substr(preg_replace('~\s+~', ' ', $query), 0, 90);
            $affected = $pdo->exec($query);
            printf("  %2d. строк: %d — %s…\n", $i + 1, (int) $affected, $short);
        }
        if ($dry) {
            DB::rollBack();
            echo "  (откатано)\n";
        } else {
            DB::commit();
        }
    } catch (\Throwable $e) {
        DB::rollBack();
        printf("ОШИБКА в %s: %s\nФайл откатан, дальше не выполняю.\n", $file, $e->getMessage());
        exit(1);
    }
}

echo "\nГотово.\n";
