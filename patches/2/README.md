# OSMO → Metronic 8.2.1 (demo48)

Патч добавляет в проект **вторую тему оформления** на Metronic 8.2.1 и переключатель
между старым (MaterialPro) и новым оформлением в шапке сайта.

Ничего из существующей вёрстки не удаляется: старые вьюхи продолжают работать,
новые кладутся в отдельный каталог и перекрывают старые только для темы Metronic.

---

## 1. Как это устроено

Активная тема хранится в cookie `ui_theme` (на устройство, без БД).
Middleware `ResolveUiTheme` читает cookie и **подкладывает каталог темы первым
в список путей поиска Blade**:

```
resources/views/themes/metronic/pub/proposal/index.blade.php   ← если есть, берётся он
resources/views/pub/proposal/index.blade.php                    ← иначе старая вёрстка
```

Так же работают и x-компоненты (`x-ui.badge.light`, `x-sidebar.menu` и т.д.).

Что это даёт:

* переводить страницы можно **по одной**, ничего не ломая;
* diff новой темы виден одним каталогом;
* когда старая тема больше не нужна — содержимое `resources/views/themes/metronic`
  переносится в `resources/views`, каталог `themes` и провайдер удаляются,
  всё остальное работает без изменений.

Все новые страницы кладите сразу в `resources/views/themes/metronic/…`.

---

## 2. Установка

### Автоматически

```bash
cd /путь/к/osmo
bash patch/bin/install.sh /путь/к/репозиторию/metronic
php artisan view:clear
```

Скрипт: копирует файлы, копирует `demo48/assets` в `public/metronic/assets`,
вносит три правки в существующие файлы (с бэкапами `*.bak-ui`) и чистит кэш.

### Вручную

1. Скопировать в проект каталоги `app/`, `config/`, `resources/`, `public/` из `patch/`.
2. Скопировать `metronic/html/metronic_html_v8.2.1_demo48/demo48/assets`
   в `public/metronic/assets`.
3. `config/app.php` → в массив `providers`:
   ```php
   App\Providers\UiThemeServiceProvider::class,
   ```
4. `app/Http/Kernel.php` → в группу `web`, последней строкой:
   ```php
   \App\Http\Middleware\ResolveUiTheme::class,
   ```
5. `resources/views/layouts/header.blade.php` → сразу после
   `<ul class="navbar-nav">` (правый блок иконок):
   ```blade
   @include('components.ui.theme_switch')
   ```
6. `php artisan view:clear && php artisan config:clear`

### После установки обязательно

Откройте `config/ui.php` и проверьте пути:

* `themes.metronic.extra_css` — путь к **вашему Font Awesome Pro**
  (`fa-light`, `fa-duotone`, `fa-solid` используются везде: в шапке, в меню,
  в иконках страниц). В патче стоит предположение
  `/assets/libs/fontawesome/css/all.min.css`.
* `legacy_css` — иконочные шрифты MaterialPro (`mdi-*`, `ti-*`, `icon-*`).
  Они нужны, пока не все страницы переведены: на непереведённых страницах
  такие иконки ещё встречаются. После полного перевода строки можно удалить.
* `legacy_js` — blockUI, jquery-ui, moment, visibility.js, toastr.

---

## 3. Что вошло в патч

### Ядро

| Файл | Назначение |
|---|---|
| `config/ui.php` | реестр тем, cookie, пути к ассетам и легаси-библиотекам |
| `app/Support/UiTheme.php` | `UiTheme::current()`, `::is()`, `::asset()`, `::config()` |
| `app/Http/Middleware/ResolveUiTheme.php` | определяет тему, подкладывает каталог вьюх |
| `app/Providers/UiThemeServiceProvider.php` | маршрут `GET /ui-theme/{theme}` (`route('ui.theme', 'metronic')`) |
| `reference/app/Http/Kernel.php` | справочная копия Kernel с уже добавленным middleware |

### Каркас новой темы — `resources/views/themes/metronic/`

