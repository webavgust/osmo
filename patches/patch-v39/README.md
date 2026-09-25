# patch v39 — список пользователей: `/api/user/list_table` без связей групп и подразделений

Ставится поверх v38. Номер v37 зарезервирован в `patches/PLAN.md` за синхронизацией Битрикс24 —
v39 от него не зависит. v35, v36, v38 уже на проде (25.09.2026) — v39 выкатывается следующим.

Найдено 25.09.2026 при работе над v38.

## Что было сломано

`GET /api/user/list_table?_token=…` (маршрут `api.users.list` → `Api\UserController::list_table` →
`UserService::tableDefault` → `UserRepository::getTable`) отвечал **500**
`Call to undefined method App\Modules\Pub\User\Models\User::groups()`. `getTable()` фильтровал по
`group_id` / `department_id` через `whereHas('groups' | 'departments')` и добавлял
`withCount('groups', 'departments')` и `with('groups', 'departments')`. Эти связи убраны из `User` вместе с
модулями `UserGroup` / `UserDepartment` при чистке кластера прошлого проекта (коммиты `452f672`, `8b05b65`;
таблицы `user_user_group` и `user_user_department` на проде были пусты).

Из-за этого обе таблицы, которые берут данные с этого маршрута, были **пустыми**:
- «Пользователи» `/user/list` (`users.list`);
- «Доступы → Персональные» `/access/set/user` (`access_set.user_list`).

## Какой шаблон на самом деле рисуется

Оба контроллера рендерят вьюхи через `pub::` — `view('pub::user.list')` и `view('pub::user.access.list')`.
`ResolveUiTheme` подкладывает каталог темы только для имён без `::`, поэтому **в обеих темах** рисуются
`resources/views/pub/user/list.blade.php` и `resources/views/pub/user/access/list.blade.php`; тема Metronic
меняет только макет вокруг. Копия `resources/views/themes/metronic/pub/user/list.blade.php` не используется
(проверено по разметке: в теме Metronic у `#filter` нет класса `bt-toolbar`, у кнопки синхронизации остался
`waves-effect` — это разметка из `pub/`). У `access/list` копии в теме нет.

## Что ждут шаблоны

JS обоих шаблонов (bootstrap-table, `data-side-pagination="server"`) читает из ответа `total`,
`totalNotFiltered`, `rows`, а в строке — `id`, `last_name`, `name`, `second_name`, `active`,
`personal_photo`, `work_department`, `work_position`, `email`, `personal_mobile`, `work_phone`,
`personal_birthday`. Параметры запроса — `search`, `sort`, `order`, `offset`, `limit`.
**`groups`, `departments`, `groups_count`, `departments_count`, `group_id`, `department_id` не используются
нигде** (поиск по `resources/views/pub/user`, `resources/views/themes/metronic/pub/user`, `public/js`).
Состав полей строки задаёт `User::$showFields` и не менялся.

## Правки (точечно)

- `app/Modules/Pub/User/Repositories/UserRepository.php`, `getTable()`:
  - убраны фильтры `group_id` / `department_id` (`whereHas('groups' | 'departments')`), на их месте —
    комментарий с причиной;
  - `->withCount('groups', 'departments')->with('groups', 'departments')` убраны, остался
    `->select(User::getShowFields())`.
  Если старый клиент передаст `group_id` / `department_id` — параметры молча игнорируются.
- `resources/views/pub/user/list.blade.php`, `resources/views/pub/user/access/list.blade.php`,
  `personFormatter`: `row.second_name` → `(row.second_name || '')`. Отчества нет ни у одного
  пользователя (`second_name = null` у всех семи), и как только таблица ожила, после имени у всех
  выводилось слово «null».

Не менялось: неиспользуемая копия `themes/metronic/pub/user/list.blade.php` (в ней осталось «null», но она
не рисуется), маршруты, `config/modular.php`, миграции, стили, `public/`.

## Руками

Ничего. Кеш вьюх сбросить при выкатке (ниже).

## Выкатка

1. `git pull`.
2. `su www-root -s /bin/sh -c "php artisan view:clear"`; `find storage bootstrap/cache -user root | wc -l` = 0.

## Проверка (локально, 25.09.2026; автологин, пользователь 1)

| Что | До | После |
|---|---|---|
| `GET /api/user/list_table?…&order=asc&offset=0&limit=25` | 500 `User::groups()` | 200, `total` 7, `totalNotFiltered` 7, 7 строк |
| `…&search=Анн` | 500 | 200, 2 строки из 7 |
| `…&sort=email&order=desc&offset=25&limit=10` | 500 | 200, 0 строк (всего 7) |
| `…&group_id=1&department_id=2&limit=5` | 500 | 200, 5 строк (параметры игнорируются) |
| `/user/list`, старая тема (Browser pane) | таблица пустая | 7 строк, «Записи с 1 по 7 из 7», без «null», ошибок в консоли нет |
| `/user/list`, тема Metronic (Browser pane) | таблица пустая | 7 строк, неактивные — красным, ошибок в консоли нет |
| Поиск в таблице «Круч» (`resetSearch`) | — | 2 строки, «Записи с 1 по 2 из 2» |
| `pub::user.access.list` — рендер на сервере (tinker) | — | рендерится, правка на месте, `data-url` на `list_table` |

- `php -l` — `UserRepository.php` без ошибок.
- Поиск `groups` / `departments` на `User` по `app`, `resources`, `routes` — других вызовов нет.
- `route:list` — 354 маршрута, как после v38.

Страницу `/access/set/user` в браузере открыть нельзя, см. ниже.

## Найдено попутно (не правилось)

- **`/access/set/user` — 403 всем, включая админа.** Маршрут закрыт `can:access_set`, а записи с кодом
  `access_set` в локальной таблице `accesses` нет, поэтому шлюз отказывает. Карточка персональных доступов
  `/access/set/user/{user}` под тем же правом. На проде не проверялось (запрос — в «Перед выкаткой»).
- **Неизвестный `sort` → 500** (`Unknown column … in 'order clause'`): `getTable()` передаёт `sort` из
  запроса в `orderBy` как есть. Со страницы не воспроизводится — таблица сортирует только по реальным
  колонкам (`last_name`, `email`, `personal_mobile`, `work_phone`, `personal_birthday`); инъекции нет,
  Laravel экранирует имя колонки.
- В `UserRepository` остались импорты `EducationApplication`, `EducationTask`, `Access`, `AccessUserService`,
  `DB` — в классе не используются.

## Перед выкаткой (прод, только чтение)

```sql
SELECT id, code FROM accesses WHERE code = 'access_set';
```

Если пусто — «Персональные доступы» закрыты и на проде (патч это не меняет, решать отдельно).

## Чек-лист на проде

- [ ] `/user/list` — таблица с пользователями, «Записи с 1 по N из N», без «null» после имени.
- [ ] Поиск по фамилии сужает таблицу, пагинация и сортировка по Ф.И.О. / E-mail работают.
- [ ] Во вкладке «Сеть» запрос `api/user/list_table` — 200.
- [ ] Если право `access_set` на проде есть — `/access/set/user` показывает таблицу пользователей.
