<?php
/*
 * Резервная копия базы портала перед выкаткой — выгрузка через подключение самого портала.
 *
 * mysqldump на проде с учётными данными из конфигурации получал Access denied, хотя PDO портала
 * подключается, поэтому копия снимается PHP: для каждой таблицы — DROP + SHOW CREATE TABLE и
 * INSERT пачками, потоком в gzip, в согласованном снимке транзакции (REPEATABLE READ). Пароль
 * нигде не используется напрямую и не печатается. Запускать из корня сайта (или по SSH в stdin):
 *
 *   php patches/deploy-v21-v32/backup_db.php [каталог]   — по умолчанию /root/backup
 *
 * Восстановление: gunzip < файл.sql.gz | mysql <база>  (или через тот же PDO).
 */

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$database = (string) config('database.connections.' . config('database.default') . '.database');
$dir = rtrim($argv[1] ?? '/root/backup', '/');

if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
    exit("Не удалось создать каталог {$dir}\n");
}

$file = sprintf('%s/%s-%s.sql.gz', $dir, $database, date('Y-m-d_H-i'));
$gz = gzopen($file, 'wb6');
if (!$gz) {
    exit("Не удалось открыть {$file} на запись\n");
}

$pdo = DB::connection()->getPdo();
$pdo->exec('SET NAMES utf8mb4');
$pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
$pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

gzwrite($gz, "-- Резервная копия базы {$database}, " . date('Y-m-d H:i:s') . "\n"
    . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET UNIQUE_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

$tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
$views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_NUM);
$total_rows = 0;

foreach ($tables as [$table]) {
    $quoted = '`' . str_replace('`', '``', $table) . '`';
    $create = $pdo->query("SHOW CREATE TABLE {$quoted}")->fetch(PDO::FETCH_NUM)[1];
    gzwrite($gz, "DROP TABLE IF EXISTS {$quoted};\n{$create};\n");

    // строки потоком: небуферизованный запрос не держит таблицу в памяти
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $stmt = $pdo->query("SELECT * FROM {$quoted}");
    $batch = [];
    $columns = null;
    $rows = 0;

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        if ($columns === null) {
            $columns = [];
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $columns[] = '`' . str_replace('`', '``', $stmt->getColumnMeta($i)['name']) . '`';
            }
        }
        $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';
        $rows++;

        if (count($batch) >= 500) {
            gzwrite($gz, "INSERT INTO {$quoted} (" . implode(',', $columns) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
            $batch = [];
        }
    }
    $stmt->closeCursor();
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    if ($batch) {
        gzwrite($gz, "INSERT INTO {$quoted} (" . implode(',', $columns) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
    }
    gzwrite($gz, "\n");
    $total_rows += $rows;
}

foreach ($views as [$view]) {
    $quoted = '`' . str_replace('`', '``', $view) . '`';
    $create = $pdo->query("SHOW CREATE VIEW {$quoted}")->fetch(PDO::FETCH_NUM)[1];
    gzwrite($gz, "DROP VIEW IF EXISTS {$quoted};\n{$create};\n\n");
}

gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\nSET UNIQUE_CHECKS = 1;\n-- конец копии\n");
gzclose($gz);
$pdo->exec('COMMIT');

// проверка: файл читается до конца и заканчивается меткой
$tail = '';
$check = gzopen($file, 'rb');
while (!gzeof($check)) {
    $tail = substr($tail . gzread($check, 65536), -64);
}
gzclose($check);

if (!str_contains($tail, '-- конец копии')) {
    exit("Резервная копия НЕ снята: файл {$file} не дочитывается до конца. Выкатку не начинать.\n");
}

printf("Резервная копия: %s (%.1f МБ): таблиц %d, представлений %d, строк %d\n",
    $file, filesize($file) / 1024 / 1024, count($tables), count($views), $total_rows);
