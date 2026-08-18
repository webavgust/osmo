<?php

/*
|--------------------------------------------------------------------------
| Оформление интерфейса (UI Kit)
|--------------------------------------------------------------------------
|
| Проект поддерживает несколько наборов вёрстки одновременно. Активная тема
| определяется cookie (см. ключ "cookie"), значение по умолчанию — "default".
|
| Тема "materialpro" — это существующие вьюхи в resources/views (ничего не
| переопределяет). Тема "metronic" подключает каталог
| resources/views/themes/metronic — любой файл оттуда ПЕРЕКРЫВАЕТ одноимённый
| файл из resources/views. Файлов, которых там нет, движок возьмёт из старой
| темы, поэтому переводить страницы можно постепенно.
|
| Когда старая тема больше не нужна: содержимое resources/views/themes/metronic
| переносится в resources/views, каталог themes и провайдер удаляются.
|
*/

return [

    // Тема по умолчанию (если cookie не выставлена)
    'default' => env('UI_THEME', 'materialpro'),

    // Имя cookie с выбранной темой и её срок жизни в минутах
    'cookie' => 'ui_theme',
    'cookie_lifetime' => 60 * 24 * 365,

    // Показывать ли переключатель в шапке
    'switch_enabled' => (bool) env('UI_THEME_SWITCH', true),

    // Показывать переключатель только администраторам
    'switch_admin_only' => (bool) env('UI_THEME_SWITCH_ADMIN_ONLY', false),

    'themes' => [

        'materialpro' => [
            'title' => 'MaterialPro',
            'subtitle' => 'текущее оформление',
            'icon' => 'fa-layer-group',
            // null = базовые вьюхи resources/views
            'views' => null,
        ],

        'metronic' => [
            'title' => 'Metronic 8.2',
            'subtitle' => 'новое оформление',
            'icon' => 'fa-wand-magic-sparkles',

            // Каталог с переопределениями, относительно resources/views
            'views' => 'themes/metronic',

            // Куда скопированы ассеты Metronic (public/metronic/assets)
            'assets' => '/metronic/assets',

            /*
             * Дополнительные css, которые нужно подключить в новой теме.
             * Сюда обязательно вписать путь к вашему Font Awesome Pro —
             * он используется компонентами x-ui.icon.* и иконками меню.
             * Проверьте реальный путь в public/ и поправьте при необходимости.
             */
            'extra_css' => [
                '/assets/libs/fontawesome/css/all.min.css',
            ],

            // Скрипты старого фронта, которые нужны новой теме
            // (то, чего нет в бандле Metronic)
            'legacy_js' => [
                '/assets/libs/block-ui/jquery.blockUI.js',
                '/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js',
                '/assets/custom/jquery.ui/jquery-ui.min.js',
                '/dist/modules/moment/min/moment.min.js',
                '/dist/modules/visibilityjs/lib/visibility.fallback.js',
                '/dist/modules/visibilityjs/lib/visibility.core.js',
                '/dist/modules/visibilityjs/lib/visibility.timers.js',
            ],

            'legacy_css' => [
                '/assets/custom/jquery.ui/jquery-ui.css',
                '/assets/extra-libs/toastr/dist/build/toastr.min.css',

                /*
                 * Иконочные шрифты MaterialPro. Нужны, пока не все страницы
                 * переведены: на непереведённых встречаются mdi-*, ti-*,
                 * icon-* (simple-line-icons). Проверьте пути в public/ —
                 * без них на старых страницах будут пустые квадраты.
                 * После полного перевода строки можно удалить.
                 */
//                '/assets/extra-libs/materialdesignicons/css/materialdesignicons.min.css',
//                '/assets/extra-libs/themify-icons/themify-icons.css',
//                '/assets/extra-libs/simple-line-icons/css/simple-line-icons.css',
            ],
        ],

    ],
];