| Файл | Что делает |
|---|---|
| `layouts/layout_short.blade.php` | `<head>`, бандлы Metronic, тёмная тема, порядок загрузки скриптов |
| `layouts/layout.blade.php` | каркас: header → sidebar → toolbar → content → footer + `spider_tick` |
| `layouts/sidebar.blade.php` | **левое меню**: логотип, кнопка сворачивания, карточка пользователя, дерево меню, кнопки внизу |
| `layouts/header.blade.php` | шапка: бургер, напоминания, календарь, уведомления, светлая/тёмная тема, переключатель оформления, профиль |
| `layouts/breadcrumbs.blade.php` | toolbar: заголовок + хлебные крошки + `@yield('breadcrumb_right')` |
| `layouts/layout_pdf.blade.php` | стили Metronic для PDF/печати |
| `components/sidebar/menu*.blade.php` | дерево меню из БД → вертикальное меню с аккордеонами |
| `components/breadcrumb*.blade.php` | крошки в стиле Metronic |
| `components/ui/badge/*.blade.php` | `bg-light-*` → `badge-light-*` |
| `components/ui/icon/keen.blade.php` | `<x-ui.icon.keen icon="ki-calendar" paths="3" />` — если понадобятся KeenIcons |
| `components/ui/theme_switch.blade.php` | переключатель оформления |
| `auth.blade.php` | страница входа |

### Левое меню

Сделано **по структуре сайдбара MaterialPro**:

| MaterialPro | Metronic-версия |
|---|---|
| `user-profile` с фоном | карточка пользователя (аватар, имя, e-mail), ведёт в профиль |
| `nav-small-cap` — корневой раздел | `menu-heading` — подпись группы разделов |
| `sidebar-item` + `has-arrow` + `collapse` | `menu-item menu-accordion` + `menu-sub-accordion` |
| `sidebar-footer` с выходом | три кнопки внизу: календарь, профиль, выход |
| режим `sidebar_mode = mini` | атрибут `data-kt-app-sidebar-minimize`, кнопка сворачивания в шапке меню |

Ветка с активной страницей раскрывается автоматически, активный пункт
подсвечивается. Свёрнутое состояние запоминается в `localStorage`
(ключ `osmo_sidebar_minimize`), начальное значение берётся из
пользовательской настройки `sidebar_mode`. На мобильных меню открывается
как drawer по бургеру.

Стили меню — `public/metronic/css/osmo-sidebar.css`: в бандле demo48 меню
находится в шапке, поэтому правил `app-sidebar` там нет, и они дописаны
отдельным файлом (тёмный сайдбар, свёрнутый режим, drawer, печать).

### Иконки

Везде **Font Awesome Pro**, KeenIcons не используются:

| Где | Иконка |
|---|---|
| бургер | `fa-light fa-bars` |
| напоминания | `fa-duotone fa-alarm-exclamation` |
| календарь | `fa-duotone fa-calendar-days` |
| уведомления | `fa-duotone fa-message` |
| светлая/тёмная тема | `fa-duotone fa-sun-bright` / `fa-moon-stars`, в меню — `fa-desktop` |
| переключатель оформления | `fa-duotone fa-palette` |
| сворачивание меню | `fa-light fa-angles-left` |
| подпись группы в меню | `fa-light fa-ellipsis` |
| крошки | `fa-light fa-house`, `fa-light fa-angle-right` |
| подвал меню | `fa-light fa-calendar-days`, `fa-user`, `fa-power-off` |
| действия в таблицах | `fa-light fa-ellipsis-vertical`, `fa-pen`, `fa-trash`, `fa-copy` |
| формы | `fa-light fa-floppy-disk`, `fa-plus`, `fa-filter`, `fa-xmark`, `fa-magnifying-glass` |

Иконки пунктов меню берутся из поля `icon` в БД как раньше — там уже FA,
ничего менять не нужно.

### Переведённые страницы

