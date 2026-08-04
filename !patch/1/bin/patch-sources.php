<?php
/**
 * Точечные правки существующих файлов проекта.
 * Скрипт идемпотентный: повторный запуск ничего не сломает.
 *
 * Запуск из корня проекта:  php patch/bin/patch-sources.php
 */

$root = getcwd();

function edit(string $file, callable $fn): void
{
    if (! is_file($file)) {
        echo "  [пропуск] нет файла: {$file}\n";
        return;
    }

    $before = file_get_contents($file);
    $after = $fn($before);

    if ($after === null) {
        echo "  [ок] уже пропатчен: {$file}\n";
        return;
    }

    if ($after === false) {
        echo "  [!] не нашёл якорь в {$file} — поправьте вручную (см. README.md)\n";
        return;
    }

    copy($file, $file . '.bak-ui');
    file_put_contents($file, $after);
    echo "  [+] изменён: {$file} (бэкап: {$file}.bak-ui)\n";
}

echo "Правка исходников проекта\n";

/* 1. config/app.php — регистрация провайдера */
edit($root . '/config/app.php', function (string $s) {
    if (str_contains($s, 'UiThemeServiceProvider')) {
        return null;
    }

    $anchor = 'App\Providers\ComposerServiceProvider::class,';
    if (! str_contains($s, $anchor)) {
        $anchor = 'App\Providers\RouteServiceProvider::class,';
    }
    if (! str_contains($s, $anchor)) {
        return false;
    }

    return str_replace(
        $anchor,
        $anchor . "\n        App\Providers\UiThemeServiceProvider::class,",
        $s
    );
});

/* 2. app/Http/Kernel.php — middleware в группу web */
edit($root . '/app/Http/Kernel.php', function (string $s) {
    if (str_contains($s, 'ResolveUiTheme')) {
        return null;
    }

    $anchor = "\\Illuminate\\Routing\\Middleware\\SubstituteBindings::class,\n        ],\n\n        'api'";
    if (! str_contains($s, $anchor)) {
        return false;
    }

    $replace = "\\Illuminate\\Routing\\Middleware\\SubstituteBindings::class,\n"
        . "\n            // UI Kit: активная тема оформления (MaterialPro / Metronic)\n"
        . "            \\App\\Http\\Middleware\\ResolveUiTheme::class,\n"
        . "        ],\n\n        'api'";

    return str_replace($anchor, $replace, $s);
});

/* 3. Старая шапка — переключатель оформления */
edit($root . '/resources/views/layouts/header.blade.php', function (string $s) {
    if (str_contains($s, 'components.ui.theme_switch')) {
        return null;
    }

    $anchor = '<ul class="navbar-nav">';
    $pos = strpos($s, $anchor);
    if ($pos === false) {
        return false;
    }

    $insert = $anchor . "\n\n                @include('components.ui.theme_switch')\n";

    return substr_replace($s, $insert, $pos, strlen($anchor));
});

echo "Готово.\n";
