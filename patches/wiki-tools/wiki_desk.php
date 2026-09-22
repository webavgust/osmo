<?php
// Стол «WIKI» для кадров документации: создать (create) или удалить (delete).
// Стол владельца (is_default) не трогается. Запуск: php wiki_desk.php create|delete
require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\Desktop\Services\WidgetRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$name = 'WIKI (кадры документации)';
$mode = $argv[1] ?? 'create';

if ($mode === 'delete') {
    $ids = DB::table('desktops')->where('user_id', 1)->where('name', $name)->pluck('id');
    DB::table('desktop_widgets')->whereIn('desktop_id', $ids)->delete();
    DB::table('desktops')->whereIn('id', $ids)->delete();
    echo "удалено столов: ", count($ids), "\n";
    exit;
}

// раскладка: [виджет, x, y, w, h] на сетке 32 колонок
$layout = [
    ['currency',          0, 0, 4, 2], ['period',          4, 0, 4, 2],
    ['kpi',               8, 0, 6, 2], ['proposal_status', 14, 0, 9, 4], ['funnel_stages', 23, 0, 9, 6],
    ['button',            0, 2, 4, 2], ['reminders',       4, 2, 10, 6],
    ['payments_plan',    14, 4, 9, 6],
    ['scoring_top',       0, 8, 14, 6], ['keys_expiring',  14, 10, 9, 6], ['deals_top', 23, 6, 9, 10],
];

$now = now();
$id = DB::table('desktops')->insertGetId([
    'user_id' => 1, 'name' => $name, 'is_system' => 0, 'is_default' => 0, 'sort' => 99,
    'context' => json_encode(['period' => 'quarter', 'currency' => 'RUB']),
    'version' => 1, 'created_by' => 1, 'updated_by' => 1, 'created_at' => $now, 'updated_at' => $now,
]);

foreach ($layout as [$widget, $x, $y, $w, $h]) {
    $class = WidgetRegistry::find($widget);
    if (!$class) { echo "нет виджета {$widget}\n"; continue; }
    DB::table('desktop_widgets')->insert([
        'desktop_id' => $id, 'uid' => 'w' . Str::lower(Str::random(12)), 'widget' => $widget,
        'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'free_size' => 1,
        'settings' => json_encode($class::defaults(), JSON_UNESCAPED_UNICODE),
        'created_at' => $now, 'updated_at' => $now,
    ]);
}
echo "DESK={$id}\n";
