<?php
/*
 * Проверка портала после выкатки (patch v34): ключевые страницы через ядро под двумя
 * пользователями (админ и обычный) и отрисовка всех блоков системных столов.
 * Только чтение: GET-запросы и рендер, в базу ничего не пишет.
 *
 * ЗАПУСКАТЬ ТОЛЬКО ОТ ПОЛЬЗОВАТЕЛЯ САЙТА — иначе скомпилированные шаблоны и кэш в storage
 * окажутся с владельцем root и сайт ответит 500 (так было 23.09.2026):
 *
 *   cd /var/www/www-root/data/www/osmo-avg.ru
 *   su www-root -s /bin/sh -c "php patches/patch-v34/tools/prod_check.php"          — пользователи 1 и 5
 *   su www-root -s /bin/sh -c "php patches/patch-v34/tools/prod_check.php 1,3"      — свои пользователи
 *   find storage bootstrap/cache -user root | wc -l                                  — должно быть 0
 */

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\Desktop\Models\Desktop;
use App\Modules\Pub\Desktop\Services\DesktopService;
use App\Modules\Pub\User\Models\User;
use App\Support\UiTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    exit("Запущено от root — запустите от пользователя сайта: su www-root -s /bin/sh -c \"php …\"\n");
}

$users = array_filter(array_map('intval', explode(',', $argv[1] ?? '1,5')));

// /desktop открывать не надо: у пользователя без столов он создаёт копию стола по умолчанию
$pages = ['/desktop/' . (Desktop::system()->where('is_default', true)->value('id') ?? 1), '/proposals',
    '/external-proposals', '/bitrix/deal', '/bitrix/dashboard', '/analytics/licenses', '/analytics/partners',
    '/payment-calendar', '/report/payments', '/calendar', '/partners', '/companies'];

$failed = 0;
foreach ($users as $uid) {
    $codes = [];
    foreach ($pages as $url) {
        auth()->loginUsingId($uid);
        $request = Request::create($url, 'GET', [], ['ui_theme' => 'metronic']);
        $response = $kernel->handle($request);
        $code = $response->getStatusCode();
        if ($code >= 500) $failed++;
        $codes[] = $url . ' ' . $code;
        $kernel->terminate($request, $response);
    }
    echo "пользователь $uid: ", implode(' | ', $codes), PHP_EOL;
}

// блоки системных столов — как их рисует стол
UiTheme::use('metronic');
$path = UiTheme::viewsPath();
if ($path && is_dir($path)) View::getFinder()->prependLocation($path);

foreach ($users as $uid) {
    $user = User::find($uid);
    if (!$user) continue;
    auth()->login($user);

    $total = 0;
    $bad = [];
    foreach (Desktop::system()->orderBy('sort')->get() as $desktop) {
        $items = DesktopService::gridItems($desktop, $user);
        $html = DesktopService::renderBatch($desktop, $items, $user);
        foreach ($items as $item) {
            $total++;
            $h = $html[$item['uid']] ?? '';
            if (str_contains($h, 'Не удалось')) $bad[] = $desktop->name . ':' . $item['widget'];
        }
    }
    $failed += count($bad);
    echo "пользователь $uid: блоков системных столов $total, с ошибкой ", count($bad), $bad ? ' — ' . implode(', ', $bad) : '', PHP_EOL;
}

echo $failed ? "ЕСТЬ ОШИБКИ: $failed\n" : "Ошибок нет\n";
exit($failed ? 1 : 0);
