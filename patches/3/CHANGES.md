# Патч v3 — только изменённые файлы

Накатывается ПОВЕРХ предыдущей установки: скопируйте каталоги `resources` и `public`
из этого архива в корень проекта с заменой, затем:

    rm resources/views/themes/metronic/layouts/header.blade.php
    php artisan view:clear

## Изменённые файлы (7)

| Файл | Что изменено |
|---|---|
| `public/metronic/css/osmo-sidebar.css` | светлое меню, шрифт крупнее, ширина 330px, отступ снизу, нейтрализация геометрии Metronic |
| `resources/views/themes/metronic/layouts/layout_short.blade.php` | убраны атрибуты `data-kt-app-sidebar-*` с `<body>` |
| `resources/views/themes/metronic/layouts/layout.blade.php` | верхний бар убран, вместо него мобильная полоса с бургером |
| `resources/views/themes/metronic/layouts/sidebar.blade.php` | иконки действий и профиль перенесены в заголовок меню |
| `resources/views/themes/metronic/layouts/breadcrumbs.blade.php` | уменьшен верхний отступ |
| `resources/views/themes/metronic/components/sidebar/menu-item.blade.php` | размеры иконок из CSS, точное определение активной ветки |
| `resources/views/themes/metronic/components/ui/theme_switch.blade.php` | вид под панель действий в меню |

## Удалить

`resources/views/themes/metronic/layouts/header.blade.php` — верхний бар больше не используется.

## Что именно исправлено по вашему списку

1. **Слишком тёмное меню** — меню теперь светлое: белый фон, серый текст (`#4b5675`),
   активный пункт — голубая подложка `primary-light` с синим текстом.
   Тёмный вариант остался, но включается только тёмной темой (кнопка солнца/луны).

2. **Мелкий шрифт** — пункты меню 17px (было 14px), вложенные 16px, подписи групп 13px,
   иконки 18px. Размеры вынесены в переменные в начале файла:
   `--osmo-sidebar-font`, `--osmo-sidebar-font-sub`, `--osmo-sidebar-font-heading`.

3. **Выделенный пункт без текста** — это был конфликт: Metronic красил заголовок активного
   пункта в `primary`, а фон тоже был `primary` (синий на синем). Теперь цвета текста,
   иконки и маркера для `.menu-link.active` заданы явно.

4. **Отступы главной области** — на `<body>` стояли `data-kt-app-sidebar-fixed`
   и `data-kt-app-sidebar-push-*`: Metronic добавлял свои отступы по собственной
   переменной ширины сайдбара, и они складывались с моими — отсюда пустая полоса слева
   и обрезанная таблица справа. Атрибуты убраны, лишние margin у `app-wrapper`,
   `app-header` и `app-container` обнулены. Верхний отступ до заголовка тоже уменьшен.

5. **Иконки перенесены в меню, верхний бар убран** — напоминания, календарь,
   уведомления, тема, оформление и выход теперь в панели под логотипом.
   Профиль — карточка с аватаром, именем и выпадающим меню.
   На мобильных осталась узкая полоса с бургером и логотипом.

6. **Ширина меню** — 330px вместо 265px (в 1.5 раза больше базовой ширины Metronic 265px),
   свёрнутое — 88px. Меняется переменной `--osmo-sidebar-width`.

7. **Отступ снизу** — меню стало плавающей панелью с одинаковым отступом
   сверху, снизу и слева (16px, переменная `--osmo-sidebar-gap`),
   скруглением и лёгкой тенью.

## Быстрая подстройка

Все размеры и цвета меню — в `:root` в начале `osmo-sidebar.css`:

    --osmo-sidebar-width: 330px;
    --osmo-sidebar-gap: 16px;
    --osmo-sidebar-font: 1.0625rem;
    --osmo-sidebar-bg: #ffffff;
