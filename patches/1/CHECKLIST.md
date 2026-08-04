# Чек-лист проверки после установки

Проверять в двух режимах: переключатель в шапке → **MaterialPro** и **Metronic**.
В старом оформлении всё должно выглядеть ровно как раньше — это главная проверка
на то, что патч ничего не сломал.

## 0. Смоук-тест (5 минут)

- [ ] `/` открывается в обоих оформлениях
- [ ] переключатель в шапке виден и переключает (cookie `ui_theme`)
- [ ] после переключения выбор сохраняется при переходах и после перезагрузки
- [ ] тёмная тема (иконка луны) переключается и запоминается (`localStorage`)
- [ ] меню в шапке раскрывается, ссылки ведут туда же, что и в сайдбаре
- [ ] в консоли браузера нет ошибок JS (особенно `AdminSettings`, `select2`, `toastr`)
- [ ] `spider_tick` работает: индикатор уведомлений появляется/гаснет
- [ ] мобильная ширина: бургер открывает меню-drawer

## 1. Общие механики (проверить на любой странице)

- [ ] модалка `box()` — открывается, закрывается, кнопки внутри работают
- [ ] офф-канвас `sidebar()` — открывается и закрывается
- [ ] `x-ui.a.ajax` (например «Обновить доступы» в профиле) — уведомление toastr
- [ ] `select2` — одиночный и множественный, поиск, очистка
- [ ] sweetalert2 — подтверждения удаления
- [ ] загрузка файлов (dropzone) — `components/files/dropzone`
- [ ] блокировка экрана `blockUI` во время ajax
- [ ] тултипы и дропдауны Bootstrap внутри подгруженного ajax-html
- [ ] пагинация и сортировка в `bootstrap-table`
- [ ] datepicker / jquery-ui в фильтрах

## 2. Страницы

### Приоритет 1 — предложения
- [ ] `pub/proposal/index` — список, фильтры, экспорт
- [ ] `pub/proposal/create` — создание, расчёты, `extra-pays`, `hardware_table`
- [ ] `pub/proposal/edit` — редактирование, пересчёт сумм, сохранение
- [ ] `pub/proposal/detail` — просмотр, печать/PDF

### Приоритет 2 — заказы и задачи
- [ ] `pub/order/index`, `pub/order/detail`
- [ ] `pub/order_task/index`, `create`, `create_2`, `edit`, `edit_2`, `detail`
- [ ] `pub/order_task_object/detail`
- [ ] сайдбары задач (`pub/order_task/sidebars/*`)

### Приоритет 3 — выезды, лаборатория
- [ ] `pub/visit/index`, `edit`, `fill`, `lab`, `task`, `view`
- [ ] `pub/plan_visit/index`
- [ ] `pub/lab_measure/index`, `cost_control`
- [ ] `pub/lab_object/index`, `bind`, `bind_new`

### Справочники
- [ ] `pub/company/index`, `detail`, `create`, `edit`
- [ ] `pub/partner/index`, `detail`, `create`, `edit`
- [ ] `pub/software/*`, `pub/work/*`, `pub/scenario/*`, `pub/scenario_group/*`
- [ ] `pub/neuroservice/*`, `pub/neuroservice_group/*`
- [ ] `pub/menu/index`, `pub/constant/index`

### Пользователи и доступы
- [ ] `pub/user/list`, `detail`, `work_calendar`, `analytics_bind`
- [ ] `pub/user_group/*`, `pub/user_department/*`
- [ ] `pub/access/index`, `create`, `edit`, `pub/access_group/*`

### Отчёты
- [ ] `pub/report/payment`, `license_keys`, `china`, `popular`
- [ ] `pub/report/scenarios`, `scenarios_specs`, `specs`
- [ ] `pub/calculation/index`, `supervisor`, `tender`

### Прочее
- [ ] `pub/calendar/index` (fullcalendar)
- [ ] `pub/reminder/index`, `filter`
- [ ] `pub/log/index`, `all`, `day`
- [ ] `pub/evaluation/index`, `init`, `edit`, `detail`
- [ ] `pub/notify/list`
- [ ] `bitrix/dashboard/index`, `bitrix/sync/index`
- [ ] страница входа (`auth.blade.php`) в обеих темах

### Печать и выгрузки
- [ ] PDF: `templates/pdf/task_order`, `templates/pdf/calendar_events`
- [ ] Word: `templates/word/evaluation_blank`
- [ ] Email: `templates/email/notify`
- [ ] печать страницы из браузера (`@media print`)

## 3. На что смотреть на каждой странице

1. Не разъехалась ли сетка (`row` / `col-*`) внутри `app-content`.
2. Читаются ли бейджи статусов (цвет + контраст), особенно в тёмной теме.
3. Не обрезаются ли широкие таблицы (`.table-responsive`).
4. Влезают ли кнопки в toolbar (блок `@yield('breadcrumb_right')`).
5. Работают ли inline-скрипты страницы (`@section('js')`).

## 4. Если что-то сломалось

* Вёрстка конкретной страницы — скопируйте её в
  `resources/views/themes/metronic/…` и правьте там, старая версия останется целой.
* Глобальный дефект (класс из MaterialPro) — правило в
  `public/metronic/css/osmo-compat.css`.
* Ошибка JS — смотрите `public/metronic/js/osmo-metronic.js`, скорее всего
  не хватает заглушки или переинициализации плагина.
* Быстрый откат для всех: в `.env` → `UI_THEME=materialpro`, `UI_THEME_SWITCH=false`.
