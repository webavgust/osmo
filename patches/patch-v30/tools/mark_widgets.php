<?php
/*
 * Отметки «сделано» в каталоге виджетов (patch v30).
 *
 * Проходит по заголовкам patches/patch-v30/WIDGETS.md и ставит «✔» тем виджетам,
 * которые уже есть в реестре (WidgetRegistry), а у остальных отметку снимает.
 * Запускать после каждой партии виджетов:
 *
 *   php patches/patch-v30/tools/mark_widgets.php
 */

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\Desktop\Services\WidgetRegistry;

$file = $root . '/patches/patch-v30/WIDGETS.md';
$text = file_get_contents($file);
$done = array_keys(WidgetRegistry::all());

$made = 0;
$left = [];

$text = preg_replace_callback('~^### (?:✔ )?(.+?) — `([a-z_0-9]+)`$~mu', function ($m) use ($done, &$made, &$left) {
    $ready = in_array($m[2], $done, true);
    $ready ? $made++ : $left[] = $m[2];

    return '### ' . ($ready ? '✔ ' : '') . $m[1] . ' — `' . $m[2] . '`';
}, $text);

// строка со счётом в шапке каталога
$total = $made + count($left);
$text = preg_replace(
    '~^Выгрузка из артефакта проекта решения: .*$~mu',
    'Выгрузка из артефакта проекта решения: ' . $total . ' виджетов в 9 категориях, сделано ' . $made . '.',
    $text
);

file_put_contents($file, $text);

echo "сделано: $made из $total\n";
echo "осталось: " . implode(', ', $left) . "\n";
