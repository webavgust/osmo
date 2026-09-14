<?php
/*
 * Резервная копия базы портала перед выкаткой: mysqldump с учётными данными из конфигурации
 * Laravel. Пароль попадает только во временный файл настроек с правами 600, который удаляется
 * сразу после выгрузки, — в командную строку и в вывод он не выходит. Запускать из корня сайта.
 *
 *   php patches/deploy-v21-v32/backup_db.php [каталог]   — по умолчанию /root/backup
 */

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$conn = config('database.connections.' . config('database.default'));
$dir = rtrim($argv[1] ?? '/root/backup', '/');

if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
    exit("Не удалось создать каталог {$dir}\n");
}

$cnf = tempnam(sys_get_temp_dir(), 'osmo-dump-');
chmod($cnf, 0600);
file_put_contents($cnf, sprintf("[client]\nuser=\"%s\"\npassword=\"%s\"\nhost=\"%s\"\nport=%d\n",
    addcslashes((string) $conn['username'], '"\\'), addcslashes((string) $conn['password'], '"\\'),
    addcslashes((string) $conn['host'], '"\\'), (int) ($conn['port'] ?? 3306)));

$file = sprintf('%s/%s-%s.sql.gz', $dir, $conn['database'], date('Y-m-d_H-i'));
$cmd = sprintf('mysqldump --defaults-extra-file=%s --single-transaction --routines --triggers --no-tablespaces %s | gzip > %s; echo ${PIPESTATUS[0]}',
    escapeshellarg($cnf), escapeshellarg((string) $conn['database']), escapeshellarg($file));

$status = trim((string) shell_exec('bash -c ' . escapeshellarg($cmd)));
unlink($cnf);

if ($status !== '0' || !is_file($file) || filesize($file) < 1024) {
    exit("Резервная копия НЕ снята (код mysqldump {$status}). Выкатку не начинать.\n");
}

printf("Резервная копия: %s (%.1f МБ)\n", $file, filesize($file) / 1024 / 1024);
