# patch-v29 — журнал изменений сущностей (запрос владельца 14.09.2026)

Ставится поверх `patch-v28`. Итерация 8 плана `patches/PLAN.md`: глобальный
журнал изменений через trait `HasLogger`, лента изменений на карточках КП,
партнёра и компании, просмотр состояния объекта на выбранный момент, право
просмотра через галочку в админ-панели.

---

## Что сделано

### Ядро (этап A)

- Trait `app/Models/Traits/HasLogger.php`, подключается в моделях явно
  (`use HasLogger;`). Каталог `app/Models/traits` переименован в `Traits`
  (namespace `App\Models\Traits`, на Linux регистр важен) — `HasDetailPage`
  переехал вместе с ним.
- Корни агрегатов — `config/entity_log.php`: `proposal`, `partner`, `company`.
  Остальные подключённые модели — части, поднимаются к корню через `logParent()`.
  Всего 20 моделей: КП с вариантами, сценариями, платформами, работами, ПО,
  доплатами, оборудованием и привязками сделок; партнёр с договорами,
  спецификациями, оплатами, сценариями спецификаций, привязками КП, ключами и
  сопоставлением с Битрикс24; компания.
- **Слепки агрегата и дифф.** В конце запроса (middleware `FlushEntityLog`,
  первый в группах `web` и `api`; страховка — `app()->terminating()` в
  `AppServiceProvider`) по «грязным» корням снимается JSON-слепок всего дерева,
  сравнивается с предыдущим, в `entity_logs` пишется событие, в
  `entity_log_changes` — построчные изменения (`EntityLogService`,
  `EntityLogSnapshot`, `EntityLogDiff`). Дочерние строки, которые формы
  пересоздают целиком, поэтому дают «кол-во лицензий: 5 → 7», а не
  «удалено/создано».
- Массовые `update`/`delete` через query builder (статус и сделка КП, привязки
  спецификации, оплаты, сценарии спецификации) обёрнуты в
  `EntityLogService::around(...)`, иначе они бы в журнал не попадали.
- Команда `php artisan entity-log:baseline` — снимает начальные слепки
  («Начало журнала») у корней, у которых записей ещё нет.
- Право `entity_log_view` (`AuthServiceProvider`): администратор панели —
  всегда, остальные — по флагу `users.log_view`.

### Интерфейс (этап B)

- Лента `/timeline/{type}/{key}` (модуль `Pub/EntityLog`): события по дням —
  кто, когда, какое поле поменял, старое и новое значение; фильтр по полю,
  пользователю и событию. У КП лента общая для всех редакций (ключ — `group`).
- Кнопка с иконкой timeline справа в крошках (`layouts/breadcrumbs.blade.php`)
  на карточках КП, партнёра и компании — контроллеры передают `'log_root'`.
- **Состояние на момент.** `?at=<id слепка>` или `?at=Y-m-d` на детальной
  странице (`EntityLogViewService::state()`): модель гидрируется из слепка,
  сверху баннер во всю ширину — «Просмотр состояния на 14 сентября 2026, 11:03»,
  событие и автор, кнопка «Журнал изменений» и крестик возврата к текущему
  состоянию (`layouts/layout.blade.php`). Дата раньше первого слепка — первое
  известное состояние с пометкой «Журнал ведётся с …». Мусор в `?at=` или чужой
  слепок — 404. Редактирование в этом режиме спрятано: у КП — меню «⋮», смена
  статуса, «Прикрепить спецификацию», таблица заметок; у партнёра и компании —
  «Редактировать». Без права `entity_log_view` параметр `?at=` игнорируется.
- Админ-панель: галочка «Видит журнал изменений» в попапе пользователя и
  переключатель в карточке `/admin/users/{id}` (сохраняется сразу,
  `POST /api/admin/users/log_view/{user}`), бейдж «журнал изменений». У админа
  переключатель заблокирован — журнал ему открыт всегда.

## Файлы

Новые:

```
app/Http/Middleware/FlushEntityLog.php
app/Models/Traits/HasLogger.php
app/Modules/Pub/EntityLog/Console/BaselineCommand.php
app/Modules/Pub/EntityLog/Controllers/EntityLogController.php
app/Modules/Pub/EntityLog/Models/EntityLog.php
app/Modules/Pub/EntityLog/Models/EntityLogChange.php
app/Modules/Pub/EntityLog/Routes/web.php
app/Modules/Pub/EntityLog/Services/EntityLogDiff.php
app/Modules/Pub/EntityLog/Services/EntityLogService.php
app/Modules/Pub/EntityLog/Services/EntityLogSnapshot.php
app/Modules/Pub/EntityLog/Services/EntityLogViewService.php
config/entity_log.php
database/migrations/2026_09_14_100001_create_entity_logs_table.php
database/migrations/2026_09_14_100002_create_entity_log_changes_table.php
database/migrations/2026_09_14_100003_add_log_view_to_users_table.php
resources/views/themes/metronic/pub/entity_log/index.blade.php
```

Переименование: `app/Models/traits/` → `app/Models/Traits/`
(`HasDetailPage.php` переехал, namespace `App\Models\Traits`).

