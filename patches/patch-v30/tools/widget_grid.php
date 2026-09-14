<?php
/*
 * Прогон виджета рабочего стола по сетке размеров (patch v30).
 *
 * Правило: после любой правки виджета его прогоняют по всей сетке — ширина 2…16 и высота
 * 2…32 колонок/ячеек с шагом 2 (128 размеров). Виджет рисуется сервером в каждом размере на
 * одной странице, страница открывается в безголовом Edge, скрипт widget_grid.js в самой
 * странице меряет, что видно, что обрезано и что ушло в многоточие. Размер блока в пикселях —
 * как на столе при окне 1920: ячейка 47,25 px, отступ блока 8 px с каждой стороны.
 *
 *   php patches/patch-v30/tools/widget_grid.php kpi                  — матрица по сетке
 *   php patches/patch-v30/tools/widget_grid.php kpi,note             — несколько виджетов
 *   php patches/patch-v30/tools/widget_grid.php kpi --detail=4x2,8x8 — что видно в размерах
 *   php patches/patch-v30/tools/widget_grid.php kpi --sizes=2x2,4x6  — только эти размеры
 *   php patches/patch-v30/tools/widget_grid.php kpi --live           — живые данные вместо образца
 *   php patches/patch-v30/tools/widget_grid.php kpi --settings='{"show_title":false}'
 *   php patches/patch-v30/tools/widget_grid.php kpi --cell=40 --margin=8
 *
 * Страница и отчёт остаются в storage/app/desk-grid/{id}.html и {id}.json.
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

/*** АРГУМЕНТЫ ***/

$ids = [];
$opt = ['cell' => 47.25, 'margin' => 8, 'budget' => 12000, 'live' => false, 'sizes' => null, 'detail' => [], 'settings' => []];

foreach (array_slice($argv, 1) as $arg) {
    if (!str_starts_with($arg, '--')) {
        $ids = array_merge($ids, array_filter(explode(',', $arg)));
        continue;
    }
    [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, null);
    match ($key) {
        'cell', 'margin' => $opt[$key] = (float) $value,
        'budget' => $opt['budget'] = (int) $value,
        'live' => $opt['live'] = true,
        'sizes' => $opt['sizes'] = array_filter(explode(',', (string) $value)),
        'detail' => $opt['detail'] = array_filter(explode(',', (string) $value)),
        'settings' => $opt['settings'] = (array) json_decode((string) $value, true),
        default => exit("Неизвестный ключ --$key\n"),
    };
}

if (!$ids) {
    exit("Укажите id виджета: php patches/patch-v30/tools/widget_grid.php kpi [--detail=4x2]\n");
}

$sizes = $opt['sizes'];
if (!$sizes) {
    foreach (range(2, 16, 2) as $w) {
        foreach (range(2, 32, 2) as $h) {
            $sizes[] = $w . 'x' . $h;
        }
    }
}

$user = User::find((int) (getenv('DESK_USER') ?: 1));
if (!$user) {
    exit("Пользователь не найден\n");
}
auth()->login($user);
$ctx = DesktopContext::make([], $user);

$edge = getenv('DESK_EDGE') ?: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
$dir = storage_path('app/desk-grid');
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$exit = 0;
foreach ($ids as $id) {
    $class = WidgetRegistry::find($id);
    if (!$class) {
        echo "Виджет «{$id}» не найден\n";
        $exit = 1;
        continue;
    }

    $report = runWidget($id, $class, $sizes, $opt, $ctx, $edge, $dir, $root);
    if ($report === null) {
        $exit = 1;
        continue;
    }

    printReport($id, $class, $report, $sizes, $opt);
}

exit($exit);

/*** СТРАНИЦА И ЗАПУСК ***/

/**
 * Нарисовать виджет во всех размерах, прогнать страницу в Edge и вернуть отчёт
 *
 * @return array|null
 */
