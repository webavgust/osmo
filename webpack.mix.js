const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

/*
    Копирование ассетов и дистрибутива
 */
mix.copy('resources/assets', 'public/assets');
mix.copy('resources/dist', 'public/dist');

/* Точечное копирование модулей */
mix.copy('node_modules/jstree/dist', 'public/dist/modules/jstree');

mix.scripts([
    'public/dist/js/app.js',
    'public/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js',
    'public/assets/extra-libs/sparkline/sparkline.js',
    'public/dist/js/sidebarmenu.js',
    'public/dist/js/feather.min.js',
    'public/dist/js/custom.min.js',
    'resources/js/app.js',
], 'public/js/app.js', 1);

/* сливаем стили*/
mix.css('resources/dist/css/style.css', 'public/dist/css/style.css');
mix.css('resources/css/app.css', 'public/css/app.css');

/* сливаем скрипты */
mix.js('resources/js/pages/*.js', 'public/js/pages.js');



