# patch v38 — удалены мёртвые страницы «Рабочий график» и «Привязка аналитика к объектам»

Ставится поверх v36. Номер v37 зарезервирован в `patches/PLAN.md` за синхронизацией Битрикс24 —
v38 от него не зависит. Порядок выкатки: v35 → v36 → v38.

Обе страницы модуля Pub/User — остатки прошлого проекта, найдены 25.09.2026 при работе над v36.
Ни одна не открывалась, в меню ссылок на них нет. **Решение владельца 25.09.2026:** удалить сами
страницы (группа A), мёртвые импорты в трёх сервисах (B) и остальные осиротевшие шаблоны `lab`
(C); файлы удалялись по одному, каждый — с подтверждения владельца (`git rm`, в истории git остаются).

## Почему страницы мёртвые

- **«Рабочий график»** `/user/{id}/work_calendar` — 500: моделей
  `App\Modules\Pub\UserWorkCalendar\Models\UserWorkCalendar` и `App\Modules\Pub\WorkCalendar\Models\WorkCalendar`
  нет в git с первого коммита. Ключа `settings.working_time` в `config/settings.php` тоже нет.
- **«Привязка аналитика к объектам»** `/user/analytics/{id}` — 403 всем: права `lab_objects_bind` нет ни в
  `accesses`, ни в `Gate::define`. За ним контроллер звал несуществующие `UserRepository::getAnalytics()`,
  `LabObjectRepository`, `LabMeasureRepository`, а API — `$user->lab_objects()`, которой у `User` нет.

## Разведка (что проверено перед удалением)

| Что | Где искал | Итог |
|---|---|---|
| Методы, маршруты, вьюхи, компоненты | `app`, `routes`, `config`, `database`, `resources`, `public/js`, `public/metronic/js` — полные имена классов и короткие из строк `use` | только сами страницы и их API |
| Меню | таблица `menus` (`url`) | ссылок нет |
| Маршруты, на которые ссылались мёртвые шаблоны | `route:list` | `work_calendar.index`, `api.work_calendar.set`, `api.lab-measure.*`, `api.lab-object.*`, `lab-object.bind` — не существуют |
| Право `lab_objects_bind` | `accesses`, `AuthServiceProvider` | нет |
| Настройка `user_work_times` | `user_settings` | строк нет (таблица пуста) |
| Таблицы | `information_schema` локальной `avgmom`, `database/migrations` | `*work_calendar*`, `lab_*`, `*measure*` нет, миграций нет |

Прод не проверялся — см. «Перед выкаткой».

## Файлы

Удалены (18):
- A, «Рабочий график»: `resources/views/pub/user/work_calendar.blade.php` (его рендерил `pub::user.work_calendar`),
  `resources/views/themes/metronic/pub/user/work_calendar.blade.php` (неиспользуемая копия),
  `resources/views/pub/user/sidebars/work_calendar_set_time.blade.php`,
  `resources/views/pub/work_calendar/index.blade.php` (производственный календарь — никто не рендерил),
  `resources/views/components/work-calendar.blade.php`, `app/View/Components/WorkCalendar.php`.
- A, «Привязка аналитика»: `resources/views/pub/user/analytics_bind.blade.php`,
  `resources/views/themes/metronic/pub/user/analytics_bind.blade.php`,
  `resources/views/components/lab-object/tree_block.blade.php`, `resources/views/components/lab-object/bind/pad.blade.php`.
- C, осиротевшие шаблоны `lab` (не рендерятся, маршрутов нет): `resources/views/components/lab-object/bind/{pane,measures_out,cb}.blade.php`,
  `resources/views/pub/lab_object/{bind,bind_new,index}.blade.php`, `resources/views/pub/lab_measure/{cost_control,index}.blade.php`.

Изменены (точечно):
- `app/Modules/Pub/User/Routes/web.php` — 3 маршрута (`users.work_calendar`, `users.lab_object_bind`,
  `users.sidebar_work_calendar_set_time`).
- `app/Modules/Pub/User/Routes/api.php` — 4 маршрута (`api.users.work_calendar.set` / `.copy` / `.set_time`,
  `api.users.analytic_bind`).
- `app/Modules/Pub/User/Controllers/UserController.php` — методы `lab_object_bind`, `work_calendar_show`,
  `work_calendar_set_time`; импорты `LabMeasure*`, `LabObject*`, `UserWorkCalendar`, `WorkCalendar`, `Carbon`.
