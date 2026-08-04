# Патч v5 — страницы, доступные из меню

Накатывается поверх v4: скопируйте `resources` и `public` в корень проекта с заменой, затем

    php artisan view:clear

**51 файл:** 48 страниц + компонент списка групп сценариев + `osmo-compat-pages.css`
+ обновлённый `layout_short.blade.php` (подключает новый css).

---

## 1. Как определялся список

Меню хранится в таблице `menus`, в репозитории его нет — миграция создаёт только
служебные пункты. Поэтому за основу взят **маршрутный граф**: модуль объявляет
страницы в `app/Modules/*/*/Routes/web.php`, `ModularProvider` их подключает.
Пункт меню — это URL, а URL без маршрута открыть нельзя.

Всё, у чего **нет ни одного GET-маршрута**, из меню недостижимо: даже если пункт
там остался, он ведёт на 404.

Проверить по вашей боевой базе:

```sql
SELECT id, parent_id, name, url, active
FROM menus
ORDER BY parent_id, sort;
```

Если найдёте живой пункт, которого нет в списке ниже, — пришлите, добавлю страницу.

---

## 2. Переведено в этом патче (48 страниц)

| Раздел | Страницы | Маршруты |
|---|---|---|
| Сценарии | `pub/scenario/{index,create,edit}`, `pub/scenario_group/{create,edit}` | `scenario.*`, `scenario_group.*` |
| Компании | `pub/company/{index,create,edit,detail}` | `company.*` |
| Партнёры | `pub/partner/{index,create,edit,detail}` | `partner.*` |
| ПО | `pub/software/{index,create,edit}` | `software.*` |
| Работы | `pub/work/{index,create,edit}` | `work.*` |
| Пользователи | `pub/user/{list,detail,work_calendar,analytics_bind}` | `users.*` |
| Группы пользователей | `pub/user_group/{list,detail}` | `user_group.*` |
| Доступы | `pub/access/{index,create,edit}`, `pub/access_group/{create,edit}` | `access.*`, `access_group.*` |
| Меню | `pub/menu/index` | `menu.index` |
| Логи | `pub/log/{index,all,day}` | `log.*` |
| Календарь | `pub/calendar/index` | `calendar.index` |
| Напоминания | `pub/reminder/{index,filter}` | `reminder.*` |
| Константы | `pub/constant/index` | `constants.index` |
| Уведомления | `pub/notify/list` | `notify.list` |
| Отчёты | `pub/report/{payment,specs,scenarios,scenarios_specs,license_keys,china,popular}` | `report.*` |
| Битрикс | `bitrix/sync/index` | `sync.index` |
| КП | `pub/proposal/detail` | `proposal.detail` |

Ранее (v1–v4): `bitrix/dashboard/index`, `pub/proposal/{index,create,edit}`,
`pub/neuroservice/{index,create,edit}`, `pub/neuroservice_group/{create,edit}`,
`auth`, каркас и меню.

**Итого переведено: 57 страниц из 549 файлов вьюх.**

### Что сделано на страницах

Замены выполнены по всему набору единообразно (298 замен):

* `waves-effect`, `font-weight-medium`, `btn-rounded`, `text-right` — убраны
  или переведены на утилиты Bootstrap 5;
* `table customize-table v-middle` → `table table-row-dashed table-row-gray-300 align-middle`,
  `thead.table-secondary` → `fw-bold text-muted bg-light`;
* `control-label col-form-label` → `col-form-label fw-semibold` (+ `text-lg-end`);
* `btn-info` → `btn-primary`, `btn-outline-*` → `btn-light-*`;
* алерты валидации → `alert alert-danger`;
* иконки `mdi-*`, `ti-*`, `icon-*` (simple-line) → `fa-light fa-*` — около 40 соответствий;
* `row page-titles` скрыт: заголовок страницы рисует toolbar каркаса.

Логика страниц (JS, bootstrap-table, ajax, формы) не тронута.

Двухпанельные экраны (`.email-app` — сценарии, нейросервисы), поиск, календарь,
jsTree в меню и плотные таблицы отчётов стилизованы в `osmo-compat-pages.css`.

