<?php
/*
 * Проверка виджетов рабочего стола (patch v30) из консоли.
 *
 * Рисует виджеты сервером в нескольких размерах и показывает, что получилось:
 * размер HTML, плашки «Не удалось показать виджет», исключения. Нужна, потому что
 * виджет обязан выглядеть прилично в любом прямоугольнике — размер блока свободный.
 *
 *   php patches/patch-v30/tools/render_widgets.php                 — все виджеты
 *   php patches/patch-v30/tools/render_widgets.php kpi,note        — только эти
 *   php patches/patch-v30/tools/render_widgets.php kpi 4x2,12x7    — свои размеры
 *   php patches/patch-v30/tools/render_widgets.php kpi 4x2 dump    — с HTML в консоль
 *
 * Виджет рисуется и с живыми данными, и образцовыми (превью библиотеки).
 */

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Services\WidgetRegistry;
use App\Modules\Pub\User\Models\User;
use App\Support\UiTheme;
use Illuminate\Support\Facades\View;

// вьюхи стола живут только в теме Metronic, а в консоли её никто не включает
UiTheme::use('metronic');
$path = UiTheme::viewsPath();
if ($path && is_dir($path)) {
    View::getFinder()->prependLocation($path);
}

$user = User::find((int) (getenv('DESK_USER') ?: 1));
if (!$user) {
    exit("Пользователь не найден\n");
}
auth()->login($user);

$only = isset($argv[1]) && $argv[1] !== 'all' ? array_filter(explode(',', $argv[1])) : null;
$dump = in_array('dump', $argv, true);

// размеры: объявленные виджетом плюс свободные, которых он не ждёт
$extra = isset($argv[2]) && $argv[2] !== 'dump' ? array_filter(explode(',', $argv[2])) : ['2x2', '4x2', '7x3', '16x5', '32x2'];

$ctx = DesktopContext::make([], $user);
$bad = 0;
$total = 0;

foreach (WidgetRegistry::all() as $id => $class) {
    if ($only && !in_array($id, $only, true)) {
        continue;
    }

    $sizes = array_values(array_unique(array_merge($class::sizes(), $extra)));
    printf("%-22s %s\n", $id, $class::name() . ' — ' . $class::category() . ', доступен: ' . ($class::available($user) ? 'да' : 'нет'));

    foreach ($sizes as $size) {
        [$w, $h] = $class::parseSize($size);
        if ($w < 1 || $h < 1) continue;

        foreach (['живые' => false, 'образец' => true] as $mode => $preview) {
            $total++;
            $declared = $class::allows($w, $h) ? ' ' : '~';

            try {
                $html = WidgetRegistry::instance($id)->html($w, $h, [], $ctx, $preview, true);
                $error = str_contains($html, 'desk-error') ? ' ОШИБКА В ТЕЛЕ' : '';
                $empty = str_contains($html, 'desk-empty') ? ' (пусто)' : '';
                if ($error) $bad++;

                printf("   %s%-7s %-8s %6d байт%s%s\n", $declared, $size, $mode, strlen($html), $error, $empty);

                if ($dump) echo $html . "\n";
            } catch (\Throwable $e) {
                $bad++;
                printf("   %s%-7s %-8s ИСКЛЮЧЕНИЕ: %s (%s:%d)\n", $declared, $size, $mode, $e->getMessage(), basename($e->getFile()), $e->getLine());
            }
        }
    }
}

echo "\nПроверок: $total, с ошибкой: $bad\n";
echo "Знак ~ — размер, который виджет не объявлял (свободный размер).\n";