function runWidget(string $id, string $class, array $sizes, array $opt, DesktopContext $ctx, string $edge, string $dir, string $root): ?array
{
    $settings = array_merge($class::previewSettings(), $opt['settings']);
    $sections = [];

    foreach ($sizes as $size) {
        [$w, $h] = $class::parseSize($size);
        $pw = round($w * $opt['cell'] - 2 * $opt['margin'], 2);
        $ph = round($h * $opt['cell'] - 2 * $opt['margin'], 2);

        try {
            $html = app($class)->html($w, $h, $settings, $ctx, !$opt['live']);
            $sections[] = "<section class=\"desk-grid-run\" data-size=\"{$w}x{$h}\" style=\"width:{$pw}px;height:{$ph}px\">"
                . "<div class=\"desk-item\"><div class=\"desk-item-body\">{$html}</div></div></section>";
        } catch (\Throwable $e) {
            $message = e($e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
            $sections[] = "<section class=\"desk-grid-run\" data-size=\"{$w}x{$h}\" data-exception=\"{$message}\" style=\"width:{$pw}px;height:{$ph}px\"></section>";
        }
    }

    $public = static fn(string $file) => 'file:///' . str_replace('\\', '/', $root) . '/public/' . $file;
    $css = [
        'https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700',
        $public('metronic/assets/plugins/global/plugins.bundle.css'),
        $public('metronic/assets/css/style.bundle.css'),
        $public('assets/libs/fontawesome/css/all.min.css'),
        $public('css/app.css'),
        $public('css/fix.css'),
        $public('css/palette.css'),
        $public('metronic/css/osmo-fix.css'),
        $public('metronic/css/osmo-compat.css'),
        $public('metronic/css/osmo-desktop.css'),
    ];
    foreach (glob($root . '/public/metronic/css/osmo-desktop-widgets/*.css') ?: [] as $file) {
        $css[] = $public('metronic/css/osmo-desktop-widgets/' . basename($file)) . '?v=' . filemtime($file);
    }
    $js = [
        'https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js',
        $public('metronic/js/osmo-desktop-charts.js'),
        $public('metronic/js/osmo-desktop-fit.js'),
    ];

    $head = implode("\n", array_map(fn($href) => '<link rel="stylesheet" href="' . e($href) . '">', $css));
    $scripts = implode("\n", array_map(fn($src) => '<script src="' . e($src) . '"></script>', $js));
    $runner = file_get_contents(__DIR__ . '/widget_grid.js');

    $page = <<<HTML
<!doctype html>
<html lang="ru" data-bs-theme="light">
<head>
<meta charset="utf-8">
<title>{$id}</title>
{$head}
<style>
    /* body у Metronic — flex-колонка: блоки размером в контейнер (container-type: size) в ней
       сжимаются до нуля, поэтому страница прогона — обычный поток */
    html, body { display: block !important; height: auto !important; }
    body { margin: 0; padding: 16px; background: rgb(248, 241, 237); font-family: Inter, Helvetica, sans-serif; }
    section.desk-grid-run { flex: none; position: relative; display: inline-block; vertical-align: top; margin: 0 16px 16px 0; border-radius: .625rem; }
    section.desk-grid-run::after { content: attr(data-size); position: absolute; left: 0; top: 100%; font-size: 10px; color: #999; }
    .desk-item, .desk-item-body, .desk-item-body > .desk-widget { height: 100%; }
</style>
</head>
<body>
{$scripts}
HTML;
    $page .= "\n" . implode("\n", $sections) . "\n<script>\n" . $runner . "\n</script>\n</body>\n</html>\n";

    $file = $dir . DIRECTORY_SEPARATOR . $id . '.html';
    file_put_contents($file, $page);

    $dom = dumpDom($edge, 'file:///' . str_replace('\\', '/', $file), $opt['budget']);
    if ($dom === null) {
        echo "{$id}: Edge не ответил за отведённое время\n";
        return null;
    }
    if (!preg_match('~<script type="application/json" id="desk-grid-report">(.*?)</script>~s', $dom, $m)) {
        echo "{$id}: в странице нет отчёта (скрипт замеров не успел или упал)\n";
        return null;
    }

    $report = json_decode($m[1], true);
    file_put_contents($dir . DIRECTORY_SEPARATOR . $id . '.json', json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return $report;
}

/**
 * Открыть страницу в безголовом Edge и вернуть DOM после скриптов. Профиль — свой на
 * каждый запуск: несколько прогонов параллельно не мешают друг другу
 *
 * @return string|null null — не уложился во время
 */
function dumpDom(string $edge, string $url, int $budget): ?string
{
    $profile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'desk-grid-' . getmypid() . '-' . bin2hex(random_bytes(3));
    $cmd = [$edge, '--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--hide-scrollbars',
        '--allow-file-access-from-files', '--window-size=1920,1080', '--user-data-dir=' . $profile,
        '--virtual-time-budget=' . $budget, '--dump-dom', $url];

    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['file', 'NUL', 'w']], $pipes);
    if (!is_resource($proc)) {
        return null;
    }

    stream_set_blocking($pipes[1], false);
    $out = '';
    $deadline = microtime(true) + 90;
    $timeout = false;

    while (true) {
        $chunk = fread($pipes[1], 65536);
        if ($chunk !== false && $chunk !== '') {
            $out .= $chunk;
            continue;
        }
        if (!proc_get_status($proc)['running']) {
            $out .= stream_get_contents($pipes[1]);
            break;
        }
        if (microtime(true) > $deadline) {
            $timeout = true;
            exec('taskkill /F /T /PID ' . (int) proc_get_status($proc)['pid'] . ' 2>NUL');
            break;
        }
        usleep(100000);
    }

    fclose($pipes[1]);
    proc_close($proc);
    removeDir($profile);

    return $timeout ? null : $out;
}

/** Удалить временный профиль Edge (что не удалилось — останется во временной папке) */
function removeDir(string $dir): void
{
    if (!is_dir($dir)) return;

    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        @($item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()));
    }
    @rmdir($dir);
}