| Страница | Файл в теме | Что сделано |
|---|---|---|
| `/bitrix/dashboard` | `bitrix/dashboard/index.blade.php` | показатели в сетке `g-4`, таблицы в `card-flush` с табами `nav-line-tabs` в `card-toolbar`, кнопки фильтра/валюты в toolbar, адаптив до `col-6` на мобильных |
| `/proposals` | `pub/proposal/index.blade.php` | табы менеджеров в шапке карточки, тулбар bootstrap-table (фильтр/сброс/создать), модалка фильтра на Metronic, иконки таблицы FA. Вся логика bootstrap-table, daterangepicker и ajax-фильтра — без изменений |
| `/proposals/create` | `pub/proposal/create.blade.php` | адаптирована механически: кнопки, лейблы, алерты валидации, рамки таблицы. Конструктор КП (2400 строк логики) не тронут |
| `/proposals/edit` | `pub/proposal/edit.blade.php` | то же |
| `/neuroservice` | `pub/neuroservice/index.blade.php` | вместо `email-app/todo-box-container` — карточка с двумя колонками: список групп слева, таблица справа. Поиск, переключение групп, удаление — исходный JS сохранён |
| `/neuroservice/create`, `/edit` | `pub/neuroservice/*.blade.php` | формы Metronic: `form-control-solid`, `required`-лейблы, ошибки под полем, footer с кнопками |
| `/neuroservice_group/create`, `/edit` | `pub/neuroservice_group/*.blade.php` | то же + удаление группы в модалке |

Сопутствующие компоненты в теме: `components/neuroservice/*`,
`components/proposal/*` (включая `extra-pays`, `hardware_table`, `log_table`,
`task`, `table/main/actions`), `components/bitrix/dashboard/tbl_industry_name`,
`bitrix/dashboard/box/filter`.

### Фронт

* `public/metronic/css/osmo-sidebar.css` — левое меню (см. выше).
* `public/metronic/css/osmo-compat.css` — слой совместимости: остатки MaterialPro
  (`waves-*`, `page-titles`, `bg-light-*`, `invoice-header`, `email-app`,
  `bootstrap-table`), select2, тосты, индикатор уведомлений, тёмная тема, печать.
* `public/metronic/js/osmo-metronic.js` — мост: заглушка `AdminSettings`,
  тема select2, настройки toastr, память свёрнутого меню, переинициализация
  компонентов Metronic после ajax-вставок `box()` / `sidebar()`.

---

## 4. Что осталось

Каркас, меню и семь страниц переведены. Остальные ~520 шаблонов открываются
в новой теме и работают (обе темы — Bootstrap 5), но с вёрсткой MaterialPro,
подпёртой слоем совместимости.

Как переводить дальше:

```bash
mkdir -p resources/views/themes/metronic/pub/order
cp resources/views/pub/order/index.blade.php \
   resources/views/themes/metronic/pub/order/index.blade.php
```

Соответствие классов:

| MaterialPro | Metronic |
|---|---|
| `card` / `card-body` | так же (+ `card-flush`, `card-header`, `card-toolbar`, `card-footer`) |
| `badge bg-light-danger text-danger` | `badge badge-light-danger` |
| `btn btn-info waves-effect` | `btn btn-primary` |
| `btn btn-light-danger text-danger` | `btn btn-light-danger` |
| `row page-titles` | toolbar (уже в каркасе, из шаблона удалить) |
| `form-group mb-3` / `control-label col-form-label` | `row mb-6` / `col-form-label fw-semibold` |
| `form-control` | `form-control form-control-solid` |
| `checkbox checkbox-info` | `form-check form-check-custom form-check-solid` |
| `table customize-table v-middle` | `table table-row-dashed table-row-gray-300 align-middle` |
| `nav nav-tabs` | `nav nav-tabs nav-line-tabs nav-line-tabs-2x` |
| `mdi-*`, `ti-*`, `icon-*` | `fa-light fa-*` |

---

## 5. Откат

```bash
# вернуть старую тему всем: в .env
UI_THEME=materialpro
UI_THEME_SWITCH=false
```

Полный откат: восстановить `*.bak-ui`, удалить провайдер из `config/app.php`,
middleware из `Kernel.php`, каталоги `resources/views/themes/metronic`
и `public/metronic`.

---

## 6. Известные ограничения

* **Font Awesome Pro** подключается отдельно — в бандле Metronic только
  FA Free / KeenIcons / Line Awesome. Без правильного пути в `extra_css`
  иконки будут пустыми.
* Стили левого меню написаны вручную (в бандле demo48 их нет). Если решите
  взять бандл демо с сайдбаром — файл `osmo-sidebar.css` можно удалить.
* `bootstrap-table` стилизован слоем совместимости, а не нативно.
* `proposal/create` и `proposal/edit` адаптированы механически: логика
  и структура конструктора КП сохранены как есть, переведены только
  контролы и заголовки.
* Ассеты Metronic не входят в архив (~40 МБ) — копируются из вашего
  репозитория `webavgust/metronic` скриптом установки.
