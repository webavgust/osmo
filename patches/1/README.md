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
resources/views/themes/metronic/pub/order/index.blade.php   ← если есть, берётся он
resources/views/pub/order/index.blade.php                    ← иначе старая вёрстка
```

Так же работают и x-компоненты (`x-ui.badge.light`, `x-sidebar.menu` и т.д.).

Что это даёт:

* переводить страницы можно **по одной**, ничего не ломая;
* diff новой темы виден одним каталогом;
* когда старая тема больше не нужна — содержимое `resources/views/themes/metronic`
  переносится в `resources/views`, каталог `themes` и провайдер удаляются,
  всё остальное работает без изменений.

Все новые страницы, которые вы будете делать, кладите сразу в
`resources/views/themes/metronic/…`.

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

Откройте `config/ui.php` и проверьте ключ `themes.metronic.extra_css` — там
должен быть путь к **вашему Font Awesome Pro** (`fa-light`, `fa-duotone`
используются в `x-ui.icon.*` и в иконках меню). В патче стоит предположение
`/assets/libs/fontawesome/css/all.min.css`.

Там же — `legacy_js` / `legacy_css`: библиотеки старого фронта, которых нет
в бандле Metronic (blockUI, toastr, jquery-ui, moment, visibility.js).
Если какой-то путь у вас другой — поправьте.

---

## 3. Что вошло в патч

**Ядро**

| Файл | Назначение |
|---|---|
| `config/ui.php` | реестр тем, cookie, пути к ассетам и легаси-библиотекам |
| `app/Support/UiTheme.php` | `UiTheme::current()`, `::is()`, `::asset()`, `::config()` |
| `app/Http/Middleware/ResolveUiTheme.php` | определяет тему, подкладывает каталог вьюх |
| `app/Providers/UiThemeServiceProvider.php` | маршрут `GET /ui-theme/{theme}` (`route('ui.theme', 'metronic')`) |
| `reference/app/Http/Kernel.php` | справочная копия Kernel с уже добавленным middleware (не копируется установщиком — ваш Kernel правится на месте) |

**Каркас новой темы** — `resources/views/themes/metronic/`

| Файл | Что делает |
|---|---|
| `layouts/layout_short.blade.php` | `<head>`, бандлы Metronic, тёмная тема, порядок загрузки скриптов |
| `layouts/layout.blade.php` | каркас demo48 (header → toolbar → content → footer) + `spider_tick` |
| `layouts/header.blade.php` | шапка: логотип, напоминания, календарь, уведомления, тема, переключатель оформления, профиль |
| `layouts/breadcrumbs.blade.php` | toolbar Metronic: хлебные крошки + заголовок + `@yield('breadcrumb_right')` |
| `layouts/sidebar.blade.php` | пустой (в demo48 меню в шапке) |
| `layouts/layout_pdf.blade.php` | стили Metronic для PDF/печати |
| `components/sidebar/menu*.blade.php` | дерево меню из БД → горизонтальное меню Metronic (аккордеон на мобильных) |
| `components/breadcrumb*.blade.php` | крошки в стиле Metronic |
| `components/ui/badge/*.blade.php` | `bg-light-*` → `badge-light-*` |
| `components/ui/icon/keen.blade.php` | новый компонент `<x-ui.icon.keen icon="ki-calendar" paths="3" />` |
| `components/ui/theme_switch.blade.php` | переключатель оформления (версия Metronic) |
| `auth.blade.php` | страница входа на Metronic |

**Фронт**

* `public/metronic/css/osmo-compat.css` — слой совместимости: остатки MaterialPro
  (`waves-*`, `page-titles`, `bg-light-*`, `bootstrap-table`), select2, тосты,
  индикатор уведомлений, тёмная тема, печать.
* `public/metronic/js/osmo-metronic.js` — мост: заглушка `AdminSettings`,
  тема select2, настройки toastr, переинициализация компонентов Metronic
  после ajax-вставок `box()` / `sidebar()`.

**Переключатель в старой теме** — `resources/views/components/ui/theme_switch.blade.php`
(подключается в существующую шапку одной строкой).

---

## 4. Что осталось на следующие шаги

Патч даёт **каркас + слой совместимости**: все 530 существующих шаблонов
открываются в новой теме и остаются рабочими, потому что обе темы —
Bootstrap 5. Но нативной вёрстки Metronic на страницах пока нет.

Порядок дальнейшей работы (по вашему приоритету):

1. `pub/proposal/index` — списки, фильтры, bootstrap-table
2. `pub/proposal/create` (144 КБ) и `pub/proposal/edit` (189 КБ) — формы
3. `pub/order/index`, `pub/order/detail`
4. `pub/order_task/*`
5. Остальное по чек-листу

Как переводить страницу:

```bash
# копируем страницу в тему и правим уже её
mkdir -p resources/views/themes/metronic/pub/proposal
cp resources/views/pub/proposal/index.blade.php \
   resources/views/themes/metronic/pub/proposal/index.blade.php
```

Соответствие классов при переводе:

| MaterialPro | Metronic |
|---|---|
| `card` / `card-body` | так же (`card`, `card-body`, `card-header`, `card-toolbar`) |
| `badge bg-light-danger text-danger` | `badge badge-light-danger` |
| `btn btn-info waves-effect` | `btn btn-primary` |
| `btn btn-light-danger text-danger` | `btn btn-light-danger` |
| `row page-titles` | toolbar (уже в каркасе, из шаблона удалить) |
| `form-group mb-3` | `fv-row mb-5` |
| `table table-striped` | `table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3` |
| `ti-*`, `mdi-*` | `ki-duotone ki-*` (или оставить Font Awesome) |

---

## 5. Откат

```bash
# вернуть старую тему всем: в .env
UI_THEME=materialpro
UI_THEME_SWITCH=false
```

Полный откат: восстановить `*.bak-ui` файлы, удалить провайдер из `config/app.php`,
middleware из `Kernel.php`, каталоги `resources/views/themes/metronic`
и `public/metronic`.

---

## 6. Известные ограничения

* **Меню переехало в шапку.** В demo48 нет боковой панели — дерево меню из БД
  рендерится горизонтальным меню с выпадашками. Если разделов много, имеет смысл
  сгруппировать верхний уровень (или вернуть сайдбар, но тогда нужен бандл
  другого демо Metronic, где стили `app-sidebar` собраны).
* **Font Awesome Pro** должен подключаться отдельно — в бандле Metronic только
  FA Free / KeenIcons / Line Awesome.
* **DataTables** (bootstrap-table) стилизован слоем совместимости, а не нативно.
* Ассеты Metronic не входят в архив патча (~40 МБ) — копируются из вашего
  репозитория `webavgust/metronic` скриптом установки.