/*** ОТЧЁТ ***/

/**
 * Коды размера: X исключение или плашка ошибки, 0 пусто, C обрезано на краю, H спрятано
 * обрезкой целиком, T многоточие, F шрифт мельче 10 px, G график ниже 40 px, e занято
 * меньше четверти площади, s есть прокрутка, f строки спрятаны подгоном
 */
function codes(array $s): string
{
    $c = '';
    if (!empty($s['exception']) || !empty($s['error'])) $c .= 'X';
    if (!empty($s['empty'])) $c .= '0';
    if (!empty($s['clipped'])) $c .= 'C';
    if (($s['cut'] ?? 0) > 0) $c .= 'H';
    if (!empty($s['truncated'])) $c .= 'T';
    if (($s['minFont'] ?? 99) < 10) $c .= 'F';
    foreach ($s['charts'] ?? [] as $chart) {
        if ((int) explode('x', $chart)[1] < 40) { $c .= 'G'; break; }
    }
    if (empty($s['exception']) && empty($s['empty']) && ($s['cover'][0] ?? 0) * ($s['cover'][1] ?? 0) < 2500) $c .= 'e';
    if (($s['scrolled'] ?? 0) > 0) $c .= 's';
    if (($s['fitOut'] ?? 0) > 0) $c .= 'f';

    return $c === '' ? '·' : $c;
}

function printReport(string $id, string $class, array $report, array $sizes, array $opt): void
{
    $by = [];
    foreach ($report['sizes'] ?? [] as $s) {
        $by[$s['size']] = $s;
    }

    $ws = [];
    $hs = [];
    foreach ($sizes as $size) {
        [$w, $h] = array_map('intval', explode('x', $size));
        $ws[$w] = true;
        $hs[$h] = true;
    }
    ksort($ws);
    ksort($hs);

    printf("\n%s — %s (%s данные, ячейка %s px)\n", $id, $class::name(), $opt['live'] ? 'живые' : 'образцовые', $opt['cell']);
    echo str_pad('h\\w', 5);
    foreach (array_keys($ws) as $w) echo str_pad((string) $w, 6);
    echo "\n";

    $count = [];
    foreach (array_keys($hs) as $h) {
        echo str_pad((string) $h, 5);
        foreach (array_keys($ws) as $w) {
            $s = $by[$w . 'x' . $h] ?? null;
            $code = $s ? codes($s) : ' ';
            foreach (str_split($code) as $ch) {
                if ($ch !== '·') $count[$ch] = ($count[$ch] ?? 0) + 1;
            }
            echo mb_str_pad($code, 6);
        }
        echo "\n";
    }

    $names = ['X' => 'ошибка', '0' => 'пусто', 'C' => 'обрезано на краю', 'H' => 'спрятано обрезкой', 'T' => 'многоточие',
        'F' => 'шрифт < 10px', 'G' => 'график < 40px', 'e' => 'занято < 1/4', 's' => 'прокрутка', 'f' => 'подгон спрятал строки'];
    $summary = [];
    foreach ($names as $ch => $name) {
        if (!empty($count[$ch])) $summary[] = "$ch $name: {$count[$ch]}";
    }
    echo 'Итого: ' . ($summary ? implode('; ', $summary) : 'замечаний нет') . "\n";
    if (!empty($report['errors'])) {
        echo 'Ошибки JS: ' . implode(' | ', array_slice(array_unique($report['errors']), 0, 5)) . "\n";
    }

    foreach ($opt['detail'] as $size) {
        $s = $by[$size] ?? null;
        if (!$s) {
            echo "\n[$size] нет в прогоне\n";
            continue;
        }
        printf("\n[%s] коды %s, тело %s px, занято %s%% × %s%%, мин. шрифт %s px\n",
            $size, codes($s), implode('×', $s['body'] ?? []), $s['cover'][0] ?? 0, $s['cover'][1] ?? 0, $s['minFont'] ?? '—');
        if (!empty($s['exception'])) echo "  исключение: {$s['exception']}\n";
        echo '  видно: ' . implode(' | ', $s['shown'] ?? []) . "\n";
        if (!empty($s['truncated'])) echo '  многоточие: ' . implode(' | ', $s['truncated']) . "\n";
        if (!empty($s['clipped'])) echo '  обрезано: ' . implode(' | ', $s['clipped']) . "\n";
        if (!empty($s['charts'])) echo '  графики: ' . implode(', ', $s['charts']) . "\n";
        printf("  спрятано обрезкой: %d, в прокрутке: %d, под растворением: %d, подгоном: %d\n",
            $s['cut'] ?? 0, $s['scrolled'] ?? 0, $s['faded'] ?? 0, $s['fitOut'] ?? 0);
    }
}