---

## 3. Неиспользуемые папки шаблонов

Ни один из этих шаблонов не имеет маршрута — модуля с `Routes/web.php` для них
в проекте нет. Их можно удалять.

### Полностью мёртвые бизнес-разделы

| Папка | Файлов | Почему мёртвая |
|---|---:|---|
| `pub/evaluation/` (+ `box/`) | 8 | модуля `Evaluation` с маршрутами нет; в `routes/web.php` только события |
| `pub/visit/` (+ `box/`) | 11 | модуля `Visit` с маршрутами нет (остались job и репозиторий) |
| `pub/plan_visit/` (+ `boxes/`) | 4 | маршрутов нет |
| `pub/lab_measure/` | 2 | маршрутов нет |
| `pub/lab_object/` | 3 | маршрутов нет |
| `pub/order/` | 2 | модуля `Order` нет — есть только `OrderTask` |
| `pub/calculation/` | 3 | маршрутов нет |
| `pub/work_calendar/` | 1 | дубль: рабочий календарь живёт в `pub/user/work_calendar` |
| `pub/user_department/` | 2 | модуля `UserDepartment` с маршрутами нет |
| `UserDepartment/` | 4 | **пустые файлы (0 байт)** — заготовка генератора |

### Резервные копии и черновики

| Папка / файл | Что это |
|---|---|
| `pub/user.bak/` (10 файлов) | копия `pub/user/` — `list.blade.php` побайтно совпадает с оригиналом |
| `pub/temp/scenarios.blade.php` | маршрут объявлен только при `APP_ENV=development` |
| `test.blade.php` | 123 байта, заглушка |

### Одноразовые страницы в корне `resources/views/`

Маршруты есть, но в меню их нет — это утилиты и эксперименты:

| Файл | Маршрут | Примечание |
|---|---|---|
| `graph.blade.php` | `/graph` | отладочный график |
| `tbl.blade.php` | `/tbl` | тестовая таблица |
| `scenario_unique.blade.php` | `/scenario_unique` | разовый анализ уникальности сценариев |
| `travel.blade.php` | `/travel` | только `APP_ENV=development` |
| `wb.blade.php` | `/wb` | только `APP_ENV=development`, 31 КБ |
| `valentine.blade.php` | `/valentine/{mode}` | только `APP_ENV=development` |
| `welcome.blade.php` | — | стандартная страница Laravel, маршрута нет |
| `dashboards/default.blade.php` | — | 357 байт, не подключается |

### Требует вашего решения

| Папка | Файлов | Ситуация |
|---|---:|---|
| `pub/order_task/` (+ `box/`, `details/`, `sidebars/`) | 16 | **маршруты живые** (`order_task.*`), но раздел относится к лабораторной части, которая в остальном мертва (`order`, `visit`, `lab_*` без маршрутов). Если раздел не используется — сносится вместе с модулем; если используется — переведу в следующем патче |
| `pub/order_task_object/detail` | 1 | то же |
| `pub/contract/detail` | 1 | у модуля `Contract` только box-маршруты, отдельной страницы `detail` нет |
| `templates/word/evaluation_blank` | 1 | шаблон Word для мёртвого раздела `evaluation` |
| `notifications/visit/samplers_notify` | 1 | уведомление мёртвого раздела `visit` |
| `notifications/events/calculation__make` | 1 | уведомление мёртвого раздела `calculation` |

**Итого к удалению: ~60 файлов** (без учёта спорных).

Перед удалением:

```bash
# убедиться, что шаблон нигде не рендерится
grep -rn "pub.evaluation\|pub\.visit\|pub\.order\b" app/ resources/views/ routes/
```

---

## 4. Компоненты

`resources/views/components/` (292 файла) намеренно не чистились: компоненты
подключаются через `x-` теги из десятков мест, надёжно определить мёртвые можно
только полным обходом всех вьюх. Если нужно — сделаю отдельным проходом:
соберу список `x-*` тегов по живым страницам и сопоставлю с файлами компонентов.