Изменённые:

```
app/Console/Kernel.php                                   регистрация BaselineCommand
app/Http/Kernel.php                                      FlushEntityLog первым в web и api
app/Models/ModuleModel.php                               docblock: как подключать HasLogger
app/Providers/AppServiceProvider.php                     страховочный flush при завершении
app/Providers/AuthServiceProvider.php                    Gate entity_log_view
config/modular.php                                       модуль EntityLog в Pub
app/Modules/Pub/User/Models/User.php                     cast log_view, canViewEntityLog()
app/Modules/Admin/Users/Controllers/Api/ApiUsersController.php     поле log_view, logView()
app/Modules/Admin/Users/Routes/api.php                   POST /api/admin/users/log_view/{user}
app/Modules/Admin/Users/Services/AdminUserService.php    флаг log_view, setLogView()
app/Modules/Pub/Proposal/Controllers/ProposalController.php        log_root, состояние ?at=
app/Modules/Pub/Partner/Controllers/PartnerController.php          то же
app/Modules/Pub/Company/Controllers/CompanyController.php          то же
app/Modules/Pub/Proposal/Services/ProposalStatusService.php        around() у массового update
app/Modules/Pub/Proposal/Services/ProposalDealService.php          around() у update/delete
app/Modules/Pub/ContractSpecification/Services/SpecProposalService.php             around()
app/Modules/Pub/ContractSpecification/Repository/ContractSpecificationRepository.php  удаление через Eloquent
app/Modules/Pub/Proposal/Repositories/ProposalRepository.php       удаление через Eloquent
app/Modules/Pub/Payment/Repositories/PaymentRepository.php         удаление через Eloquent
resources/views/themes/metronic/layouts/breadcrumbs.blade.php      кнопка журнала
resources/views/themes/metronic/layouts/layout.blade.php           баннер состояния
resources/views/themes/metronic/pub/proposal/detail.blade.php      без редактирования в режиме состояния
resources/views/themes/metronic/pub/partner/detail.blade.php       то же
resources/views/themes/metronic/pub/company/detail.blade.php       то же
resources/views/themes/metronic/admin/users/boxes/form.blade.php   галочка «Видит журнал изменений»
resources/views/themes/metronic/admin/users/show.blade.php         бейдж и переключатель
```

Модели с `use HasLogger` (20 — только подключение trait'а и `logParent()`/`logChildren()`):
`Company`, `Contract`, `ContractSpecification`, `ContractSpecificationProposal`,
`ContractSpecificationScenario`, `Hardware`, `LicenseKey`, `Partner`,
`PartnerCrmCompany`, `Payment`, `Proposal`, `ProposalCrmDeal`, `ProposalSoftware`,
`ProposalVariant`, `ProposalVariantExtraPay`, `ProposalVariantPlatform`,
`ProposalVariantScenario`, `ProposalVariantSoftware`, `ProposalVariantWork`,
`ProposalWork` (каталоги `app/Modules/Pub/*/Models/`).

## Руками

1. `php artisan migrate` — таблицы `entity_logs`, `entity_log_changes`, колонка
   `users.log_view`.
2. `composer dump-autoload` — каталог `app/Models/traits` переименован в
   `Traits`. Если файлы копировались, а не переносились, старый каталог `traits`
   на сервере удалить: на Linux это два разных пути.
3. `php artisan entity-log:baseline` — один раз: начальные слепки всех КП,
   партнёров и компаний. Без команды baseline снимется у объекта при первой
   его правке, и журнал у него начнётся с этого момента.
4. `php artisan optimize:clear`.
5. Проверить `config/modular.php`: в `modules.Pub` есть `'EntityLog'`
   (в патче лежит обновлённый файл).
6. Выдать галочку «Видит журнал изменений» тем, кому нужен журнал
   (админ-панель → пользователь). Администраторам панели она не нужна.

## Чек-лист проверки

- [x] Правка КП (кол-во лицензий у сценария) → в ленте `/timeline/proposal/{group}`
      событие «Изменение» с полем, старым и новым значением; кнопка timeline
      в крошках ведёт туда.
- [x] Смена статуса КП (массовый update по группе) → событие в ленте.
- [x] Оплаты спецификации (форма пересоздаёт строки) → в ленте партнёра одно
      событие с диффом, а не «удалено/создано».
- [x] Правка партнёра → лента партнёра.
- [x] `?at=<id слепка>` и `?at=Y-m-d` на карточках КП, партнёра, компании: баннер,
      меню «⋮», статус и «Редактировать» спрятаны; крестик возвращает к текущему
      состоянию; `?at=abc` и чужой слепок → 404; дата раньше первого слепка →
      первое состояние с пометкой «Журнал ведётся с …».
- [x] Пользователь без галочки: кнопки в крошках нет, `/timeline/...` → 403,
      `?at=` игнорируется; после галочки — лента и состояние доступны.
- [x] Админ-панель: галочка в попапе и переключатель на карточке сохраняются,
      бейдж обновляется; у админа переключатель заблокирован.