- `app/Modules/Pub/User/Controllers/Api/UserController.php` — методы `work_calendar_set`, `work_calendar_copy`,
  `work_calendar_set_time`, `analytic_bind`; импорты `LabObject`, `UserWorkCalendar`, `WorkCalendar`, `Carbon`.
- `app/Modules/Pub/User/Services/UserService.php` — `analytic_bind()`, импорт `LabObject`.
- `app/Modules/Pub/User/Models/User.php` — связь `work_calendar()`, импорты `UserWorkCalendar`, `LabObject`.
- B: `app/Modules/Pub/Company/Services/CompanyService.php`, `app/Modules/Pub/Partner/Services/PartnerService.php`,
  `app/Modules/Pub/Work/Services/WorkService.php` — строка `use …UserWorkCalendar` (класс в коде не использовался).

Миграций, стилей и правок `config/modular.php` нет.

**Связь с v36.** В v36 (не закоммичен) правились обработчики `success` в двух шаблонах Metronic —
`themes/metronic/pub/user/work_calendar.blade.php` и `analytics_bind.blade.php`. v38 удаляет эти файлы,
так что та часть v36 снимается (в README v36 помечено). Если v36 закоммитить раньше v38 — ничего страшного,
v38 удалит файлы вместе с правкой.

## Перед выкаткой (прод, только чтение)

```sql
SHOW TABLES LIKE '%work_calendar%';  SHOW TABLES LIKE 'lab\_%';  SHOW TABLES LIKE '%measure%';
SELECT id, code FROM accesses WHERE code = 'lab_objects_bind';
SELECT COUNT(*) FROM user_settings WHERE settings LIKE '%user_work_times%';
```

Ожидается пусто / 0, как локально. Если что-то найдётся — данные остаются в базе как есть, код их больше
не читает; решать отдельно (патч базу не меняет).

## Выкатка

1. `git pull`.
2. `su www-root -s /bin/sh -c "php artisan optimize:clear"` (маршруты и вьюхи); `find storage bootstrap/cache -user root | wc -l` = 0.

## Проверка (локально, 25.09.2026)

- `php artisan route:list` работает: 361 → 354 маршрута, ушли ровно эти 7, новых нет.
- `php -l` — все изменённые PHP-файлы без ошибок.
- Поиск по `app`, `routes`, `config`, `resources`, `public/js` — ссылок на удалённое не осталось (кроме
  чужого наследия, см. ниже).
- Страницы (локальный автологин, пользователь 1):

| Адрес | До | После |
|---|---|---|
| `/user/list`, `/user` (своя карточка) | 200 | 200 |
| `/user/1` | 500 `user_sub_users` | 500 `user_sub_users` — прежняя ошибка, не из-за v38 |
| `/companies`, `/companies/detail/37`, `/partners`, `/partners/detail/13`, `/partners/create`, `/works` | — | 200, без исключений в разметке |
| `/user/1/work_calendar`, `/user/1/sidebar/work_calendar_set_time/…` | 500 | 404 |
| `/user/analytics/1` | 403 | 404 |

## Найдено попутно (не правилось)

- **`/api/user/list_table` — 500** `Call to undefined method User::groups()`: `UserRepository` (стр. 37, 62–63)
  берёт связи `groups` и `departments`, убранные вместе с кластером прошлого проекта (на HEAD их тоже нет).
  Таблица на `/user/list` из-за этого, вероятно, пустая. Вынесено отдельной задачей.
- `/user/{id}` — 500 из-за таблицы `user_sub_users`, которой нет (известно, память «кластер прошлого проекта»).
- Прочее наследие с `LabObject` / `LabMeasure` — только импорты и шаблоны Evaluation, к двум страницам
  отношения не имеет: `app/Console/Kernel.php` (`LabOjectService`), `Organization`, трейты `HasTreeStructure`
  и `Searchable`, `View/Components/User/TableCard`, `resources/views/pub/evaluation/*`; импорт
  `EducationTaskCourse` в `UserController`.

## Чек-лист на проде

- [ ] Запросы «Перед выкаткой» — пусто / 0.
- [ ] `php artisan route:list | grep -E "work_calendar|analytic"` — пусто.
- [ ] `/user/list`, `/companies`, `/partners`, `/works`, карточки компании и партнёра открываются.
- [ ] `/user/{id}/work_calendar` и `/user/analytics/{id}` — 404.
