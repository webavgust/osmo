# План итераций по ТЗ от 2026-09-09

Источник: `C:\Users\hakka\Downloads\ТЗ osmo.txt` (шесть пунктов: реестр сделок
Bitrix, API Алексея, партнёры ↔ сделки и проекты, значок проекта, скоринг,
список КП без перезагрузки). Перед каждой итерацией владелец делает `/clear`,
поэтому всё, что нужно знать для работы, лежит здесь. Каждая итерация — один
патч `patches/patch-vNN` с README (формат — как в `patches/patch-v20/README.md`),
один коммит в `master`, выкатка `git pull` на проде (см. память
`server-is-reference`). Тяжёлую реализацию отдавать агентам (просьба владельца).

Разбор логической модели данных (что спроектировано неудачно и что с этим
делать) — в соседнем файле **`patches/ARCHITECTURE.md`**; схема связей —
`patches/schema.html`. Рефакторинг по нему владелец поручит отдельной задачей.

## Статус

| # | Патч | Содержание | Состояние |
|---|------|------------|-----------|
| 1 | v21 | API Алексея (OSMOVIEW CP): забирать КП и переносить к себе (ТЗ п.2) | закоммичен 2026-09-09 (a2a4582 + 531cbba), на прод не выкачен; API закрыт авторизацией AI Studio — данные заливаются командой импорта |
| 2 | v22 | Реестр сделок Bitrix + выгрузка в Excel (ТЗ п.1) | закоммичен 2026-09-09 (a2a4582 + 531cbba), на прод не выкачен |
| 3 | v23 | Партнёры ↔ компании Bitrix, вкладки на карточке партнёра, вкладка «Сделки Битрикс» (ТЗ п.3, часть 1) | готов целиком: часть 1 закоммичена 2026-09-09 (a2a4582 + 531cbba), часть 2 (табы, `GET /partners/deals/{partner}`, `bitrix/deal/_tab`) закоммичена 2026-09-11, на прод не выкачена |
| 4 | v24 | Сущность «Проект» по сделкам, вкладки «Проекты» / «Архив проектов», разовое автосоздание (ТЗ п.3, часть 2) | готов: модуль `DealProject`, попапы, колонка «Проект» и кнопка «проект» в реестре, вкладки «Сделки» / «Проекты» / «Архив проектов» **на `/bitrix/deal`** (правка владельца 10.09: не на карточке партнёра), привязка КП к сделке прямо из реестра, команда `deal-project:seed` — проверено локально (21 проект из 37 сделок, 16 ждут сопоставления партнёров), закоммичен 2026-09-11, на прод не выкачен |
| 5 | v25 | Партия правок владельца 11.09.2026 (9 пунктов) + список КП без перезагрузки (п.6) | закоммичен 2026-09-11, на прод не выкачен; **значок проекта (п.4) не делался** — в партию не входил |
| 6 | v26 | Скоринг партнёров: сделки и проекты вместо «КП за год», новые веса (п.5) | закоммичен 2026-09-11, на прод не выкачен |
| 7 | v27 | Правки владельца 13.09.2026 (п. 13–35 ниже): меню, статусы КП, Битрикс24 и др. | не закоммичен |
| 8 | v28 | Админ-панель: признак админа, пользователи, журнал входов, константы (см. «Итерация 7») | в работе: этап A готов и проверен (слепок прав до/после, вход под Алексеем); этап B (пользователи, мягкое удаление) готов и проверен в браузере (список, карточка, журнал входов, попап, флаг темы туда-обратно); этап C (константы: 30 в `consts`, 25 вынесено из кода, слепок расчётов до/после совпал) готов и проверен в браузере; не закоммичено; п.3 запроса ждёт владельца |
| 9 | v29 | Журнал изменений сущностей: trait `HasLogger`, слепки и диффы, лента изменений, просмотр состояния на дату (см. «Итерация 8») | готов 14.09.2026: этап A (ядро: миграции, trait, модуль `EntityLog`, 19 моделей, baseline 655 слепков), B1 (лента `/timeline/{type}/{key}` с фильтрами, кнопка в крошках — проверено в браузере), B2 (состояние на дату `?at=` с баннером и скрытым редактированием, флаг `log_view` в админке, `patches/patch-v29/README.md`) — проверки агентов через HTTP-ядро; не закоммичено |
| 10 | v30 | Рабочий стол: сетка виджетов 32 колонки, библиотека с превью, пресеты свои и системные (см. «Итерация 9») | в работе с 14.09.2026: проект и каталог 80 виджетов — артефакт https://claude.ai/code/artifact/8ad76927-7dc1-4663-a899-dccc237e891e; решения владельца получены; каркас (мой), A2 сервис и API, W1 и W2 (8 виджетов), W3 (реестр показателей `Desktop/Metrics/MetricRegistry` + kpi, proposal_status, scoring_top; показатели оплат и ключей добавил я через виджеты W2), W4 (funnel_table, country_month; показатели воронки в реестр добавил я) — готовы и проверены скриптами, первая партия — все 14 виджетов, у «Числа» 19 показателей; A3 страница — готова, перетаскивание с полупрозрачной копией в конечной позиции проверено мной в браузере; A4 (библиотека — агент завис, контроллер/разметку/JS/CSS написал я; попап настроек; столы, пресеты, пакетная отрисовка; домик/вход/меню/крошки/удаление `Pub/Dashboard`) — готов, всё проверено мной в браузере (окно 1600 px): добавление из библиотеки, перетаскивание карточки с копией в конечной позиции, применение настроек «Числа», меню и попап столов; README `patches/patch-v30/README.md`; не закоммичено; A4 (библиотека, настройки, столы, домик/меню) — после A3 |

Параллельно ведутся только независимые итерации (v21, v22, v23-сопоставление):
v23-вкладки опираются на таблицу реестра из v22, v24 — на v22 и v23,
v25 — на v24, v26 — на v23 и v24, поэтому идут после.

После завершения итерации: обновить эту таблицу (патч, коммит, дата),
дописать строку в `github.md` (раздел «Патчи»), README патча положить в
`patches/patch-vNN/README.md`.

## Итерация 7 · patch-v28 · Админ-панель (в работе, запрос владельца 13.09.2026)

Запрос: признак админа (выдать Анне), ссылка «Админ-панель» рядом с «Мой профиль»;
страницы: 1) пользователи — список, создание, редактирование, удаление, карточка
пользователя (пока журнал авторизаций и запрет переключения на старую тему; дальше —
статистика); 2) константы — таблица `consts` с кодом и примечанием, редактирование,
вынос констант из кода; 3) — владелец не дописал, уточнить.

Уточнение владельца: «убери у всех is_admin, пусть будут обычными пользователями, но
чтобы не сломался доступ к страницам публичной части; переключатель тем — отдельным флагом».

**Факты (разведка 13.09.2026):**
- `users.is_admin` уже был у 5 из 7; `User::isAdmin()` = `is_admin || can_do('super_user')`,
  а `super_user` (accesses.id = 6) стоял у всех семи — фактически админом был каждый.
  Админу любое право выдаётся автоматически (`admin_invert = 0` у всех 9 прав).
  В `access_user` у пользователей не было ничего, кроме `super_user`.
- Права → Gate через `AuthServiceProvider` (class/method из `accesses`). У
  `payment_calendar_view` и `deal_card_view` в class/method прописаны контроллеры —
  эти Gate падают TypeError (давно, не связано с v28); `can_do()` по ним работает.
- Gate `general_access` в `accesses` был привязан к `AccessPolicy::access_view` (проверял
  «Просмотр доступов»). Слепок «после» показал: у обычных пользователей `can_do` = 1, а Gate = 0
  и меню пустое. В `patch_v28_admin.sql` привязка исправлена на
  `AccessGroupPolicy::general_access`.
- Проверка `general_access` в `AuthCheckGlobalAccess` закомментирована; но пункты меню
  «Работа», «Отчёты», «Справочники» привязаны к `general_access` — без него сайдбар пуст.
- Кэш прав `can_do_{uid}` — навсегда; после любых правок прав — `cache:clear`.
- Журнал входов уже пишется: `user_auth_attempts` (login, user_id, success, ip,
  user_agent, attempted_at), `AuthAttemptService` из `UserController`.
- `consts`: `name`, `key`, `value`, `system`; 5 записей; модуль `Pub/Constant`
  (`/constants`, `can:constant_control` — такого права в `accesses` нет, страница закрыта всем).
- На пользователя ссылаются 19 колонок без внешних ключей (proposals.manager_id,
  payments.user_id, deal_projects.created_by, reminders, calendar и др.).

**Решения:**
- `is_admin` = доступ в админ-панель (Gate `admin_panel`, `User::isPanelAdmin()`), только Анна.
  Обычным пользователям снят `super_user`, явно выданы `general_access`,
  `payment_calendar_view`, `deal_card_view` (`patch_v28_admin.sql`). Раздел «Настройки»
  (Меню, Доступы) у них пропадает.
- Что перестаёт работать у обычных пользователей (было за счёт «все админы»): удаление
  спецификаций (`ContractSpecification::canDelete` = `is_admin()`), правка чужих событий
  календаря, напоминаний и заметок, скрытые уведомления, выбор любого пользователя в
  напоминаниях (`getSubUsers`), пункт «Обновить доступы» и ID в меню профиля.
- Переключатель тем — флаг `users.ui_theme_switch` (миграция, по умолчанию 1 — как было у
  всех). При 0 переключатель скрыт, `ResolveUiTheme` принудительно ставит Metronic.
- Удаление спецификаций договоров владелец вернул всем пользователям:
  `ContractSpecification::canDelete()` = `auth()->check()` (было `is_admin()`); проверяют его
  API `ApiContractSpecificationController::delete` и кнопка в попапе редактирования.
- Удаление пользователя — мягкое (решение владельца): `users.deleted_at` + `SoftDeletes`,
  данные и связи остаются, в списке фильтр «Удалённые» и «Восстановить»; связи других моделей
  на пользователя — `withTrashed()`, чтобы имена не пропадали. Себя удалить нельзя.

**Этапы:** A — права, флаг темы, Gate (сам); B — модуль админ-панели и пользователи (агент);
C — константы (агент, после B); проверка слепком прав до/после (`scratchpad/access_*.json`).

**Этап B — сделан (агент, 13.09.2026):**
- Модули `app/Modules/Admin/Panel` (редирект `/admin` → список) и `app/Modules/Admin/Users`
  (`UsersController`, `Api/ApiUsersController`, `Services/AdminUserService`); `config/modular.php`:
  `Admin => ['Panel', 'Users']`, `can:admin_panel` в группе. Web-маршруты группы Admin получают
  префикс `/admin` от `app/Providers/ModularProvider.php` (имена `admin.*` прописаны явно); API —
  `/api/admin/users/*` под `can:admin_panel` + `ajax.api` (в `<meta name="_token">` — ajax_token,
  CSRF сессии там нет, поэтому AJAX через api, как у соседних модулей).
- Вьюхи только в теме: `themes/metronic/admin/{partials/nav, users/index, users/show,
  users/boxes/form, users/partials/js}`. `ResolveUiTheme` на `/admin*` принудительно ставит
  Metronic (в старой теме этих вьюх нет).
- Список с отбором «Все / Активные / Отключённые / Удалённые», попап создания/правки
  (пароль — `Hash::make`, bcrypt, как у `Auth::attempt`; email необязателен — у части
  пользователей он пустой), запрет снять админа / отключить / удалить себя, новому
  пользователю — права `general_access`, `payment_calendar_view`, `deal_card_view`.
- Мягкое удаление: миграция `2026_09_13_100100_add_deleted_at_to_users_table`, `SoftDeletes` в
  `User`, «Восстановить»; удалённый не входит и теряет сессию; `withTrashed()` у связей на
  пользователя (`Proposal::manager/status_author`, `Payment::user`, `Reminder`, `Calendar`,
  `UserNote`, `UserSetting`, `DealProject*`, `ProposalCrmDeal`, `ContractSpecificationProposal`,
  `ExternalProposal::transferred_user`, `HasCreator::creator`); `getAllWithTrashed()` — с `withTrashed()`.
- Карточка `/admin/users/{user}`: шапка, заглушка «Статистика», переключатель «Разрешить
  переключение на старую тему», журнал авторизаций (по user_id и по логину/email, по 50).
- Ссылка «Админ-панель» — в меню пользователя сайдбара (и в `header.blade.php` на будущее).

**Этап C — сделан (агент, 13.09.2026):**
- Миграция `2026_09_13_100200_add_note_to_consts_table`: `consts.note` + уникальный индекс
  `consts_key_unique`. Колонка `key` не переименована — в интерфейсе это «Код».
- `Constant`: `get`/`set` как были (`set` подставляет `name` при вставке), новые `value` / `int` /
  `float` / `json` / `flush` с кэшем на запрос (сброс на `saved`/`deleted`).
- Модуль `app/Modules/Admin/Consts` (`/admin/consts`, попап создания/правки, удаление несистемных;
  JSON — форматированно в форме, компактно в БД, с проверкой; у системных значение только для
  чтения). Вьюхи `themes/metronic/admin/consts/{index, boxes/form}`.
- В `consts` вынесено 25 настроек из кода (`patch_v28_consts.sql`, всего констант 30, у всех
  примечания): веса скоринга, веса лет и глубина истории, горизонты лицензий и «хвост» истёкших,
  пороги скидок, «скоро» в платёжном календаре, допуски сверки спецификаций и CRM, даты «сделки с»,
  шаблон ссылки на сделку и коды UF-полей Битрикс24, пакет detail и пороги переноса OSMOVIEW CP,
  лимит поиска сделок. PHP-константы остались значениями по умолчанию, чтение — через методы
  классов (`PartnerScoringService::weights()`, `LicenseRegistryService::horizons()` и т.п.).
  UF-поля в SQL подставляются с проверкой (латиница/цифры/`_`), дата и шаблон ссылки — с откатом
  к значению из кода при неверном вводе.
- Проверка: слепок 22 расчётов до/после — совпал полностью; смена `scoring_weight_deals` на 11
  меняет баллы и места, возврат — снова совпадение.
- Не перенесено: старый дашборд Битрикса и старые вьюхи (строки зашиты в разметку, их правит
  владелец), продление лицензий в старой теме (горизонты 30/60/90 в разметке), дата разовой команды
  `deal-project:seed`, пороги подписей `LicenseRenewalService::urgency()`, `NDS_DEFAULT`.
- `artisan view:cache` падает на старых вьюхах прошлого проекта (`order_task.cell.id` в
  `pub/contract/detail`, `components/dashboard/lab_supervisor/...`) — не связано с v28.

## Итерация 8 · patch-v29 · Журнал изменений сущностей (запрос владельца 14.09.2026)

Запрос: глобальный механизм логирования сущностей через trait `HasLogger` (рядом с
`ModuleModel`). У подключённых модулей на детальной странице справа в крошках — кнопка с
иконкой timeline → общая страница ленты: кто, когда, какое поле поменял, старое и новое
значение, фильтр по полю модели (пример: в КП поменяли кол-во лицензий у одного из
вариантов — это должно быть видно). Плюс просмотр состояния модели на выбранный момент:
клик по дате открывает детальную страницу этой модели, сверху крупный баннер во всю ширину
«Просмотр состояния на 1 сентября» и крестик, возвращающий к текущему состоянию.
Логируется для всех; смотреть могут админ и те, кому в админке поставили галочку.
Модели: КП, Партнёр, Компания, Договор, Спецификация, Оплата и т.д.

**Факты (разведка 14.09.2026):**
- Почти все записи идут через Eloquent (`create/update/save/delete`) — события моделей есть.
  Исключения (query builder, событий нет): `ProposalStatusService::set` и
  `ProposalDealService::syncMain` / `setMain` (`Proposal::where('group')->update`),
  `SpecProposalService::win` / `detach`, `ProposalDealService::detach`,
  `PaymentRepository::create` (`$spec->payments()->delete()`), `ProposalRepository::update`
  (`software()->delete()`, `works()->delete()`), `ContractSpecificationRepository::update`
  (`contract_specification_scenarios()->delete()`).
- **Дочерние строки пересоздаются целиком** при каждом сохранении: у КП — сценарии/платформы/
  работы/ПО вариантов и `proposal_works`/`proposal_software` (варианты `proposal_variants`
  сохраняются на месте, `variant_id` приходит из формы); у спецификации — сценарии; у
  спецификации — все оплаты (`PaymentRepository::create`: удалить всё, создать заново; форма
  `pub/payments/boxes/control.blade.php` шлёт строки `payment[i][...]` без id). Поэтому
  событийный лог «по строкам» дал бы на каждое сохранение кучу «удалено/создано», а не
  «кол-во лицензий: 5 → 7». Отсюда решение — **слепки агрегата и дифф слепков**.
- «Кол-во лицензий» из примера владельца — `proposal_variant_scenarios.count`
  (колонка «ЛИЦЕНЗИИ» карточки КП); у платформ/ПО/работ тоже `count`.
- Детальные страницы есть у трёх моделей: `proposal.detail` (`/proposals/detail/{group}/{iteration}`),
  `partner.detail`, `company.detail`. Договор, спецификация, оплата, ключи — попапы на
  карточке партнёра, своих страниц нет. Крошки: `HasBreadcrumb` + `'breadcrumbs'` во view,
  тулбар справа — `@yield('breadcrumb_right')` в `themes/metronic/layouts/breadcrumbs.blade.php`.
- КП: строка `proposals` = редакция (`group` + `iteration`), статус и сделка пишутся во все
  редакции группы. Новая редакция = новые строки всего дерева (`ProposalRepository::create`
  с `parent`), поэтому id вариантов между редакциями не совпадают.
- Старый модуль `Pub/Log` — это «заметки» (журналирование вручную, таблица `logs`,
  `x-proposal.log_table`); с новым журналом не пересекается, имена не должны совпадать.
- Объёмы (локальная база): proposals 432, variants 592, scenarios 1873, partners 58,
  companies 165, contracts 26, specs 68, payments 127, keys 100, hardware 333, extra_pays 48 —
  слепки целиком в JSON допустимы.
- Font Awesome Pro лежит в `public/assets/libs/fontawesome/css/all.min.css`, `fa-timeline`
  и `fa-clock-rotate-left` есть. Metronic-таймлайн (`timeline`, `timeline-item`,
  `timeline-line`, `timeline-icon`, `timeline-content`) есть в `style.bundle.css`.

**Решения (архитектура):**
- Модуль `app/Modules/Pub/EntityLog` (в `config/modular.php` → Pub), trait
  `app/Models/Traits/HasLogger.php` (namespace `App\Models\Traits`), подключается в моделях
  явно (`use HasLogger;`), `ModuleModel` не трогаем (только docblock-ссылка на trait).
- **Агрегаты.** Корень — модель с детальной страницей; части — модели, у которых
  `logParent()` возвращает родителя (цепочка до корня, кэш на запрос по `class:id`):
  - `Proposal` (ключ ленты — `group`, т.е. лента общая для всех редакций; слепок — одна
    строка-редакция): `variants` → (`proposal_scenarios`, `proposal_platforms`,
    `proposal_works`, `proposal_software`, `extra_pays`, `hardware`); `software`, `works`
    (уровня КП); `ProposalCrmDeal` (привязки сделок, связь по `proposal_group` — родитель =
    последняя редакция группы).
  - `Partner`: `contracts` → `contract_specifications` → (`payments`,
    `contract_specification_scenarios`, `proposal_links`, `license_keys`); `crm_companies`.
  - `Company`: только свои поля (договоры/спецификации живут в ленте партнёра —
    их правят с карточки партнёра).
- **Слепок** — JSON: `{class, key, title, attrs: getAttributes() без игнорируемых,
  children: {relation: [слепки]}}`; храним сырые значения (даты и json строками из БД),
  чтобы гидрация была точной. Таблица `entity_logs`: `id, type (слаг корня: proposal /
  partner / company), group_key (varchar 64: group у КП, id у остальных), model_id, event
  (baseline | created | updated | deleted), user_id nullable, title, data (longtext json,
  null у deleted), changes_count, created_at`; индексы `(type, group_key, created_at)`,
  `(type, model_id)`. Таблица `entity_log_changes`: `id, entity_log_id, kind (changed |
  added | removed), model_class, model_key, path (varchar 500 — «Вариант 2 (1 год) →
  Нейросервис «X»»), field nullable, label, old_value, new_value (сырые, text), old_label,
  new_label (человеческие, сформированы в момент записи: связи → название, enum →
  label, bool → да/нет, даты d.m.Y, деньги через `cost_normalize`)`; индексы
  `(entity_log_id)`, `(model_class, field)`.
- **Когда пишем.** События trait: `creating/updating/deleting` (до записи) — если у корня
  ещё нет ни одного слепка, снять baseline прямо сейчас (иначе первый дифф не с чем
  сравнить); `created/updated/deleted` (после записи) — пометить корень «грязным» на
  этот запрос (при смене родителя у части — грязными становятся и старый, и новый корень:
  старый ищется по `getOriginal()`). В конце запроса — `EntityLogService::flush()`: по
  каждому грязному корню новый слепок, дифф с последним слепком той же `model_id`,
  запись `entity_logs` + `entity_log_changes`; пустой дифф — ничего не пишем. Flush
  вызывается из middleware `FlushEntityLog` (первым в группах `web` и `api` в
  `app/Http/Kernel.php`, чтобы сработать после контроллера) и страховочно из
  `app()->terminating()` (консоль, ошибки). Любая ошибка журнала гасится `report()` —
  сайт от журнала падать не должен.
- Массовые `update` без событий оборачиваются явно: `EntityLogService::around($root, fn)`
  (baseline при отсутствии + пометка грязным) — в `ProposalStatusService::set`,
  `ProposalDealService::syncMain/setMain/detach`, `SpecProposalService::win/detach`.
  Query-builder-удаления детей в логируемых репозиториях меняются на Eloquent
  (`->get()->each->delete()` / `->each->delete()`), чтобы `deleting` давал baseline.
- **Сопоставление детей в диффе** — `logKey(int $index)`: по умолчанию id; у
  пересоздаваемых коллекций — позиция `#n` в порядке связи (`orderBy('sort')` и т.п.):
  варианты КП — позиция (так сходятся и разные редакции), сценарии варианта —
  `scenario_id`, платформы/работы/ПО варианта, `proposal_works`/`proposal_software`,
  оплаты, сценарии спецификации — позиция; договоры, спецификации, ключи, hardware,
  extra_pays — id; `ProposalCrmDeal` — `crm_deal_id`; `PartnerCrmCompany` — `crm_company_id`;
  `ContractSpecificationProposal` — `proposal_group`. Совпали ключи — сравниваем поля
  (`changed`), нет в старом — `added` (одна строка с названием объекта), нет в новом —
  `removed`.
- **Новая редакция КП** — событие `created`, но дифф считается относительно последнего
  слепка предыдущей редакции той же группы (`iteration - 1`): видно, что изменилось в
  новой редакции. Новый корень без предшественника — `created` со строками `added` по
  непустым полям корня и по одной строке на каждого ребёнка первого уровня.
- **Настройка в модели** (все методы с дефолтами в trait): `logParent(): ?Model`,
  `static logChildren(): array` (`['variants' => ProposalVariant::class, …]` — имена
  hasMany-связей), `static logFields(): array` (`'name' => ['label' => 'Название']`,
  `'partner_id' => ['label' => 'Партнёр', 'relation' => 'partner']`, `'status' =>
  ['label' => 'Статус', 'enum' => ProposalStatus::class]`, `'sended_at' => ['label' =>
  'Дата', 'type' => 'date']`, `'cost_total' => ['label' => 'Итого', 'type' => 'money']`,
  `'is_signed' => [..., 'type' => 'bool']`, `'task' => [..., 'type' => 'html']`; тип
  неописанного поля берётся из casts), `static logIgnore(): array` (`created_at`,
  `updated_at`, FK на родителя, `id` — всегда; плюс технические: `report_data`,
  `neuro_costs`, `number_int`, `status_changed_*`, `crm_deal_linked_*`, `job_id` и т.п.),
  `logKey(int $index): string`, `logTitle(): string` (подпись объекта: «КП № 12 (ред. 2)»,
  «Вариант 2 (1 год)», «Нейросервис «Распознавание»», «Договор № 5», «Спецификация «X»»,
  «Оплата 01.09.2026 · 100 000»), `static logLabel(): string` (название типа),
  `logUrl(): ?string` (детальная страница, только у корня), `logGroupKey(): string`,
  `static logType(): string` (слаг).
- **Права.** Колонка `users.log_view` (bool, default 0), Gate `entity_log_view` =
  `is_admin || log_view` (`User::canViewEntityLog()`), в `AuthServiceProvider` рядом с
  `admin_panel`. В админ-панели: переключатель «Видит журнал изменений» в попапе
  пользователя и на карточке (как флаг темы). Логирование само по себе от прав не зависит.
- **Лента.** Маршрут `entity_log.index` = `GET /timeline/{type}/{key}` (`can:entity_log_view`),
  вьюха `themes/metronic/pub/entity_log/index.blade.php`: крошки «<тип> / <объект> /
  Журнал изменений», события по убыванию времени сгруппированы по дням (заголовок дня —
  ссылка на состояние на конец дня), у события — пользователь, действие, у КП — редакция,
  таблица изменений (объект → поле → было → стало) и ссылка «Открыть состояние на этот
  момент» (`?at=<id слепка>`). Фильтры в крошках по шаблону сайта: поле (select с
  optgroup по типу объекта, значение `model_type.field`), пользователь; кнопка «Фильтр» со
  счётчиком и «Убрать». Кнопка на детальных страницах — в `layouts/breadcrumbs.blade.php`
  генерически: `@if(!empty($log_root)) @can('entity_log_view')` → `btn btn-icon btn-light-primary`
  с `fa-light fa-timeline`; контроллеры трёх детальных страниц передают `'log_root' => $model`.
- **Состояние на момент.** `?at=` у `proposal.detail` / `partner.detail` / `company.detail`
  (число — id слепка, дата `Y-m-d` — последний слепок на конец дня; раньше первого слепка —
  показываем первый с пометкой). `EntityLogService::stateAt($root, $at)` гидрирует модель
  из JSON (`newInstance`, `setRawAttributes`, `exists = true`, дети — `setRelation`
  по `logChildren()` рекурсивно); belongsTo-связи (партнёр, компания, валюта, сценарий)
  подгружаются живыми по FK — это допустимо. Контроллеры подменяют модель и передают во
  view `'log_state' => [...]`; `layouts/layout.blade.php` перед крошками рисует баннер во
  всю ширину (дата/время, кто внёс, крестик = ссылка на страницу без `?at`); в режиме
  состояния действия редактирования на странице скрыты (КП: меню «⋮», статус без
  `editable`; партнёр/компания: кнопка «Редактировать»).
- Команда `php artisan entity-log:baseline` — baseline-слепки всех корней без слепков
  (на проде запускается один раз после миграций; идемпотентна).
- Старая тема: логирование работает (оно серверное), кнопка и баннер — только в Metronic.

**Этапы:** A — ядро: миграции, trait, модуль (модели, сервисы слепка/диффа/гидрации,
flush, команда baseline), подключение к моделям, правки репозиториев (агент);
B — интерфейс: лента, кнопка в крошках, `?at=` и баннер, галочка в админке, README (агент,
после A). Проверка: правка КП (кол-во лицензий), смена статуса, оплаты спецификации,
партнёр; просмотр состояния; вход под обычным пользователем — кнопки нет.

**Этап A — сделан (агент, 14.09.2026), отклонения от плана выше:**
- Миграции `2026_09_14_100001..100003` (`entity_logs`, `entity_log_changes`, `users.log_view`),
  `config/entity_log.php` (реестр корней), trait `app/Models/Traits/HasLogger.php` — каталог
  `app/Models/traits` переименован в `Traits` (`HasDetailPage` переехал, импорты в `Calendar`
  и `Organization` поправлены): на Linux-проде регистр важен, **после `git pull` нужен
  `composer dump-autoload`**.
- Слепок хранит все сырые атрибуты, `logIgnore()` применяется только в диффе (иначе гидрация
  теряет `id`/`group`/`iteration`). Родитель части задаётся `logParentRelation()` (имя
  belongsTo-связи), `logParent()` переопределён только у `ProposalCrmDeal` (по group).
  Корень — класс из `config/entity_log.php` (`isLogRoot()` без загрузки связей).
- Общие поля группы КП (`status`, `status_reason`, `status_comment`, `crm_deal_id`) при диффе
  берут старое значение из последнего слепка группы (`logSharedFields()`), чтобы правка старой
  редакции не показывала чужую смену статуса.
- `deleted` пишется только если у корня уже была запись; корень без слепка при flush получает
  `baseline`, `created` — для созданных в запросе и новых редакций КП (дифф с предыдущей).
- `ProposalDealService::detach` оставлен на query builder (обёрнут `around`); остальные
  удаления детей переведены на Eloquent. Деньги в подписях — с копейками.
- Фильтр по полю: `слаг_типа.поле` (`proposal_variant_scenario.count`); `EntityLogChange::field_key`.
- Проверено скриптами в транзакциях с откатом: лицензии 3→4 → «Вариант 2 (бессрочно) →
  Нейросервис «…» → Лицензии: 3 → 4»; статус → 3 строки; партнёр name/grade; оплата
  `amount_fact` с путём «Договор → Спецификация → Оплата»; `stateAt` по старому/новому слепку;
  новая редакция → `created` с диффом относительно ред. 1; переезд hardware между КП →
  `removed`/`added`; baseline 432/58/165, повторно 0.
- Flush идёт после сборки ответа: страница, отрендеренная в том же запросе, что и правка,
  новое событие ещё не видит (AJAX с reload — норма).

**Этап B — сделан (агенты B1 и B2, 14.09.2026; первый агент на весь этап трижды зависал,
не написав ни файла — задание пришлось дробить, см. память `agent-stalls-split-tasks`):**
- B1: `app/Modules/Pub/EntityLog/{Routes/web.php, Controllers/EntityLogController.php}`,
  вьюха `themes/metronic/pub/entity_log/index.blade.php` (события по дням, таймлайн Metronic,
  таблица «Объект / Поле / Было / Стало», added/removed бейджами, «показаны N из M», ссылки
  «Состояние на конец дня» `?at=Y-m-d` и «Открыть состояние на этот момент» `?at=<id>`,
  фильтр под кнопкой в крошках: поле с optgroup, пользователь, событие), кнопка `fa-timeline`
  в `layouts/breadcrumbs.blade.php` по `$log_root` + право, `'log_root'` в трёх `detail`.
- B2: `EntityLogViewService::state()` / `dateWords()` (ядро не менялось), `'log_state'` в трёх
  `detail`, баннер в `layouts/layout.blade.php` перед крошками (дата словами, событие, автор,
  «Журнал изменений», крестик на текущее состояние, note при `exact = false`); в режиме
  состояния скрыты меню «⋮» КП, смена статуса, «Прикрепить спецификацию», журнал заметок,
  кнопки «Редактировать» партнёра и компании. Админка: `log_view` в попапе и на карточке
  пользователя (переключатель + бейдж, у админа заблокирован), `POST /api/admin/users/log_view/{user}`.
- Проверки: `?at=<id>` / `?at=Y-m-d` / `?at=2000-01-01` (первый слепок с note) / `?at=abc` (404) /
  чужой слепок (404); обычный пользователь без флага — без кнопки и баннера, `/timeline` → 403,
  с флагом — всё видно; лента партнёра и КП проверены в браузере.
- Грабли проверки: без cookie `ui_theme=metronic` тема падает в materialpro, где вьюх журнала
  нет (500) — curl без cookie не показателен; `proposal/detail.blade.php` объявляет `cost_out()`
  в `@php`, второй рендер в одном процессе падает «Cannot redeclare».

## Итерация 9 · patch-v30 · Рабочий стол (запрос владельца 14.09.2026)

Запрос: домик в крошках и рабочий стол сейчас ведут на воронку продаж — нужна новая главная
страница «Рабочий стол»: пункт меню, переход с домика и сразу после входа; крошка «Рабочий
стол» только на самом столе. Стол — сетка виджетов и библиотека (аналогия — экран
смартфона): 32 столбца, любое число строк, ячейка квадратная, виджеты кратны ячейке, у
виджета несколько размерных рядов. По умолчанию просмотр (всё зафиксировано), режим
редактирования — перетаскивать, добавлять из библиотеки, удалять, менять размер. Столов
любое количество, стол сохраняется как пресет; админ создаёт системные пресеты для всех;
разные разрешения должны отрабатывать корректно. Библиотека по категориям: «Общая» плюс
по функциональным страницам; у виджета в библиотеке — превью. Примеры: блок с суммой
(показатель, шрифт, заливка, валюта), выбор валюты (влияет на весь стол), таблица «страны и
статусы помесячно на 6 месяцев», быстрая ссылка на модель с выбором поля-названия, сумма
оплат за N дней, истекающие ключи через N дней, напоминания, блокнот, баннер во всю ширину.

**Сделано 14.09.2026:** проект решения и каталог — артефакт
https://claude.ai/code/artifact/8ad76927-7dc1-4663-a899-dccc237e891e (файл
`scratchpad/osmo-desktop-widgets.html` сессии): что меняется в маршрутах/меню/крошках,
сетка и размерные ряды (XS 2×2 … XL 32×12), сторона ячейки на реальных экранах, контракт
виджета (`WidgetInterface`: sizes/settings/data/render/preview/access/ttl), хранение
(`desktops`, `desktop_widgets`, `desktop_users`), фронт (GridStack.js 10, `column: 32`,
`cellHeight: 'auto'`), режимы, библиотека, пресеты и версии системных пресетов, адаптив
(32 → 16 колонок масштабом ½ → лента на телефоне), права; каталог 80 виджетов в 9
категориях с размерами, настройками, источником и логикой; 5 стартовых пресетов; этапы
v30-A…D; 8 открытых вопросов владельцу (место пункта меню, только Metronic, порог 16
колонок, кто делает системные пресеты, GridStack, общие столы, состав первой партии из 14
виджетов, удаление старого `Pub/Dashboard`).

**Факты (разведка 14.09.2026):** `route('dashboard.index')` объявлен дважды — в
`Pub/Dashboard` (`/dashboard/{mode?}`, остаток прошлого проекта: режим подчинённых, даты в
сессии, `DashboardDataService` с `dd()`) и в `Bitrix/Dashboard` (`/bitrix/dashboard`);
побеждает Bitrix, поэтому домик (`themes/metronic/components/breadcrumb.blade.php`), вход
(`UserController::Auth`) и `RedirectIfAuthenticated` ведут на воронку. Крошку «Рабочий стол»
добавляет конструктор `Pub\Dashboard\DashboardController`. Меню: «Работа» (`parent_id = 10`,
в нём КП, Внешние КП, Реестр сделок, Воронка), «Отчёты» (15), «Справочники» (6),
«Настройки» (1). Данные для виджетов уже есть в сервисах: `Bitrix\Dashboard\DashboardDataService`
(sales/licenses/services/devcost/platform/servicesRaw, матрицы country_status_month/quarter,
manager_status_quarter, industry_name), `ProposalStatusService`, `PaymentCalendarService`,
`LicenseRenewalService`/`LicenseRegistryService`, `PartnerScoringService`/`PartnerStatsService`,
`DiscountAnalysisService`, `CrmMismatchService`, `DealProjectService`, `ExternalProposalService`,
`ReportService`, `CurrencyService`, модули Reminder/UserNote/Calendar/Notify, журнал v29.

**Решения владельца (14.09.2026) по открытым вопросам артефакта:**
1. Пункт «Рабочий стол» — первым в разделе «Работа».
2. Только Metronic: в старой теме домик и вход ведут на воронку, как сейчас.
3. Порог (на моё усмотрение): 32 колонки при ширине сетки > 960 px (окно ≈1280 px с сайдбаром,
   ячейка ≈30 px; сначала было 1200, но тогда уже окно 1600 px давало 16 колонок), 16 колонок
   масштабом ½ до 768 px, уже — лента в одну колонку; редактирование только на 32 колонках.
4. Системные пресеты — только админ (`User::isPanelAdmin()`).
5. Библиотека сетки (на моё усмотрение) с требованием владельца: **при перетаскивании
   полупрозрачная копия блока стоит в конечной позиции** — видно, где он окажется, если
   отпустить кнопку. Выбор — GridStack.js 11.1.2 (MIT) с jsDelivr, как ApexCharts в воронке:
   его placeholder и есть «место приземления», в него при dragstart/resizestart клонируется
   содержимое блока с прозрачностью; соседи раздвигаются в реальном времени.
6. Общих столов нет.
7. Первая партия (на моё усмотрение): 14 виджетов из раздела «Этапы» артефакта.
8. Старый `Pub/Dashboard` (на моё усмотрение) удаляется: маршруты, контроллеры, сервисы,
   `Models/Dashboard`, вьюхи `resources/views/pub/dashboard`, компоненты `dashboard/date-select`,
   `dashboard/sub-users`, `notify/date-select` (ссылаются на его маршруты, нигде не подключены).
   `Policies/DashboardPolicy.php` остаётся: на неё ссылается строка `accesses` id 7
   (`desktop_ann`), Gate объявляется из этой таблицы.

**Факты для реализации:**
- `Breadcrumb::__construct()` всегда кладёт первым пунктом «Рабочий стол» со ссылкой `/`, поэтому
  подпись видна на всех страницах. В Metronic-компоненте `themes/metronic/components/breadcrumb.blade.php`
  первый пункт пропускается (`->slice(1)`), страница стола добавляет свой пункт; `forTitle()`
  и старая тема не меняются.
- Домик и логотипы в Metronic: `components/breadcrumb.blade.php`, `layouts/layout.blade.php:12`,
  `layouts/sidebar.blade.php:13`, `layouts/header.blade.php:15`; после входа —
  `UserController::Auth` (строка 76), `RedirectIfAuthenticated`, `routes/web.php` маршрут `/`.
  Имя `dashboard.index` после удаления `Pub/Dashboard` остаётся только у воронки — старую тему
  (`layouts/sidebar.blade.php`, `UserNote::$detail_route`) не трогаем.
- API модулей Pub — `api.php` с `middleware => ['ajax.api']` (токен `_token` = `ajax_token`,
  в JS `csrf_token()`); попапы — `box({href})`, шаблон `components.box.box-static-large`.
- Воронка берёт валюту из глобального `Cache::get('dashboard_currency')` и фильтр страницы
  (`CrmDealRepository::getFiltered()` + `DashboardFilterService`) — виджетам воронки нужна
  явная валюта стола и выборка без фильтра страницы (опциональные параметры, поведение
  страницы воронки не меняется).

**Каркас — сделан мной (14.09.2026), контракт для агентов:**
- Миграции `2026_09_14_110000_create_desktops_table`, `110100_create_desktop_widgets_table`
  (прогнаны): `desktops` (user_id null = системный, name, is_system, is_default, sort,
  context json, source_id, source_version, version, created_by, updated_by),
  `desktop_widgets` (desktop_id, uid, widget, x, y, w, h, settings json; unique desktop_id+uid).
- `app/Modules/Pub/Desktop/Models/{Desktop, DesktopWidget}` (`canView`, `canEdit`,
  `contextObject`, `toGrid`), `config/desktop.php` (сетка, версия GridStack, 9 категорий).
- `Services/DesktopContext` — валюта и период стола: `currencyFor($settings)`,
  `periodFor($settings)` (from/to), `range()`, `previousRange()`, `symbol()`, `PERIODS`.
- `Services/WidgetRegistry` — сам находит `Widgets/{Категория}/*Widget.php`: `all()`, `find()`,
  `instance()`, `availableFor()`, `categories()`, `forUser()` (библиотека).
- `Widgets/Widget` — контракт: `id/name/category/sizes/defaultSize/description/icon/order`,
  `fields()` (схема: text, textarea, number, select, bool, currency, period, entity, list),
  `usesCurrency/usesPeriod` (общие настройки валюты и периода), `showTitle`, `available($user)`,
  `ttl`, `sourceUrl`, `previewSettings`, `data()`, `sample()`; `schema()`, `normalize()`,
  `allows()`, `nearestSize()`, `meta()`; отрисовка `html()` (оболочка) и `body()` (ошибка
  данных или вьюхи — плашка вместо тела), кэш данных без настроек оформления.
- Оболочка `themes/metronic/pub/desktop/partials/widget.blade.php`, стили
  `public/metronic/css/osmo-desktop.css` (`--desk-cell`, `--desk-font-scale`, заливки,
  `.desk-value`, `.desk-label`, `.desk-delta`, `.desk-list`, `.desk-table`, `.desk-empty`),
  образец `Widgets/Common/BannerWidget` + `widgets/banner.blade.php`.

**Сделано по этапам (14.09.2026):**
- A2: `Desktop/Services/DesktopService` (`home`, `forUser`, `systems`, `create`, `copy`, `rename`, `delete`,
  `makeDefault`, `save` — синхронизация раскладки целиком с проверкой uid/виджета/размера/позиции,
  `context`/`setContext` — выбор валюты и периода в сессии `desktop_context.{id}` поверх контекста
  стола, `render`, `gridItems`, `summary` с `update_available`, `assertView`/`assertEdit`),
  `Controllers/Api/ApiDesktopController`, `Routes/api.php` (8 POST `api.desktop.*`), `Desktop` первым
  в `config/modular.php`. API не проходит `ResolveUiTheme` — `render` сам включает Metronic.
- W1: heading, note, link (КП / партнёр / компания / сделка; карточка сделки — только при праве
  `deal_card_view` и привязанном КП, иначе ссылка в Битрикс24), reminders (как `reminder.index`).
- W2: currency, period, payments_fact (свой запрос `payments` + `contract_specifications` по датам
  факта, курс на дату оплаты; сверено с `PaymentCalendarService::rows()`), keys_expiring
  (`LicenseRenewalService::expiring()`; `renewalAmount()` — в валюте спецификации, пересчёт на сегодня).
- W3: `Desktop/Metrics/MetricRegistry` — показатели КП, партнёров, компаний, внешних КП, расхождений;
  kpi (заголовок по умолчанию скрыт), proposal_status, scoring_top («текущий» год = последний год
  с данными, как на странице скоринга). Я добавил показатели оплат, ключей и воронки.
- W4: funnel_table, country_month. Сервис воронки: `DashboardDataService::__construct(?string
  $currency = null, bool $filtered = true)`, `country_status_month(int $months = 6)` (при 12 месяцах
  сверяется и год), `CrmDealRepository::getFiltered(bool $apply_filter = true)`; регресс сумм и
  матрицы на 6 месяцах совпал, все прежние вызовы без аргументов.
- A3: `Desktop/Routes/web.php`, `Controllers/DesktopController` (старая тема → воронка),
  `themes/metronic/pub/desktop/index.blade.php`, `public/metronic/js/osmo-desktop.js` (объект `Desk`:
  `init/edit/save/cancel/compact/refresh/setContext/addWidget/removeWidget/openLibrary/openSettings/
  applySettings/copyDesktop`, ленивая загрузка через IntersectionObserver, `--desk-cell` =
  `cellWidth × column / 32`, копия блока в `grid.placeholder` на `dragstart`/`resizestart`, ресайз
  к ближайшему разрешённому размеру), `public/metronic/css/osmo-desktop-grid.css`.
- Моя проверка A3 в браузере (окно 1600 px) и правки:
  - **сетка была пустой**: `gridstack.min.css` 11.x содержит ширину/позицию блоков только для
    12 колонок — сгенерировал `public/metronic/css/osmo-desktop-columns.css` (32, 16 и 1 колонка);
  - порог 16 колонок снижен с 1200 до 960 px ширины сетки (`config/desktop.php`);
  - низкие блоки (высота 1–2 ячейки) плотнее (`osmo-desktop.css`), в «Выборе валюты» на ширине 4
    только «$ USD» без названия;
  - перетаскивание: заглушка с копией (прозрачность 0.45) стоит в целевой позиции, соседи
    сдвигаются сразу (баннер уехал с y=5 на y=7), после отпускания блок встаёт на место копии,
    `dirty` → «Сохранить» активна;
  - локальный сервер отвечает по одному запросу — 9 виджетов грузятся ~10 с; пакетная отрисовка
    `api.desktop.render_batch` — в A4c1.
- A4 (14.09.2026):
  - библиотека: `Controllers/DesktopLibraryController` (превью на `sample()`, ячейка превью 8–40 px),
    `pub/desktop/library.blade.php`, `osmo-desktop-library.js` (панель справа под тулбаром, вкладки,
    поиск, «Добавить», чипы размеров, `GridStack.setupDragIn` с `handle: '.desk-lib-film'`, копия
    превью в заглушку на `drag`), `osmo-desktop-library.css`; первый агент завис без файлов — сделал сам;
  - настройки: `Api/ApiDesktopSettingsController` (`form` — HTML попапа, отказ — плашка со статусом 200;
    `search` — select2), `boxes/settings.blade.php`, `osmo-desktop-settings.js` (двойной клик по заметке
    в просмотре — настройки и сразу `save()`);
  - столы: `DesktopBoxController` + `boxes/desktop.blade.php` (create / rename / copy / system),
    `osmo-desktop-desks.js`, `DesktopService::renderBatch/applySource`, `api.desktop.apply_source`,
    пакетная отрисовка в `osmo-desktop.js` (очередь 50 мс, пачки по 12): 9 виджетов 0,3 с вместо 1,8 с;
  - главная: крошки `slice(1)`, логотипы, `/`, вход, `RedirectIfAuthenticated` → `desktop.index`,
    `patch_v30_menu.sql` (пункт id 37 локально), `Pub/Dashboard` удалён; найдено: `NotifyController`
    зависел от `Pub\Dashboard\Services\DashboardService` — период истории уведомлений перенесён в
    `NotifyController::set_dates` (`notify.set_dates`), компонент `notify/date-select` оставлен.
- Правки по замечаниям владельца (проверка 14.09.2026):
  2. «При ресайзе виджет меняется по всей сетке, кажется, что размер любой» — ресайз GridStack
     выключен (`resizable: {handles: ''}` + `enableResize(false)`, в том числе после `setStatic`),
     размер ведёт своя ручка `.desk-resize` в углу блока: от курсора считается желаемый размер в
     ячейках, берётся ближайший разрешённый и ставится через `grid.update()` — блок прыгает по
     разрешённым размерам, соседи раздвигаются сразу. Над блоком — подсказка `.desk-size-hint`
     со всеми размерами виджета, текущий подсвечен. Почему не вмешиваться в ресайз GridStack:
     `grid.update()` во время его операции затирает `node._orig` → «Cannot read properties of
     undefined (reading 'w')» и ресайз не завершается; сужение `min/max` узла приводит к залипанию
     размера. Проверено: 4×2 → 8×2 (подсветка «8×2», сохранение включается), после отпускания
     размер зафиксирован и виджет перерисован под новый размер.
  1. «На узких разрешениях высота виджетов очень маленькая» — высота строки получила нижний
     предел `desktop.min_cell` = 46 px (`layoutCells` в `osmo-desktop.js`, ключ `min_cell`
     в `DesktopController`): пока колонка шире предела, ячейка квадратная, уже — строка выше
     колонки, ширина блоков не меняется. Проверено: окно 1500 px — 32 колонки, колонка 34 px,
     строка 46 px, блок 4×2 = 137×92 (было 137×68), по высоте не обрезается ни один блок;
     окно 1280 px — 16 колонок, строка 46 px вместо 26,8.
  3. «Хочу нажимать на размер в тулбаре и отжимать замок, чтобы размер был любым» — подсказка
     размеров стала панелью `.desk-size-panel`: открывается на ручке `.desk-resize` и висит, пока
     не щёлкнут мимо неё или не нажмут Esc (клик по блоку её закрывает — начинается перетаскивание).
     Размер ставится нажатием на него, текущий подсвечен. Замок: закрыт — только размеры виджета;
     отжат — любой размер (до 32 колонок и 64 ячеек в высоту), при закрытии замка размер
     возвращается к ближайшему разрешённому. Свободный размер хранится: колонка
     `desktop_widgets.free_size` (миграция `2026_09_14_120000_add_free_size_to_desktop_widgets`),
     `DesktopService::cleanItems` не приводит такой размер к списку и клампит его по сетке
     (`MAX_ROWS` = 64), `render`/`renderBatch` получают флаг и отдают виджет в его настоящем
     размере, `copy`/`applySource` переносят флаг. В JS: `Desk.state[uid].free_size`, границы узла
     снимаются (`applyLimits`), иначе `grid.update()` обрезал бы размер по `min/max` виджета;
     ручка ресайза теперь есть и у виджета с единственным размером — под ним отжимается замок.
     Проверено в браузере: нажатие на «4×4» меняет размер и подсветку; замок отжат — границы узла
     1..32 / 1..64, мышью получаются 5×2, 6×3, 7×3, 9×5; сервер отдаёт виджет как есть
     (`data-size="12x5"`, без плашки ошибки); замок закрыт — 6×3 вернулось к 4×2; сохранение через
     `DesktopService::save`: свободный 6×3 сохранён как есть, такой же блок с закрытым замком
     приведён к 4×2; перетаскивание и ресайз мышью — без ошибок в консоли.
     Там же по замечанию: в блоке высотой в одну ячейку кнопки «Настройки» и «Удалить» накрывали
     уголок ресайза — низкие блоки помечаются классом `desk-item-short` (`markShortItems`,
     по событиям сетки), инструменты в них уходят левее уголка и становятся 22 px.
**Партия виджетов (14.09.2026).** Владелец: «разработай недостающие виджеты и переделай
текущие с учётом того, что они могут быть любого размера». Порядок работы:
- каталог из артефакта перенесён в патч — `patches/patch-v30/WIDGETS.md` (80 виджетов:
  размеры, источник, логика, настройки), в начале файла — соглашение о вёрстке виджета;
  отметки «сделано» проставляет `tools/mark_widgets.php` по реестру;
- **любой размер**: `.desk-widget` стал CSS-контейнером (`container-type: size`), кегли —
  в контейнерных единицах (`cqw`/`cqh`), вьюха получает ступени `$dw`/`$dh` (xs…xl) и
  `$rows` (сколько строк влезет), второстепенное прячется классами `desk-only-w-md|lg|xl`,
  `desk-only-h-md|lg`, `desk-hide-narrow|short` (на `th`/`td` — колонка целиком).
  Сравнений `$w`/`$h` с числами в шаблонах больше нет; все 14 старых виджетов переписаны;
- **графики**: ApexCharts 3.54.1 на странице стола, `Widget::chart()`/`sparkline()` +
  `osmo-desktop-charts.js` (снимает старый график перед заменой HTML, пересчитывает по
  ResizeObserver). `MetricRegistry::series()` даёт ряд показателя по дням/неделям/месяцам/
  кварталам, `periodOptions()` — показатели, у которых ряд есть (сейчас 5 из 19);
- **проверка**: `tools/render_widgets.php` рисует виджеты в консоли во всех объявленных и в
  свободных размерах (2×2, 7×3, 16×5, 32×2), с живыми и образцовыми данными, и считает
  ошибки. После каждой партии прогон должен давать «с ошибкой: 0»;
- виджеты писались партиями агентами (по 3–4 виджета на агента, только свои файлы; общие —
  Widget.php, MetricRegistry, CSS, JS — правил сам), сам сделал: `rates`, `clock`,
  `countdown`, `chart`, `progress`, `compare`, `embed`, `button`.

**Итог: весь каталог из 80 виджетов сделан** (80 классов, 80 вьюх; Общие 16, Личное 7,
Воронка 14, КП 13, Партнёры 7, Оплаты 9, Ключи 5, Аналитика 5, Админ 4). Прогон
`render_widgets.php` — 1172 отрисовки, ошибок 0. В браузере проверены четыре тестовых стола
(12, 16, 18 и 26 блоков) в свободных размерах: переполнений нет, ошибок в консоли нет.
Ни одна вьюха не сравнивает `$w`/`$h` с числами и не задаёт кегль инлайном.

Правки общей вёрстки по ходу партии: график ApexCharts обрезается по блоку (рисовался выше
контейнера и включал прокрутку); цвет `.desk-bar > i` и `.desk-muted` заданы без веса селектора,
иначе `bg-gray-400` и `text-danger` не срабатывали; `.desk-center` и `.desk-stack` обрезают
содержимое сами; шапка `.desk-table` в `.desk-scroll` липкая; добавлены `.desk-split`,
`.desk-grid-7`, `.desk-bar-fill`, `.desk-total`. Самообновление виджета — атрибут
`data-desk-reload="секунды"` (tickReload), часы идут через `data-desk-clock` (tickClocks).
Константа `desktop_embed_domains` (белый список доменов для «Внешней страницы») —
`patches/patch-v30/database/sql/patch_v30_consts.sql`, локально выполнена.

**Дальше по рабочему столу (после правок 1–3, 14.09.2026):** владелец правит механику и состав
виджетов по ходу проверки — каждый пункт: правка → проверка в браузере → строка в этом списке.
Накопленные хвосты:
- в узком блоке (4 колонки) длинные подписи обрезаются по ширине — возможно, поднять минимальную
  ширину «Периода» и «Выбора валюты» до 6–8 колонок;
- меню у тем общее: в старой теме пункт «Рабочий стол» ведёт на воронку (решить, скрывать ли);
- системных пресетов нет — 5 стартовых из артефакта не собраны;
- каталог: сделано 14 виджетов из 80 (артефакт), остальные партиями;
- тема по умолчанию `materialpro`: без cookie стол уводит на воронку — решить (форсировать
  Metronic на `/desktop`, сделать Metronic темой по умолчанию или оставить).

- Открыто: меню у тем общее — в старой теме пункт «Рабочий стол» виден и уводит на воронку;
  системных пресетов пока нет (5 стартовых из артефакта не созданы); остальные 66 виджетов каталога.
- Маршруты и подключения этапа A4 объявил сам заранее, чтобы параллельные агенты не правили
  одни файлы: `desktop.library`, `desktop.box_desktop` (web), `api.desktop.render_batch`,
  `api.desktop.settings_form`, `api.desktop.search` (api), ключи в `Desk.urls`, файлы-заглушки
  `osmo-desktop-library.js/.css`, `osmo-desktop-settings.js`, `osmo-desktop-desks.js`.
- Замечено: данные — одно рублёвое КП в работе с основным вариантом 12,9 млрд ₽ (из-за него
  «КП в работе, сумма» = 15,2 млрд); новые файлы кто-то сам добавляет в индекс git (`A` без
  `git add` агентов — вероятно, IDE) — перед коммитом проверить `git status`.

**Этапы реализации (агенты, задания ≤ 5 файлов):** A2 — `DesktopService`, API и маршруты;
W1–W4 — виджеты партиями параллельно; A3 — страница стола (сетка, режимы, перетаскивание с
полупрозрачной копией, ресайз по разрешённым размерам, сохранение); A4 — библиотека с превью,
попап настроек, столы и пресеты, домик/вход/меню/крошки, удаление `Pub/Dashboard`, README.

**Этап A — сделан (агент, 14.09.2026), отличия от проекта выше:**
- Миграции `2026_09_14_100001..100003` (`entity_logs`, `entity_log_changes`, `users.log_view`)
  прогнаны; baseline снят локально: proposal 432, partner 58, company 165 (повтор — 0 новых).
  Реестр корней — `config/entity_log.php` (`types`), `isLogRoot()` = «класс в реестре».
- Каталог `app/Models/traits` переименован в `Traits` (иначе `App\Models\Traits\HasLogger`
  не загрузится на Linux-проде); `HasDetailPage` переведён в этот namespace, импорты в
  `Calendar` и `Organization` поправлены. **На проде после pull — `composer dump-autoload`.**
- Родитель части задаётся `logParentRelation()` (имя belongsTo), `logParent()` переопределён
  только у `ProposalCrmDeal` (родитель — последняя редакция группы). В `Proposal` добавлена
  связь `crm_deal_links()`.
- Слепок хранит все сырые атрибуты, `logIgnore()` действует только в диффе (иначе гидрация
  теряет `id`/`group`/`iteration`). Общие поля группы КП (`logSharedFields()`: статус,
  причина, комментарий, `crm_deal_id`) при диффе берут старое значение из последнего слепка
  группы — правка старой редакции не показывает чужую смену статуса.
- События: существующий корень без слепка при flush получает `baseline`, а не `created`;
  `deleted` пишется только если корень уже был в журнале; новая редакция КП — `created`
  с диффом относительно предыдущей редакции. Кэш `rootOf()` обходится при грязном FK
  родителя (переезд части в другой агрегат даёт `removed` у старого корня и `added` у нового).
- `ProposalDealService::detach` оставлен на query builder (обёрнут `around`), остальные
  удаления детей переведены на Eloquent. Формат фильтра по полю — `слаг.поле`
  (`proposal_variant_scenario.count`), при фильтре в `changes` события остаются только
  подходящие строки, `changes_count` — общее число.
- Проверки (в транзакциях с откатом): лицензии 3→4 — одна строка «Вариант 2 (бессрочно) →
  Нейросервис «…» · Лицензии · 3 → 4»; статус — три строки (статус, причина, комментарий);
  партнёр — имя и уровень (`PartnerGrade`); оплата — путь «Договор → Спецификация → Оплата»
  и «Сумма факт»; `stateAt` по старому/новому слепку отдаёт старое/новое значение; лента,
  фильтр и `fieldOptions` работают. Flush идёт после сборки ответа: страница, отрендеренная
  в том же запросе, что и правка, новое событие ещё не видит (AJAX с reload — норма).

## Партия правок владельца от 11.09.2026

Девять пунктов ушли в `patches/patch-v25`, скоринг — в `patches/patch-v26`.
Подробности и чек-листы — в README этих патчей. Коротко, что решено по ходу:

1. **Число у вкладки** показывает то, что вкладка покажет по клику, то есть с её
   умолчаниями, а не с текущим фильтром пользователя: вкладки открываются
   с чистым отбором и на фильтр не ссылаются.
2. **Отбор вкладки на карточке партнёра живёт в адресе страницы** (как на
   реестре), а не в сессии: переживает F5, ссылку можно отправить, у каждой
   вкладки свой.
3. **«Срок» у проекта перевёрнут**: показывается и сохраняется только у пилота,
   у обычного проекта обнуляется.
4. **Сумма спецификации в карточке проекта** считается по плановым платежам,
   как в таблице договоров на карточке партнёра, а не по полю
   `contract_specifications.amount` (оно обычно пустое).
5. **Многовариантные КП OSMOVIEW CP**: в ответе `detail` дополнительные варианты
   лежат под числовыми ключами верхнего уровня, основной развёрнут в корне.
   Такие КП в базе: AK904 и AK541.
6. **Партнёры без КП и спецификаций попали в рейтинг**, если у них есть сделки
   или проекты (иначе 35 весов из 100 были бы не видны). Стало 47 партнёров
   вместо 44.
7. **«Не работает выбор компании» при создании спецификации** — причина найдена
   и общая для всего портала: тема `bootstrap5` копирует классы `<select>` на
   видимое поле select2, и `class="select2"` ломал обработчик «клик мимо
   списка» — select2 переставал узнавать свой контейнер и закрывал только что
   открытый список (открытие и закрытие за 3 мс внутри одного `mousedown`).
   Программное `.select2('open')` при этом работает, поэтому беглой проверкой
   сбой не ловится: проверять только настоящим кликом. Починка — снятие лишнего
   класса в `public/metronic/js/osmo-metronic.js`.
8. **«Вариант» КП — это столбец** формы редактирования (кнопка «+», тип
   «Пилот / Годовая / Безлимит»), а не итерация. Перенос из OSMOVIEW CP создаёт
   одну итерацию с N столбцами (`proposal_variants`, `is_main` у основного).

## Правки владельца от 12.09.2026

1. **Отбор «Анализа скидок» уехал под кнопку «Фильтр»** в тулбар страницы, как
   на `/bitrix/dashboard`: карточки с полями над таблицей больше нет. Год
   остался на виду рядом с кнопкой — это главный отбор страницы, и он уезжает
   вместе со скрытыми полями остального фильтра, поэтому смена года отбор не
   сбрасывает. Сам фильтр по-прежнему обычная GET-форма (партнёр, статус КП,
   поиск, «только выделенные»), так что ссылку с отбором можно передать;
   «Убрать» снимает только поля модалки и оставляет год.
   Файл: `resources/views/themes/metronic/pub/analytics/discounts.blade.php`.
   На `/analytics/partners` сделано то же самое (грейд и поиск — в модалке,
   год — в тулбаре). `/analytics/licenses` пока со старой карточкой.

2. **У аналитики не было заголовка.** Конструктор `AnalyticsController`
   ставил всем действиям крошку «Анализ скидок», поэтому скоринг и реестр
   лицензий крошки не получали вовсе и страница шла без заголовка — с пустой
   левой половиной тулбара это стало видно. Крошка переехала в каждое
   действие по отдельности, `breadcrumbs` теперь отдаются всем трём.

3. **Платежи на карточке сделки** (`/deal-card/…`) показываются той же
   табличкой, что в ячейке «Оплаты» на карточке партнёра: строка на платёж,
   слева значок состояния и даты «план → факт», справа суммы. Разметка
   вынесена в общий компонент `components/payment/table.blade.php` — он
   принимает и модели `Payment` (карточка партнёра), и строки из БД
   (карточка сделки: там платежи собирает `DealChainService::payments()` и
   состояние у них считается заново по логике `Payment::getStatusAttribute`).
   Карточку партнёра не трогали, она по-прежнему со своей разметкой.

4. **Привязка КП к сделке стала один к одному.** В попапе
   `bitrix/deal/box/proposal.blade.php`: если к сделке уже привязано КП,
   кнопки «Привязать» нет ни у одной строки; если у КП уже есть сделка,
   вместо кнопки стоит «занято». Подсказка под поиском переписана —
   раньше там было сказано, что у КП сделок может быть несколько.
   **Осталось на сервере:** `ApiCrmDealController::proposalAttach` отказывает
   только в обратную сторону (сделка уже занята КП); проверки «у КП уже есть
   сделка» там нет, то есть запрет пока только в интерфейсе.

5. **Шкала цветов получила :hover.** В `public/css/palette.css` добавлено 126
   правил `.text-hover-<цвет>-<ступень>:hover` и `.bg-hover-…` (7 цветов ×
   9 ступеней × 2). **Внимание:** `palette.css` не заведён в git —
   `.gitignore` пускает из `public/css/` только `fix.css`, а шкалу подключает
   `layouts/layout_short.blade.php`. На прод после `git pull` файл не приедет.

## Правки владельца от 13.09.2026

1. **«Сумма» спецификации на карточке сделки** берётся из `amount_all` —
   сумма плана всех платежей, как в колонке «Сумма» на карточке партнёра.
   В `DealChainService::specifications()` добавлен подзапрос по `payments`,
   ячейка в `deal_card/index.blade.php` выводит его. У спецификаций с пустым
   `amount` вместо «0» теперь реальные суммы (проверено: 320 000 и 2 030 086).
   **Не тронуто:** блок «Деньги по сделке» и подсказка шага «Спецификации»
   по-прежнему считают по `amount` — ждёт решения владельца.

2. **Расхождение на карточке компании свёрнуто в метку.** В ячейке осталась
   только красная (или жёлтая) плашка «Расхождение», причины и суммы
   «платежи / КП» открываются поповером по клику и закрываются кликом мимо
   (`data-bs-trigger="focus"`, текст причин экранируется). Файл:
   `pub/company/detail.blade.php`. По ходу правку один раз затёр редактор
   с открытым файлом — накатана заново поверх ручных правок владельца
   (`col-3/col-9`, `min-w-200px`, `ms-7`), они сохранены.

3. **Тулбары таблиц мелькали до инициализации.** Всё, что bootstrap-table
   забирает в свою панель через `data-toolbar`, до загрузки скриптов стояло
   в потоке страницы — на КП шесть пар «Фильтр / Создать КП» столбиком.
   Общее правило в `public/metronic/css/osmo-fix.css`:
   `.bt-toolbar:not(.fixed-table-toolbar *) { display: none !important; }`,
   класс `bt-toolbar` проставлен во всех восьми местах: `proposal/index`
   (все вкладки), `company/index`, `partner/index`, `software/index`,
   `work/index`, `user/list`, `bitrix/deal/_filter`, `external_proposal/_filter`.
   Новым таблицам с `data-toolbar` класс нужно ставить так же.

4. **Реестр сделок до инициализации показывал всю простыню.** Строки
   рендерятся на сервере (страница ~1 МБ), страницы режет bootstrap-table.
   В `<style>` `bitrix/deal/_table.blade.php`: сырая `table.table_data` скрыта,
   пока её не обернули в `.bootstrap-table`. Действует и во вкладке
   «Сделки Битрикс» карточки партнёра.

5. **Календарь был закрыт чёрным затемнением.** `pub/calendar/index.blade.php`
   держал мёртвый `div.modal-backdrop.bckdrop.hide`: класс `hide` — из
   Bootstrap 3, в Metronic его нет, и затемнение висело поверх страницы
   всегда. Заменён на `d-none` (JS этот элемент не трогает).

6. **Обход страниц без скриптов** (как они выглядят до инициализации):
   КП, OSMOVIEW CP, реестр сделок, компании, партнёры, ПО, работы,
   пользователи, воронка, все отчёты, платёжный календарь, вся аналитика,
   расхождения с Битрикс24, синхронизация, нейросервисы, сценарии, меню,
   доступы, напоминания, уведомления, карточка партнёра — после правок
   артефактов нет.
   **Найдено попутно, не исправлено:**
   - `/report/china` падал 500 «Undefined variable $cur»: в
     `report/china.blade.php` короткие теги `<? … ?>`, а локальный PHP с
     `short_open_tag` выключен. **Исправлено:** `<?php` в теме Metronic
     (стр. 47, 69) и в корневом шаблоне старой темы (стр. 48, 70).

7. **Кнопка «Убрать» у фильтра** была свёрстана классами старой темы
   (`btn-icon btn-pure btn-outline`, опечатка `delete-row-btnКу`, пустая
   подсказка) и прилипала к «Фильтру». Во всех семи копиях — отчёты «Оплаты»,
   «Лицензионные ключи», «Сценарии по спецификациям», списки компаний,
   партнёров, ПО, работ — теперь `btn-light-danger` в размер соседней кнопки,
   подсказка «Сбросить фильтр», контейнер `#filter` получил `d-flex gap-2`.
   JS по-прежнему только переключает `d-none`.

8. **Блок позиции в «Истории цен» стал цветным** — как тип договора на
   карточке партнёра: `fw-bold text-<цвет>` с иконкой. Цвет и иконка лежат
   в `ProposalPriceHistoryService::blocks()`: платформа `primary`/`fa-desktop`,
   ПО `danger`/`fa-brain-circuit`, работы `warning`/`fa-person-digging`
   (как у `ContractType`), нейросервисы `info`/`fa-microchip` — своих в
   `ContractType` у них нет. Ячейка — `proposal_tools/price_history.blade.php`.

9. **Попап «Подробнее» OSMOVIEW CP: пары «ключ — значение» стали таблицами.**
   Три сетки `.kv` на вкладке «Ключевые поля» (две колонки полей и
   «Оборудование») переделаны на `table.table-bordered align-middle`:
   ключ — `text-muted` шириной 180 px, значение без изменений. Правила `.kv`
   из `<style>` попапа удалены. Файл: `external_proposal/boxes/detail.blade.php`.
   Также в этом разделе `#table_data tr.transferred:has(+ tr.transferred)` —
   красная граница у перенесённой строки, за которой идёт ещё одна
   (`external_proposal/index.blade.php`).

10. **«Выгрузить в Excel» в реестре сделок уехала под «Действия»** — как на
    `/external-proposals`: кнопка `btn-primary` с меню, пункт открывает
    попап выгрузки. Ссылка по-прежнему несёт текущий отбор и режим вкладки;
    в `onclick` она подставляется через `@js()`, чтобы «&» не экранировался
    дважды. Файл: `bitrix/deal/index.blade.php`, компонент `x-ui.a.box`
    там больше не используется.

11. **Глобальный hover у `.btn-light`**: чёрная заливка (`--bs-dark`) и белый
    текст, иконки и стрелка дропдауна тоже белые. Правило в
    `public/metronic/css/osmo-fix.css`, селектор как у Metronic
    (`.btn.btn-light:hover:not(.btn-active)`), цвета с `!important`.

12. **Пагинация таблиц в одну строку.** bootstrap-table 1.12 ставит блокам
    `pull-left`/`pull-right` из Bootstrap 3 — в Bootstrap 5 их нет, и «Записи
    с … из …» и номера страниц вставали друг под друга, страницы по центру.
    В `public/metronic/css/osmo-fix.css`: `.fixed-table-pagination` — flex со
    `space-between`, clearfix `::after` выключен, `div.pagination` прижат вправо.
    Действует на всех таблицах сайта.

13. **OSMOVIEW CP: «Фильтр» — к «Действиям», поиск — в шапку карточки.**
    Кнопки «Фильтр (n)» и «Убрать» вместе с подсчётом правил переехали из
    `_filter.blade.php` в `breadcrumb_right` страницы (секция выполняется
    раньше `content`, поэтому подсчёт живёт там же); в `_filter` осталась
    только модалка. Панель bootstrap-table (`.fixed-table-toolbar`) на странице
    скрыта, `data-toolbar` у таблицы убран. Поле `#external_search` стоит в
    `card-toolbar` рядом со счётчиком и через паузу 300 мс передаёт текст во
    встроенный поиск таблицы (`bootstrapTable('resetSearch', …)` — сам поиск
    по-прежнему клиентский).

14. **Кнопки фильтра на всём сайте — по образцу владельца с `/external-proposals`.**
    «Фильтр» — `btn btn-light-info` (в «Оплатах» с прежним размером `btn-sm h-35`,
    у ПО и работ сохранён их `d-none`). «Убрать» — не кнопка, а ссылка
    `me-2 text-dark-500 text-hover-dark` с иконкой `fa-light fa-xmark fs-5 me-2`,
    без подсказки. Где «Убрать» была `<button id="filter_clear">` и её
    переключает JS, тег сменён на `<a href="javascript:void(0);">` — id и
    `d-none` те же, обработчики работают. Файлы: списки компаний, партнёров,
    ПО, работ, КП (6 тулбаров), отчёты «Лицензионные ключи», «Оплаты»,
    «Сценарии по спецификациям», `bitrix/deal/_filter`, аналитика скидок и
    скоринга, дашборд Битрикса (там «Убрать» — компонент `x-ui.a.ajax`, который
    всегда даёт `.btn`, поэтому `btn_type="link"` + `p-0`).
    Ссылка «Убрать» — блочный элемент и в тулбаре без `align-items-center`
    растягивалась на высоту кнопки с текстом у верхнего края: контейнерам
    тулбаров (списки, КП, три отчёта, `bitrix/deal/_filter`) добавлен
    `align-items-center`.
    **Замечено, не исправлено:** на КП у шести ссылок «Убрать» один id
    `filter_clear`, jQuery вешает обработчик только на первую — так было и до
    правки.

15. **Карточки со списками — `card-body p-2`**, как владелец сделал на
    `/external-proposals`: КП, компании, партнёры, ПО, работы, реестр сделок
    (было `pt-2`, отступы по бокам и снизу от темы). `/user/list` — рудимент,
    по слову владельца не трогаем.
    **Найдено, не исправлено:** вызовы шаблонов с префиксом модуля (`pub::…`)
    идут мимо темы — `ResolveUiTheme` делает `prependLocation` только для имён
    без `::`, поэтому готовые копии в `themes/metronic` не используются у
    `access.create`, `access.edit`, `access_group.create`, `access_group.edit`,
    `menu.index`. Переключать на имена без `::` — после сверки копий.

16. **Фильтр — в тулбар страницы, поиск — в шапку списка, по всему сайту**
    (по образцу `/external-proposals`, пункт 13).
    - КП (`pub/proposal/index`): один набор «Фильтр / Убрать / Создать КП» в
      `breadcrumb_right` в обёртке `#filter` (без `bt-toolbar` — его глобальное
      правило прячет всё вне панели таблицы); копии тулбаров для вкладок
      менеджеров удалены. Одно поле `#proposal_search` справа в строке вкладок
      вызывает `resetSearch` у таблицы активной вкладки, на `shown.bs.tab`
      текст применяется к новой вкладке. Шапка `flex-nowrap`: при нехватке
      места переносятся вкладки, поле остаётся справа.
    - Компании, партнёры, ПО, работы: тулбар («Фильтр», «Убрать», «Добавить»)
      в `breadcrumb_right` с обёрткой `#filter`; у карточки появился
      `card-header` «Список компаний / партнёров / ПО / работ» с `#table_search`.
    - Реестр сделок: новый `bitrix/deal/_filter_buttons.blade.php` (кнопки и
      подсчёт правил) — в `breadcrumb_right` перед «Действиями»; `_filter`
      получил параметр `$toolbar` (по умолчанию `true`), `_table` выводит
      `data-toolbar` только при нём. Поле `#deal_search` в строке вкладок,
      поиск по-прежнему серверный: Enter → адрес с `q`, адрес строит новый
      `CrmDealRegistryService::searchBase()` (логика вынесена из `_table`).
      Вкладка «Сделки Битрикс» на карточке партнёра не менялась.
    - Отчёты «Лицензионные ключи», «Оплаты», «Сценарии по спецификациям»:
      «Фильтр» и «Убрать» в `breadcrumb_right` (поиска там нет).
    **В патч:** новый файл `_filter_buttons.blade.php` и сервис
    `CrmDealRegistryService.php`.

17. **Платёжный календарь: клик по сумме месяца не показывал платежи.**
    Разбивка по месяцам считается с отбором по умолчанию «спецификации в работе
    + уже оплаченные», а ссылки `$link` копировали в адрес `spec_status[]=processing`.
    Статус в адресе контроллер считает ручным выбором (`spec_status_strict`) и
    поблажку для оплаченных снимает — платёж по закрытой спецификации (январь
    2026, 332 000, спец. 97) в сумме был, а в списке «Найдено: 0». Теперь, пока
    выбор не ручной, `spec_status` не пишется ни в ссылки-детализации (`$link`,
    кроме ссылок, которые задают статус сами, — «Отменённые»), ни в ссылки
    снятия условий (`$unlink`), ни в скрытые поля формы выбора года.
    Файл: `payment_calendar/index.blade.php`.

18. **Платёжный календарь: итог списка не совпадал с планом месяца.** Разбивка
    по месяцам считает «План» по курсу на сегодня, а «ИТОГО по выборке» у
    оплаченных платежей берёт факт по курсу на дату оплаты. Пример: апрель 2026,
    «В процессе», два платежа 304 + 3 744 USD — в разбивке 339 008 ₽ (× 83,75),
    в итоге 288 301 ₽ (× 71,23). Потерь строк нет. Под итогом добавлена строка
    «план … · факт …» теми же величинами, что в разбивке: без отменённых; план —
    платежи с датой плана в выбранном периоде (год, месяц; «за все годы» — без
    ограничения) по курсу на сегодня, факт — с датой оплаты в периоде по курсу
    оплаты. Выборка месяца шире (дата плана ИЛИ оплаты в месяце), поэтому итог
    и «план» могут законно отличаться.
    **Решение владельца (13.09.2026):** «Факт» в разбивке остаётся по дате оплаты
    (движение денег по месяцам) — апрельский план, оплаченный в июне, даёт факт
    в июне, а у апреля «—». Колонка подписана «Факт (по дате оплаты)» с подсказкой.
    Там же: итог списка стоял под «Сводной» (подпись `colspan="8"` при 9 колонках) —
    теперь `colspan="7"`, сумма под «Итого, ₽». Строку «план · факт» под итогом
    владелец убрал вручную; расчёт `$rows_plan`/`$rows_fact` в шаблоне остался
    без вывода — ждёт решения. В колонке дат «отсрочка N дн» → «просрочка N дн»
    (`delay` — дни между планом и оплатой), колонка выровнена вправо, опечатка
    заголовка «ОПТАЛА» исправлена.

19. **Платёжный календарь: «Найти» на нетронутом фильтре сужало выборку.** Поле
    «Статус спец.» было заранее заполнено отбором по умолчанию («В процессе»),
    форма отправляла `spec_status[]=processing`, контроллер считал это ручным
    выбором — плашка «только спец.: В процессе» и минус оплаченные по закрытым
    спецификациям (2026: было 12, стало 9). Теперь при отборе по умолчанию поле
    пустое с подсказкой «В процессе + оплаченные», а плашка статуса в `chips()`
    рисуется только для ручного выбора. Заголовок блока — «Платежи за Август 2026» /
    «за 2026 год» / «за все годы».

20. **Платёжный календарь: фильтр платежей — в модалку.** Строка полей в шапке
    блока «Платежи» убрана: справа от заголовка «Фильтр (n)» (`btn-light-info`,
    n — число бейджей отбора) и «Убрать» ссылкой (адрес прежней «Сбросить всё»).
    Поля (поиск, компании, партнёры, состояние, статус спецификации) — в модалке
    `#calendar_filter_modal`, обычная GET-форма со скрытыми year/month/all_years/age.
    На месте формы — бейджи выбранного отбора (прежние плашки «Убрать это условие»).
    JS: модалка уводится в body, select2 получил `dropdownParent` модалки.

21. **Число условий на «Фильтре» — кружок вместо «(n)», по всему сайту.** Общий
    класс `.filter-count` в `osmo-fix.css` (18 px, при двух цифрах — пилюля, фон
    `--bs-info`). Разметка: КП, компании, партнёры, ПО, работы, три отчёта,
    `bitrix/deal/_filter_buttons`, OSMOVIEW CP, аналитика скидок и скоринга,
    дашборд Битрикса, календарь. JS ajax-фильтров (КП и списки) пишет в `.count`
    число без скобок. Календарь: в попап фильтра добавлены «Период» (месяц
    года + «за все годы») и «Срок просрочки» — всё, что даёт бейдж и входит в
    счётчик, теперь видно и меняется в попапе (кроме «одна спецификация» — она
    приходит только ссылкой). «За все годы» отменяет месяц: в попапе поле месяца
    прячется и отключается, а контроллер при `all_years` обнуляет `month` — бейдж
    и счётчик дают один фильтр «за все годы».

22. **Платёжный календарь: выбор года — в тулбар страницы** (`breadcrumb_right`),
    из шапки карточки «План и факт по месяцам». Форма перенесена как была: скрытые
    поля отбора едут вместе с годом, статус по умолчанию не передаётся.

23. **«Анализ скидок»: таблица КП компактнее.** Блоки скидок — мини-таблицей (иконка и два процента ровными колонками, мелко; столбец «Скидки» стоит перед «Совокупно», расшифровка «З / П» — в подсказке заголовка; ширины ячеек заданы, колонки совпадают во всех строках), у каждого
    цветная иконка из декоратора (`DiscountAnalysisService::BLOCKS` получил `color`/`icon`,
    те же, что в «Истории цен»; название блока и суммы — в подсказке). Колонки «Партнёр»
    больше нет: в первой ячейке под КП — «партнёр → компания» бейджами, как в списке КП
    (партнёр `info`, компания `primary`; без партнёра или компании — что есть, без стрелки),
    подпись грейда убрана. Название КП выводится полностью (снято `text-truncate` и
    `max-width: 240px`).
    Колонка «Пометки» заменена красным треугольником: по клику балун со списком пометок
    (`data-bs-trigger="focus"`, текст экранирован). Статус и причина («Отменено» +
    «Дорого») — столбиком: у `components/proposal/status` новый параметр `stacked`,
    остальные места компонента не меняются.

24. **«Скоринг партнёров»: легенда балла — в модалке.** Кнопка «Как считается балл и что
    значат буквы» над таблицей и раскрывающийся блок убраны. В тулбаре страницы
    (`breadcrumb_right`, перед годом) — крупная иконка «?», по клику модалка
    `#scoring_legend_modal` с теми же весами и буквами (содержимое перенесено как было).
    Таблица партнёров уходила за карточку (почти все колонки `text-nowrap`, минимум ~1240 px):
    обёртка `overflow: visible` заменена на `table-responsive`, подсказка балла у трёх
    последних строк открывается вверх, чтобы её не обрезал контейнер.
    Попап статистики партнёра: в заголовке только период («Статистика за 2026 год» / «за все
    годы»), подпись о периоде справа от имени убрана (вкладка «По годам» — вся история).
    Имя партнёра с грейдом — в шапке попапа справа от заголовка: у обёртки
    `themes/metronic/components/box/box-static-extralarge` новая необязательная секция
    `header_right` (другие попапы не затронуты). Во вкладке «По годам» подпись «на сегодня»
    у текущего года убрана (строка по-прежнему подсвечена), у колонки года отступ слева.
    Вкладка «КП»: номер крупнее, строка «редакция N» убрана — редакция надстрочно рядом
    с номером, только если она больше 1. Ссылки на компании во вкладках попапа
    подсвечиваются при наведении (`text-hover-primary`). Вкладка «Платежи»: строка
    просроченного платежа подсвечена (`bg-light-danger`). Вкладка «Сделки»: у названия
    убрана иконка-рукопожатие, в конце — значок открытия в новой вкладке (как в реестре), ссылка подсвечивается при наведении.

25. **«Реестр лицензий»: отбор — под кнопку «Фильтр» в тулбаре**, как на «Скоринге
    партнёров». Карточка с формой над показателями убрана. В `breadcrumb_right` —
    селектор горизонта (главный отбор, всегда на виду; остальные поля уходят с ним
    скрытыми), «Фильтр» с кружком и «Убрать». Модалка `#licenses_filter_modal`:
    партнёр (select2), поиск, свитч «только активные ключи». Свитч по умолчанию включён,
    поэтому в счётчик идёт его выключение; «Убрать» оставляет горизонт и возвращает
    «только активные». Контроллер не менялся.
    Таблица: колонки «Компания» и «Партнёр» сведены в одну «Партнёр → компания» —
    бейджами, как в списке КП и на «Анализе скидок» (без партнёра — только компания).
    Между подряд идущими подсвеченными строками (`bg-light-danger`) — разделитель
    цвета danger (`#licenses_table tr.bg-light-danger:has(+ tr.bg-light-danger) td`,
    по образцу `tr.transferred` на `/external-proposals`).
    В модалке новый свитч «скрыть истёкшие» (`hide_expired`, по умолчанию выключен,
    идёт в счётчик): горизонт включающий, и истёкшие попадают в любой — теперь их можно
    убрать. Отбор в `LicenseRegistryService::rows()` после горизонта, показатели
    считаются по отобранному. Подпись под таблицей («Горизонт включающий…») убрана.
    У типа договора в колонке «Договор и спецификация» — иконка из `ContractType::data()`
    (как в попапе статистики партнёра). Колонка «Период» разбита на две — «Начало» и
    «Окончание» (дата окончания жирная, без даты — «без срока»). Ключ — обычным текстом
    мельче (`fs-7`, с переносом), а не моноширинной плашкой `<code>`. «Сумма спецификации»: крупно и жирно всегда рубли
    со знаком ₽ (у валютных — по текущему курсу); у валютных под ней серым сумма в валюте
    с кодом, у рублёвых второй строки нет.

26. **«Расхождения с Битрикс24»: отбор — под кнопку «Фильтр» в тулбаре.** У страницы не было
    крошек и заголовка (контроллер не передавал `breadcrumbs`) — добавлены (трейт
    `HasBreadcrumb`, как у `AnalyticsController`), иначе тулбару некуда встать. В `breadcrumb_right` — «Фильтр» с кружком и «Убрать». Модалка
    `#crm_monitor_filter_modal`: статус КП, менеджер (select2), свитч «все КП». Поиск остался
    в шапке списка отдельной формой (по образцу п. 16), вид расхождения — карточками сверху;
    ни то, ни другое в счётчик не идёт и «Убрать» их не сбрасывает. Кнопки «Применить» и
    «Сбросить» из шапки списка убраны.
    Ширины колонок: фиксированные `width` (140/150/150/140/260) сняты — статус, суммы и
    «Что не так» сжимаются по содержимому (`width="1%"`, `text-nowrap`), у КП `min-w-250px`,
    у сделок `min-w-200px`; освободившееся место уходит названию КП.

27. **Меню (данные в БД, `patches/patch-v27/database/sql/patch_v27_menu.sql`).**
    «Расхождения с Битрикс24» перенесены из раздела «Bitrix» в «Настройки» (`parent_id = 1`,
    после «Доступов»). Звёзды в «Отчётах»: у «Китая» заливка убрана (`fa-light fa-star`),
    у «Скоринга партнёров» — залитая `fa-solid fa-star text-warning`. Запросы идемпотентные,
    ищут пункты по url. На проде после SQL — `php artisan cache:clear` (меню кэшируется).
    «КП OSMOVIEW CP» (`/external-proposals`) переименован во «Внешние КП» — в меню (тот же SQL)
    и в заголовке страницы (`ExternalProposalController`).
    В таблице «Внешних КП» убрана кнопка «Подробнее» (глаз) во всех строках
    (`components/external_proposal/table/actions`) — попап подробностей по-прежнему открывается
    кликом по названию КП. Крошки страницы: «КП → Внешние КП» (было «КП → OSMOVIEW CP»);
    заголовок попапа отдельного КП («OSMOVIEW CP N») не менялся — там речь об источнике.
    «Отчёты» разбиты на подгруппы горизонтальными разделителями: прочие отчёты →
    «Договоры и оплаты» + «Платёжный календарь» → «Ключи» + «Реестр лицензий» (тот же SQL:
    новые `sort` и два пункта-разделителя). Разделитель — пункт меню с именем `---` без url,
    `components/sidebar/menu-item` рисует его линией (`.separator`); в меню он виден через ту же
    связку `access_menu` (access_id = 1), что и остальные пункты.
    Раздел «Bitrix» расформирован (тот же SQL, раздел выключен `active = 0`, не удалён):
    «Воронка продаж» → «Работа», «Синхронизация» → «Настройки» как «Синхронизация Битрикс24»,
    «Расхождения с Битрикс24» → «Отчёты» (после «Скоринга партнёров»; перенос в «Настройки»
    выше этим перекрыт), «Реестр сделок Bitrix» → «Реестр сделок Битрикс24».

34. **«Воронка продаж»: пропадал график «Воронка в разрезе сферы деятельности».** ApexCharts
    подключался с CDN без версии (`cdn.jsdelivr.net/npm/apexcharts`) и приезжал 5.x — он
    конфликтует с SVG.js из бандла Metronic: при первой перерисовке (resize после загрузки)
    падало `e.put is not a function`, и диаграмма исчезала. Версия закреплена —
    `apexcharts@3.54.1` (`themes/metronic/bitrix/dashboard/index.blade.php`). Старые вьюхи вне
    темы (`graph`, `templates/*`, `bitrix/dashboard` в корне) не трогались — не используются.

35. **«Bitrix» / «Битрикс» → «Битрикс24» в интерфейсе.** Только видимый текст (заголовки,
    подписи, подсказки, тексты расхождений и ошибок), в любом падеже — несклоняемо
    «Битрикс24»: 20 замен в 15 файлах — `CrmDealController` (заголовок «Реестр сделок
    Битрикс24»), `PartnerScoringService` и `analytics/partners` («Кол-во сделок Битрикс24»),
    `CrmMismatchService` (4), `DealChainService`, `ProposalDealService` (2), `analytics/boxes/partner`,
    `deal_card/index` (3), `crm_monitor/index` (4, в т.ч. колонка «В Битрикс24»),
    `partner/detail` (2, вкладка «Сделки Битрикс24»), `bitrix/deal/_tab`, `pub/proposal/boxes/deal`,
    `pub/proposal_tools/boxes/clone`, `components/proposal/summary`,
    `components/proposal/table/main/summary`. Пункты меню — в `patch_v27_menu.sql` (п. 27).
    Не трогались: комментарии, пространства имён и маршруты `Bitrix`, миграции/SQL, описание и
    вывод консольной команды `DealProjectSeedCommand` (в интерфейс не попадают).

31. **Попап проекта: таблицы «Сделки» и «Спецификации» — `.table-bordered`**
    (`deal_project/boxes/info`), как таблицы в попапе внешнего КП.

32. **Реестр сделок Битрикса: не работала кнопка «Привязать КП к сделке»** (значок
    разорванной ссылки у сделки без КП). В `bitrix/deal/_table.blade.php` у атрибута `href`
    не было открывающей кавычки (`href=javascript:box(...)"`) — браузер обрезал адрес, и попап
    не открывался. Кавычка добавлена.

33. **«Анализ скидок»: в ячейке КП главное — название.** Раньше крупной ссылкой шёл номер, а
    название — серым под ним. Теперь ссылка — название КП (как в списке КП), под ним серым
    «№ номер · редакция N» (редакция — только если больше 1), ниже бейджи «партнёр → компания».

28. **Статусы КП — только «В работе», «Выиграно», «Проиграно».** «Заморожено» и «Отменено»
    убраны из `ProposalStatus` и стали причинами проигрыша: `ProposalLostReason::FROZEN` /
    `CANCELED` (коды те же, в списке причин — первыми). Данные:
    `patches/patch-v27/database/sql/patch_v27_proposal_status.sql` — `status = 'lost'`,
    `status_reason` = прежний статус, прежняя причина дописана в начало комментария
    («Прежняя причина: Дорого»). Локально было: отменено 11, заморожено 1.
    Код: `SpecProposalService::WIN_KEEP / WIN_FROM` без удалённых статусов (прикрепление к
    спецификации по-прежнему не трогает проигранные КП), `PartnerScoringService::proposals()` —
    завершённые `won` + `lost`. Попап смены статуса, фильтры списков КП, «Анализа скидок» и
    «Расхождений» берут статусы из enum — изменились сами.
    Следствие для аналитики: бывшие «Отменено» теперь проигрыши и входят в конверсию
    (раньше отменённые в конверсии не участвовали, замороженное считалось незавершённым).

29. **Карточка сделки: название сделки Битрикса — ссылка.** В таблице «Сделки Битрикс24»
    название ведёт в Битрикс24 (`CrmDealRegistryService::url()`, новая вкладка), в конце
    значок открытия в новой вкладке, при наведении подсвечивается (`text-hover-primary`).
    В колонке «Лицензии» таблицы спецификаций бейджи сроков («до 23.09.2033») — столбиком
    с отступом `gap-1` (стояли вплотную и сливались в одну плашку).
    Название спецификации больше не открывает попап редактирования: ссылка ведёт на карточку
    партнёра `…/partners/detail/{partner}#spec_{id}` (в `DealChainService::contracts()`
    добавлен `c.partner_id`; у договора без партнёра — просто текст). На карточке партнёра у
    строки спецификации `id="spec_{id}"`, по якорю строка обведена жирной фиолетовой рамкой
    (`tr:target`, 3px `--bs-info`) и прокручивается в видимую область с отступом под шапку.
    Суммы в табличке оплат спецификации больше не переносятся («142 500 ₽» ломалось на две
    строки): `text-nowrap` у `span.td` на карточке партнёра и в общем компоненте
    `components/payment/table` (карточка сделки).

30. **«История цен»: платформа — всегда первой.** Сравнение позиций сортировалось по коду блока
    по алфавиту (neuro, platform, soft, work) — «Нейросервисы» стояли выше «Платформы». Теперь
    `ProposalPriceHistoryService::diff()` сортирует по порядку `blocks()` (платформа,
    нейросервисы, ПО, работы), внутри блока — по названию позиции.

## Локальная база: следы проверок

Чтобы не принять их за боевые данные и не удивиться расхождению с продом:

- КП **AA788–AA793** (id 817–822) заведены при проверке переноса из
  OSMOVIEW CP 09–10.09.2026; владелец не сказал, удалять ли. Тестовые КП
  переносов 11.09.2026 (AA794, AA795) удалены вместе со всеми связанными
  записями, `external_proposals` возвращены в исходное состояние.
- **21 проект** в `deal_projects` создан командой `deal-project:seed`
  10.09.2026 при проверке v24; два из них лежат в архиве — им меняли
  состояние, когда проверяли кнопки «Отправить в архив» / «Вернуть из архива».
- КП **AA794** (группа `76deecc3-…`) — перенос AK541 от 11.09.2026 для проверки
  многовариантности: одна итерация, два столбца. Владелец не сказал, удалять ли.
- Два сопоставления в `partner_crm_companies` добавлены 10.09.2026 при
  проверке выбора партнёра в попапе проекта: компания Битрикса 639
  «ПроЭнерджи Digicity» → партнёр «ПроЭнерджи» и 303 «ГК «МТ-Интеграция»» →
  «МТ Интеграция». Пары верные — обе из списка «сопоставить руками» в
  README v23, — но на проде их нет.

## Решения владельца (подтверждены 2026-09-09)

1. **Веса скоринга** — ровно по ТЗ: спецификации 35, проекты 25, конверсия 25,
   сделки 10, просрочка 5. «Размер ожидаемых платежей» из балла выпадает,
   остаётся справочной колонкой.
2. **Автосоздание проектов** — только сделки с 2025 года (37 сделок в
   проектных статусах: Completed 30, Execution PRE 3, Execution POST 2,
   Acceptance tests 1, Closing documents 1, Invoice + Specification 0).
3. **Сущность «Проект»** делается модулем `DealProject` (таблицы
   `deal_projects*`): модуль `Project` уже занят «проектами компании» с
   конфигурациями для спецификаций. В интерфейсе — «Проект».
4. **Порядок** — начинаем с API Алексея, дальше по таблице.
5. «Кол-во сделок» в скоринге — все сделки партнёра за год по `date_create`,
   любых стадий; «Количество проектов» — по году `date_start`, архивные тоже.

## Что уже есть в проекте (факты для всех итераций)

**Зеркало Битрикса** — БД `avgbitrix` (соединение `bitrix`), таблицы
`crm_deal`, `crm_deal_uf`, `crm_company`, `crm_company_uf`. Заливается
дампом через модуль `Bitrix/Sync` и **перезаписывается целиком**, поэтому
колонки туда добавлять нельзя — все связи живут в `avgmom`.
Модели: `app/Modules/Bitrix/CrmDeal/Models/{CrmDeal,CrmDealUf,CrmDealIssues}.php`,
`app/Modules/Bitrix/CrmCompany/Models/{CrmCompany,CrmCompanyUf}.php`.

Поля сделки:
- `crm_deal.company_id` / `company_name` — **партнёр** (компания Битрикса);
- `crm_deal_uf.uf_crm_1717755645` — **конечный заказчик**, текстом (название);
  `CrmDeal::customer()` ищет `crm_company` по `title`;
- `crm_deal_uf.uf_crm_1722255711522` — плановый квартал: `2026q3` либо
  `Не выбрано`; `uf_crm_1736778153503` — месяц `09`;
- `crm_company_uf.uf_crm_1719404976291` — страна партнёра
  (`CrmDealRepository::UF_COUNTRY`), в дашборде это «Страна получения средств»;
- `assigned_by` = `[83] Имя Фамилия`, чистое имя — `CrmDeal::getManagerAttribute()`;
- `stage_name` (все): Lead, Research, Presentation, Pilot project,
  Competition/tender, TCP, Contracting, Invoice + Specification,
  Execution (PRE-PAYMENT), Execution (POST-PAYMENT), Acceptance tests,
  Closing documents, Completed, Suspended, Canceled; `stage_semantic_id` S/F/P;
- `opportunity` + `currency_id`, `date_create`, `begindate`, `closedate`;
- ссылка на сделку: `https://osmoview.bitrix24.ru/crm/deal/details/{id}/`.

Цифры (2026-09-09): сделок 2024 — 116, 2025 — 198, 2026 — 92; с 2025 года
с привязанным КП — 53 из 290; партнёров-компаний Битрикса — 81.

**КП ↔ сделка** — `proposal_crm_deals` (`proposal_group`, `crm_deal_id`,
`is_main`), главная дублируется в `proposals.crm_deal_id`. Сервис
`app/Modules/Pub/Proposal/Services/ProposalDealService.php`: `links()`,
`attach()`, `detach()`, `takenDealIds()`, `managers()`, `stages()`.
Обратный поиск «сделка → КП»: `ProposalCrmDeal::whereIn('crm_deal_id', ...)`
→ `proposal_group` → `Proposal::whereIn('group', ...)->latestIteration()`.
Попап привязки: `resources/views/pub/proposal/boxes/deal.blade.php`
(общий для тем), в нём `deal_done()` делает `location.reload()` — это и есть
причина п.6.

**КП портала** — `proposals` (group uuid, iteration, name, number, company_id,
partner_id, manager_id, currency_slug, sended_at, status, lang, nds…),
варианты `proposal_variants` (+ `proposal_variant_scenarios`, `_works`,
`_software`, `_platforms`, `_extra_pays`), работы/ПО/железо КП —
`proposal_works`, `proposal_software`, `hardware`. Создание —
`ProposalRepository::create(Request)`, модель `Proposal` (`latestIteration()`,
`getRouteKey()` = group), карточка `pub/proposal/detail.blade.php`.

**Партнёры и компании портала** — `partners` (id, name, grade, type, region…),
`companies` (partner_id, country_id, sector_id). Сопоставления с Битриксом
нет. По точному совпадению названия сходятся 37 из 58 партнёров и 90 из
165 компаний-заказчиков. В кросс-базовых запросах нужен
`COLLATE utf8mb4_unicode_ci` (у `avgbitrix` коллация `utf8mb4_0900_ai_ci`).

**Спецификации** — `contract_specifications` (contract_id → `contracts.partner_id`,
company_id, name, date_create, amount, is_signed, status, currency_slug).
КП ↔ спецификация: `contract_specification_proposals` (`proposal_group`).

**Скоринг** — `app/Modules/Pub/Analytics/Services/PartnerScoringService.php`
(константы `WEIGHT_*`, `metrics()`, `partner()`, `components()`, источники
`proposals()/specifications()/payments()/links()`), попап —
`PartnerStatsService.php` + `resources/views/themes/metronic/pub/analytics/boxes/partner.blade.php`,
страница — `.../pub/analytics/partners.blade.php` (легенда весов ~стр. 120–125,
подпись в футере ~стр. 387–393, колонки таблицы).

**Меню и права.** Таблица `menus` (id, active, parent_id, protected, name,
url, icon, sort) — **не** `menu` с `code`, как в SQL патча v15 (тот SQL не
применялся, пункты вносили руками). Разделы: «КП» — `parent_id = 10`
(в нём `/proposals`), «Справочники» — 6 (`/partners`, `/companies`),
«Отчёты» — 15, «Bitrix» — 21 (`/bitrix/dashboard`, `/crm-monitor`,
`/bitrix/sync`). Права: `accesses` (9 строк, `code`/`class`/`method`), связи
`access_menu`, `access_user`; проверка `auth()->user()->can_do('code')`.
Перед добавлением нового пункта посмотреть, как гейтится `deal_card_view`
(класс + метод), и повторить.

**Грабли вёрстки (проверено на v22).** `x-ui.select.multiple` без атрибута
`id` берёт значением первый символ строки — списки передавать как
`[['id' => значение, 'name' => подпись], …]` с `id="id"`. В `<x-ui.a.box>`
ссылку передавать привязкой `:href="route(...)"`, а не интерполяцией
`href="{{ route(...) }}"` — иначе `&` экранируется дважды и параметры до
попапа не доезжают. Дерево меню кэшируется (`menu_tree_<uid>`), после SQL
с пунктом меню нужен `php artisan cache:clear`.

**Вёрстка.** Metronic-шаблоны в `resources/views/themes/metronic/...`,
общие компоненты — `resources/views/components/` (иконки `fas`). Таблицы —
bootstrap-table (`pub/proposal/index.blade.php`: серверная пагинация,
`.table_data`, `bootstrapTable('refresh')`), фильтры аналитики — GET-форма.
Табы — `nav nav-tabs nav-line-tabs` + `tab-pane` (образец
`pub/analytics/boxes/partner.blade.php`). Попапы — `box({href})`,
шаблон `@extends('components.box.box-static-large')`, секции `body`/`footer`.
Excel — PhpSpreadsheet, образец `ProposalTools/Services/ProposalExcelService::download()`
(временный файл, `ob_end_clean`, `deleteFileAfterSend`). HTTP-клиент —
Guzzle 7 (`guzzlehttp/guzzle` в composer), `Http`-фасад Laravel 9 доступен.

**Локально.** Сервер — preview `osmo-local` (http://localhost:8123, PHP 8.2.5),
автологин под Анной, БД `127.0.0.1:33306` (`avgmom`, `avgbitrix`),
клиент `W:\Soft\MySql 8.0\bin\mysql.exe`. Подробности — в памяти
`local-run-server`, `local-env-setup`.

## Порядок работы в каждой итерации

1. Прочитать этот файл, README предыдущего патча, `git log --oneline -5`.
2. Сделать правки точечно (правила в `CLAUDE.md`). Новые таблицы —
   миграциями в `database/migrations`, разовые правки данных — SQL в
   `patches/patch-vNN/database/sql/`.
3. Проверить локально в браузере (preview `osmo-local`), прогнать SQL на
   локальной базе.
4. Собрать `patches/patch-vNN/` (только новые и изменённые файлы + README:
   «Что сделано», «Файлы», «Руками», «Чек-лист проверки»).
5. Коммит в `master` (по согласованию с владельцем), строка в `github.md`,
   статус в этой таблице.
6. Выкатка: `ssh osmo`, `git pull`, `php artisan optimize:clear`,
   `composer dump-autoload`, `php artisan migrate`, SQL из патча руками.

---

## Итерация 1 · patch-v21 · API Алексея — OSMOVIEW CP (ТЗ п.2)

**API (документ `Downloads\Документация_API_для_получения_данных_OSMOVIEW_CP.docx`
+ проверка 2026-09-09).** Система Алексея — «OSMOVIEW CP Generator»
(приложение на Google AI Studio / Cloud Run, снимок формы —
`patches/patch-v21/samples/cp_form_text.txt`).
- базовый URL `https://ais-pre-c4wikvkthlyf6i4hcrukra-708753304536.europe-west2.run.app`;
- ключ — из документа Алексея, рабочий; хранится только в `.env`
  (`OSMOVIEW_CP_KEY`), в репозиторий не кладём — заголовок
  `x-api-key` либо `?api_key=`;
- `GET /api/cp/list` → `{"success":true,"count":27,"data":[{id, projectName,
  customerName, createdAt "dd.mm.yyyy", updatedAt ISO, totalCameras, userId}]}`;
  `id` — строковый doc-id (`RSjDwG5smowQ99eXuFpB`), он же аргумент detail;
- `GET /api/cp/detail/{id}` → `{"success":true,"data":{...}}`, где `data.id`
  — **другой** идентификатор, номер КП в его системе (`AK528`, `AK807`).
  Хранить оба: `external_id` (doc-id) и `external_number`;
- образцы ответов: `patches/patch-v21/samples/*.json` (список + 4 детали:
  RUB бессрочные, RUB годовые с ручными ценами, USD годовые, USD с
  `manualPrice` и дообучением).

**Блокер серверного доступа (проверено 09.09.2026).** С сервера оба метода
отвечают `302` на `/__cookie_check.html` — и с ключом в заголовке, и в
query, и с браузерным User-Agent. Дело не в сертификатах (проверено с
рабочим SSL) и не в нашем коде: приложение опубликовано за аутентификацией
Google AI Studio. Страница проверки отдаёт cookie `GAESA` заголовком, но
этого мало — её скрипт ставит ещё `__SECURE-aistudio_auth_flow_may_set_cookies`
и дальше нужен auth-token AI Studio, то есть браузерная сессия Google.
Если подставить тестовую cookie, которую браузеру ставит скрипт страницы
проверки, следующий редирект ведёт уже на
`https://aistudio.google.com/applet-auth-bridge?applet_id=…&return_url=…` —
то есть за нашим API стоит вход в аккаунт Google с доступом к апплету.
В браузере всё работает именно поэтому: цепочка `302 → cookie_check →
applet-auth-bridge → назад к API` проходит молча, сессия Google уже есть.
Наш `x-api-key` на этом этапе вообще не проверяется — он нужен уже внутри
приложения, куда запрос не доходит.

Вывод: доработками на нашей стороне это не лечится. Нужно, чтобы Алексей
отдал API без входа в Google: опубликовал апплет как доступный по ссылке
без авторизации либо задеплоил тот же код обычным сервисом Cloud Run с
`--allow-unauthenticated`. Наш клиент заработает без правок. Из встроенного браузера Claude Code всё читается. Клиент пишется
по документу (заголовок `x-api-key`), а пока Алексей не откроет прямой
доступ, в интерфейсе есть **импорт JSON** (вставить ответ `detail`, скопированный
из браузера) и artisan-команда импорта из файла. Просьба к Алексею: дать URL
без проверки cookie AI Studio (публичный Cloud Run) — тогда «Обновить из
API» заработает без правок.

**Поля detail и их смысл** (по форме генератора):
- `projectName`, `customerName` (заказчик), `clientName`, `recipientCompany`
  (адресат — обычно партнёр-интегратор: «МТ-Интеграция», «SinapSysTec»,
  «Киевская площадь»), `recipientName/Position`, `senderName/Position`;
- `createdAt` `dd.mm.yyyy`, `updatedAt` ISO, `language` (`en` → наш `lang`),
  `currency` (`USD`/`EUR`; нет поля → RUB), `exchangeRate`, `marketType`;
- `isVatIncluded` (РФ НДС 22 %), `taxMode` (`custom_charges`/`none`),
  `taxPresetId`, `additionalCharges[{id, description, ratePercent}]`;
- скидки: `clientDiscount` (на весь проект), `platformDiscount`, `neuroDiscount`,
  `workDiscount`, `partnerDiscount` (в процентах);
- платформа: `totalCameras` = число лицензий платформы, `platformManualPrice`
  (цена за лицензию, иначе прайс), тип лицензии — по `items[].isPerpetual`
  (в форме единый переключатель «Годовая / Бессрочная»);
- `items[]` — сценарии (нейросервисы): `scenarioId` (**его** справочник,
  с нашим `scenarios.id` совпадает лишь частично: 3, 4 — да; 25 → наш 28,
  39 → наш 42, 36 → наш 39, 31 → наш 34, 23 → наш 26, 14 → наш 98,
  28 → наш 115, 2 → наш 2/80/81; `"0"` — кастомный), `name`, `cameras`,
  `isPerpetual`, `manualPrice` (цена за камеру, иначе прайс),
  `customizationType` (`none`/`retrain`), `integrationType`, `comments`;
- `detailedWorks[]` — работы: `id` (`3.1`, `4.1`, `5.1`, `AI.1`…), `name`,
  `nameEn`, `hours`, `rate`, `cost` (= hours × rate), `discount`, `tasks[]`,
  `note`; `globalWorkRate` — ставка часа;
- `includeTraining`/`trainingPrice`, `includeWarranty`/`warrantyPrice`/
  `warrantyMonths`/`warrantyFirstYearIncluded`/`includeWarrantySecondYear`;
- `paymentPrepaymentPercent`/`paymentPostpaymentPercent`, `deliveryWeeks`;
- техника: `gpuModel`, `fps`, `analyticsPerCamera`, `serverCount`,
  `isHighAvailability`, `isSingleNodeDeployment`, `hardwareRequirements{}`,
  `cameraRequirements`, `cameraTypes[{type,count,specs}]`;
- тексты: `projectRawInput`, `projectDescription`, `projectGoals`,
  `introText`, `projectResult`.
Итоговых сумм в ответе **нет** — цены стандартных сценариев берутся из
прайса, поэтому при переносе считаем по нашему прайсу (`scenarios.cost_rules`
`{"1":{"u":…,"y":…},"11":…,"40":…}` — пороги по числу камер, `u` бессрочно,
`y` за год; платформа — `Constant::get('platform_cost_per_year')` той же
структуры), а `manualPrice`/`platformManualPrice` — как точечная цена.

**Как переносится КП** (`Mappers/OsmoviewCpMapper.php` → `ProposalRepository::create()`
через собранный `Request` в формате формы создания — поля `name, name_alt,
date, number, manager, company, partner, nds, lang, period[], period_active[],
period_value[], period_main, partner_*_discount[], platform[], platform_cell[],
platform_cost[], scenario[], cell[], cost[], work[], work_cell[]`, см.
`ProposalRequest` и `create.blade.php`; так расчёт совпадёт с ручным вводом):
- перенос идёт через **попап подтверждения**: партнёр (select2, предзаполнен
  по `recipientCompany` ≈ `partners.name`), компания (по `customerName` ≈
  `companies.name`, иначе предложить создать компанию у выбранного партнёра),
  менеджер (текущий пользователь), номер (по умолчанию наш следующий
  `AA…`, его `AK…` — в `name_alt`), дата = `createdAt`, валюта, НДС
  (`isVatIncluded` → 22, иначе 0), язык;
- вариант один: `isPerpetual` → `unlimited`, иначе `year` с `period_value = 1`;
  если у позиций разные `isPerpetual` — по большинству, с пометкой;
- платформа: одна позиция, `count = totalCameras`, цена
  `platformManualPrice` либо прайс, `discount = platformDiscount`;
- сценарии: строка на каждый `items[]`: наш `scenario_id` — из таблицы
  соответствий `external_scenario_map (source, external_scenario_id,
  external_name, scenario_id)`, иначе по совпадению названия, иначе
  пользователь выбирает в попапе (select2 по нашему справочнику, для `"0"` —
  служебный сценарий 97 «!!! Служебный_временно для КП!»); выбранное
  запоминается в `external_scenario_map`; `real_name` = его `name`,
  `count = cameras`, цена `manualPrice` или прайс по порогу, `discount =
  neuroDiscount`, `comment` = `comments` (+ «дообучение», если `retrain`);
- работы: строка на каждый `detailedWorks[]`: `extended` = `name` +
  список `tasks`, `notice` = `note`, `group` по префиксу `id` (`3.*` —
  «Прочие работы», `4.*` — «Поддержка платформы / обучение», `5.*` — гарантия,
  `AI.*` — «Дообучение нейросервисов»; см. существующие группы в
  `proposal_works.group`), `count = hours`, `cost = rate`, `discount` =
  `discount` позиции либо `workDiscount`; `trainingPrice` при `includeTraining`
  — если такой работы нет в `detailedWorks`; работы с `cost = 0` переносятся
  с нулевой ценой;
- `task` варианта / КП — `projectRawInput`; описание, цели, результат,
  техтребования — в `notice`/комментарий КП, чтобы ничего не потерять;
- `clientDiscount` > 0 — применить как скидку на платформу, сценарии и работы
  дополнительно к их скидкам? Нет: показать в попапе предупреждением и не
  применять (у нас нет общей скидки), пользователь решит вручную;
- после создания: `external_proposals.proposal_group`, `transferred_at/by`;
  повторный перенос той же записи запрещён, но «перенести ещё раз как новое
  КП» доступен явной кнопкой.

**Что строится** (модуль `app/Modules/Pub/ExternalProposal`, **вписать в
`config/modular.php` (Pub)**):
- `config/services.php` → `osmoview_cp` (`base_url`, `api_key`, `timeout`)
  из `OSMOVIEW_CP_URL` / `OSMOVIEW_CP_KEY` (`.env` локально и на проде —
  строка в README; в `.env.example` тоже);
- миграции: `external_proposals (id, source VARCHAR(32) DEFAULT 'osmoview_cp',
  external_id VARCHAR(64), external_number VARCHAR(32) NULL, name VARCHAR(255)
  NULL, customer VARCHAR(255) NULL, cameras INT NULL, created_at_remote DATE
  NULL, updated_at_remote DATETIME NULL, list_payload JSON NULL, payload JSON
  NULL, fetched_at NULL, proposal_group CHAR(36) NULL, transferred_at NULL,
  transferred_by NULL, timestamps, UNIQUE(source, external_id))`;
  `external_scenario_map (id, source, external_scenario_id VARCHAR(32),
  external_name VARCHAR(300) NULL, scenario_id NULL, updated_by NULL,
  timestamps, UNIQUE(source, external_scenario_id))`. В `proposals` колонок
  не добавляем; у `Proposal` — связь `external()` (hasOne по `group`);
- `Services/OsmoviewCpClient.php` — `Http`/Guzzle, `x-api-key`, `Accept:
  application/json`; 3xx/HTML → исключение с понятным текстом;
- `Services/ExternalProposalService.php` — `sync()` (список → upsert),
  `fetchDetail()`, `importJson(string|array)` (ручной ввод/файл: принимает
  и `{"success":true,"data":{...}}`, и голый `data`, и ответ списка),
  `transfer(ExternalProposal, array $choices)`;
- `Mappers/OsmoviewCpMapper.php` — `preview()` (что будет создано: партнёр,
  компания, сценарии с найденными соответствиями, работы, суммы по прайсу)
  и `request()` (сборка `Request` для `ProposalRepository::create`);
- artisan `external-proposal:import {file}` и `external-proposal:sync`;
- страница `/external-proposals` (`themes/metronic/pub/external_proposal/index.blade.php`;
  меню: раздел «КП», `parent_id = 10`, «КП OSMOVIEW CP», иконка
  `fa-light fa-cloud-arrow-down`): кнопки «Обновить из API» и «Импорт JSON»
  (попап с textarea/файлом), фильтр (поиск, перенесённые/нет), таблица
  bootstrap-table: номер (`AK…`), название, заказчик, камеры, дата,
  валюта/лицензии (из payload, если загружен), статус переноса (плашка →
  наше КП), действия «Подробнее» (попап: ключевые поля + сценарии + работы +
  сырой JSON) и «Перенести» (попап подтверждения → POST → toastr → обновление
  строки). Если API недоступен — тост с текстом ошибки, страница живёт;
- карточка КП `pub/proposal/detail.blade.php` и колонка в списке КП:
  плашка «OSMOVIEW CP AK528» (точечная вставка рядом с номером);
- патч `patches/patch-v21/` с README (файлы, SQL меню, `.env`, чек-лист,
  «что попросить у Алексея»), образцы уже в `samples/`.

---

## Итерация 2 · patch-v22 · Реестр сделок Bitrix (ТЗ п.1)

**Цель.** Страница `/bitrix/deal` «Реестр сделок Bitrix»: сделки с
2025 года, по умолчанию — без привязанного КП; фильтры, таблица, выгрузка
в Excel с выбором колонок.

**Где.** Существующий модуль `app/Modules/Bitrix/CrmDeal` (в
`config/modular.php` уже есть, префикс группы `/bitrix`, свой префикс
`deal`). Новые файлы:
- `Controllers/CrmDealController.php` — `index()` (страница),
  `export()` (POST, отдаёт xlsx по образцу `ProposalExcelController::download`);
- `Controllers/CrmDealBoxController.php` — добавить `export()` (попап выбора колонок);
- `Services/CrmDealRegistryService.php` — выборка: `crm_deal` ⋈ `crm_deal_uf`,
  `with('crm_company.companyUf')`, `date_create >= 2025-01-01`; к строкам
  подтягивается КП (`ProposalCrmDeal` → `Proposal::latestIteration()`) одним
  запросом; фильтр `['stage' => [], 'has_proposal' => 'no|yes|all',
  'manager' => [], 'country' => []]`, значения для селектов
  (`ProposalDealService::managers()/stages()`, страны как в
  `CrmDealRepository::getFilterOptions()`); параметр `?Partner $partner`
  закладывается сразу — итерация 3 использует его для вкладки;
- `Services/CrmDealExportService.php` — каталог колонок `COLUMNS`
  (`код => подпись`): все поля `crm_deal` с человеческими подписями
  (ID, Название, Стадия, Менеджер, Партнёр, Заказчик, Страна, Сумма,
  Валюта, Вероятность, Даты создания/начала/закрытия, Источник,
  Комментарий…) плюс известные UF-поля (квартал, месяц, стоимость лицензий
  `uf_crm_1718977752420`, стоимость услуг `uf_crm_1718977763677`,
  `uf_crm_1723814702122`, `uf_crm_1725019324602` — подписи взять из
  `DashboardDataService`/`CrmDealIssues`, где они расшифрованы) и колонка
  «КП» (номер, статус). Экспорт уважает текущий фильтр;
- `Routes/web.php` — `GET /bitrix/deal` → `crm-deal.index`,
  `POST /bitrix/deal/export` → `crm-deal.export`,
  `GET /bitrix/deal/box/export` → `crm-deal.box.export`.

**Вьюхи** (`resources/views/themes/metronic/bitrix/deal/`):
- `index.blade.php` — макет + `@include('bitrix.deal._filter')` +
  `@include('bitrix.deal._table')`;
- `_filter.blade.php` — GET-форма: статус (`x-ui.select.multiple`, select2),
  «Есть КП» (нет / есть / все, по умолчанию «нет»), менеджер, страна, кнопка
  «Сбросить»; принимает `$action` и `$partner`, чтобы в итерации 3 та же
  форма работала во вкладке;
- `_table.blade.php` — bootstrap-table на клиенте (строк ≤ 500: сортировка,
  поиск, пагинация в браузере), колонки: ID · Название (ссылка на
  Битрикс, `target=_blank`) · Статус (плашка по `stage_semantic_id`) ·
  Менеджер · Страна · Сумма (`tools()->cost_normalize()` + валюта) · КП
  (плашка с номером → `route('proposal.detail', [$proposal, $proposal->iteration])`,
  иначе «нет»). Оставить место под колонку «Проект» (итерация 4);
- `box/export.blade.php` — чекбоксы по `COLUMNS` (все отмечены, кнопки
  «все / ничего»), скрытые поля текущего фильтра, submit формы в новое окно.

**Меню/права.** SQL `patches/patch-v22/database/sql/patch_v22_menu.sql`:
`INSERT INTO menus (parent_id=21, name='Реестр сделок', url='/bitrix/deal',
icon='fa-light fa-table-list', sort=max+10, active=1)`; строка в `accesses`
и `access_menu` — по образцу `deal_card_view`, если проверка окажется
обязательной для показа пункта.

**Чек-лист.** Страница открывается из меню; по умолчанию только сделки
без КП с 2025 года; каждый фильтр работает и сохраняется в URL; название
ведёт в Битрикс; КП ведёт в карточку; выгрузка отдаёт xlsx с выбранными
колонками и текущим фильтром; файл открывается в Excel без ошибок.

---

## Итерация 3 · patch-v23 · Партнёры ↔ Битрикс, вкладки партнёра (ТЗ п.3, часть 1)

**Цель.** Сопоставить партнёров портала с компаниями Битрикса и показать
на карточке партнёра вкладку «Сделки Битрикс» с той же таблицей и фильтрами.

**Данные.** Миграция `create_partner_crm_companies_table`:
`partner_crm_companies (id, partner_id, crm_company_id UNIQUE, created_at)`.
Одна компания Битрикса — не более чем у одного партнёра; у партнёра может
быть несколько (например «Trafcoo (Taraf AI-Bader)» ↔ «Trafcoo», «Bifu» ↔
«Shanghai Bifu Testing Technology Co., Lt», «Techwizer» ↔ «TechWizer India»).
Модель `Partner/Models/PartnerCrmCompany.php`, связь `Partner::crm_companies()`,
хелпер `Partner::crmCompanyIds()`.

Разовое сопоставление `database/sql/patch_v23_partner_match.sql`: вставить
пары по `LOWER(TRIM(title)) = LOWER(TRIM(name))` (с `COLLATE
utf8mb4_unicode_ci`) — закроет 37 партнёров; остальные — руками через форму.

**Интерфейс.**
- Форма партнёра (`pub/partner/edit.blade.php`, `create.blade.php`,
  `ApiPartnerController::update/store`, `PartnerUpdateRequest`): поле
  «Компании в Битрикс24» — select2 multiple по `crm_company` (title + #id);
  компании, занятые другим партнёром, показывать с пометкой и не давать выбрать.
- Карточка партнёра, левая колонка: карточка «Битрикс24» — привязанные
  компании и число сделок с 2025 года; если привязок нет — подсказка
  «сопоставьте в редактировании».
- Правая колонка `pub/partner/detail.blade.php` → табы
  (`nav nav-tabs nav-line-tabs`): **Договоры** (существующая вёрстка
  переносится внутрь `tab-pane` без изменений), **Сделки Битрикс**.
  Активная вкладка — в `location.hash`.
- Вкладка грузится AJAX'ом с `GET /partners/deals/{partner}?mode=all`
  (`PartnerController::deals`, возвращает `_filter` + `_table` из итерации 2
  с `partner`-областью; фильтр внутри вкладки отправляется AJAX'ом в тот же
  контейнер, `mode` задел под итерацию 4: `all|projects|archive`).
- В `CrmDealRegistryService` область партнёра: `company_id IN crmCompanyIds()`.
  Для партнёра без сопоставления вкладка показывает пояснение вместо таблицы.

**Чек-лист.** SQL сопоставил ожидаемые 37; в форме партнёра выбор компаний
сохраняется; чужая компания недоступна; вкладки переключаются, «Договоры»
выглядят как раньше; «Сделки Битрикс» показывает только сделки партнёра,
фильтры работают без перезагрузки страницы; у партнёра без сопоставления —
пояснение.

---

## Итерация 4 · patch-v24 · Проекты по сделкам (ТЗ п.3, часть 2)

**Цель.** Сущность «Проект»: к сделке прикрепляется один проект, к проекту —
несколько сделок одного партнёра и одной компании. Вкладки «Проекты» и
«Архив проектов». Разовое автосоздание.

**Модуль** `app/Modules/Pub/DealProject` — **вписать в `config/modular.php`
(секция Pub)**. Миграции:
- `deal_projects (id, partner_id, company_id NULL, date_start DATE,
  is_pilot BOOL, deadline DATE NULL, comment TEXT NULL, archived_at NULL,
  archived_by NULL, created_by, timestamps)`;
- `deal_project_deals (id, deal_project_id, crm_deal_id UNIQUE, attached_at,
  attached_by)` — уникальность даёт «у сделки один проект»;
- `deal_project_specifications (id, deal_project_id, contract_specification_id,
  from_proposal BOOL, UNIQUE pair)`.

Модели `DealProject` (partner, company, deals, specifications, scopes
`active()/archived()`), `DealProjectDeal`, `DealProjectSpecification`.
Сервис `DealProjectService`:
- `forDeals(iterable $dealIds)` — карта `deal_id → DealProject`, статический
  кэш на запрос (таблица маленькая, тянуть один раз) — им пользуются
  реестр, вкладки и значок из итерации 5;
- `resolveCompany(CrmDeal)` — компания сделки: из прикреплённого КП
  (`proposals.company_id`), иначе `companies.name` = заказчик из
  `uf_crm_1717755645`, иначе `null`;
- `lockedSpecs(DealProject)` — спецификации, прикреплённые к КП сделок проекта
  (`contract_specification_proposals` по `proposal_group`); они отмечены и
  `readonly`;
- `availableSpecs(Partner, ?Company)` — все спецификации партнёра
  (`contracts.partner_id`) по выбранной компании;
- `createForDeal()`, `attachDeal()` (проверка: тот же партнёр и та же
  компания), `detachDeal()`, `update()`, `archive()/unarchive()`.

**Попапы** (`resources/views/themes/metronic/pub/deal_project/boxes/`):
- `form.blade.php` — создание для сделки / редактирование: переключатель
  «новый проект» / «прикрепить к существующему» (список активных проектов
  партнёра с той же компанией); поля: дата начала, чекбокс «Пилот»,
  компания (компании партнёра, предзаполнена `resolveCompany`), список
  спецификаций с чекбоксами (из КП — отмечены и disabled, подпись
  «из КП №…»), «Срок» (показывается, когда «Пилот» снят), комментарий.
  Кнопки: «Сохранить»; у существующего — «Отправить в архив» /
  «Вернуть из архива», «Открепить сделку»;
- `info.blade.php` — карточка проекта: партнёр, компания, даты, пилот,
  сделки (ссылки в Битрикс и на КП), спецификации, комментарий, кнопка
  архива. Её же открывает значок из итерации 5.

API (`Routes/api.php`, middleware `ajax.api`): `store`, `update`, `attach`,
`detach`, `archive`, `unarchive`. Web (`Routes/web.php`): попапы
`box/form/{deal}`, `box/edit/{project}`, `box/info/{project}`.

**Реестр и вкладки.** В `_table` колонка «Проект»: плашка с датой начала и
«пилот» → `info`; у сделки без проекта — кнопка «проект» (создать или
прикрепить к существующему). Режимы `mode=projects` (сделки с активным
проектом) и `mode=archive` (проект в архиве) — вкладки «Проекты» и
«Архив проектов» **на странице реестра `/bitrix/deal`**, рядом с вкладкой
«Сделки» (уточнение владельца 10.09.2026: изначально в плане они стояли на
карточке партнёра — там их быть не должно, на карточке остаются «Договоры»
и «Сделки Битрикс» из v23). Вкладка живёт в адресе (`?mode=`), как фильтр.

**Автосоздание** — artisan-команда `deal-project:seed {--from=2025-01-01}
{--dry-run}` (посмотреть, как регистрируются команды: `app/Console/Kernel.php`).
Берёт сделки в статусах Invoice + Specification, Execution (POST-PAYMENT),
Execution (PRE-PAYMENT), Acceptance tests, Closing documents, Completed без
проекта; партнёр — по `partner_crm_companies` (нет сопоставления → пропуск с
выводом в отчёт), компания — `resolveCompany()`; `date_start` из
`uf_crm_1722255711522`: `2025q3` → первый день квартала (2025-07-01),
`Не выбрано` → `begindate`; спецификации из КП сразу помечаются
`from_proposal`. Запускается один раз на проде, отчёт о пропущенных
сделках — в README.

**Чек-лист.** Сделке можно создать проект и прикрепить к существующему
только своего партнёра и компании; спецификации из КП отмечены и не
снимаются, остальные ставятся руками; «Срок» появляется, когда «Пилот» снят;
архив/возврат работают из попапа; вкладки «Проекты»/«Архив» фильтруют верно;
команда в `--dry-run` показывает ожидаемые 37 сделок, боевой запуск создаёт
проекты, повторный ничего не дублирует.

---

## Итерация 5 · patch-v25 · Значок проекта везде (п.4) + КП без перезагрузки (п.6)

**Значок.** Общий компонент `resources/views/components/bitrix/deal_project.blade.php`
(`<x-bitrix.deal_project :deal_id="$id" />`): если у сделки есть проект —
иконка `fas fa-diagram-project` (в общих компонентах только `fas`), подсказка
с датой начала, клик → `box({href: route('deal_project.box.info', $project)})`.
Данные — `DealProjectService::forDeals()` (кэш на запрос, без N+1).
Подключить всюду, где выводятся сделки:
`components/proposal/table/main/deal.blade.php`, `components/proposal/deal.blade.php`,
`pub/deal_card/index.blade.php`, `pub/crm_monitor/index.blade.php`,
`pub/payment_calendar/index.blade.php`, `pub/proposal_tools/price_history.blade.php`,
`pub/company/detail.blade.php`, попап привязки `pub/proposal/boxes/deal.blade.php`
и `deal_rows.blade.php`, таблица реестра.

**Список КП (п.6).** В `pub/proposal/boxes/deal.blade.php` `deal_done()`:
вместо `location.reload()` — вызвать `window.deal_changed()`, если страница
его объявила, иначе перезагрузка как раньше. В `pub/proposal/index.blade.php`
объявить `deal_changed = () => $('.table_data').bootstrapTable('refresh')` —
при серверной пагинации `refresh` перерисовывает текущую страницу с текущей
сортировкой (проверить, что номер страницы не сбрасывается; если сбрасывается —
передать `{pageNumber: options.pageNumber}` из `getOptions`). Фильтры хранятся
в сессии, их трогать не нужно. В карточке КП и сводной карточке остаётся
перезагрузка.

**Чек-лист.** Значок виден на всех перечисленных страницах, попап
открывается; на второй странице списка КП после привязки/отвязки сделки
страница и сортировка сохраняются, колонка «Сделка» обновилась.

---

## Итерация 6 · patch-v26 · Скоринг партнёров (ТЗ п.5)

**Веса.** `PartnerScoringService`: `WEIGHT_SPECS = 35`, `WEIGHT_PROJECTS = 25`,
`WEIGHT_CONVERSION = 25`, `WEIGHT_DEALS = 10`, `WEIGHT_OVERDUE = 5`.
`WEIGHT_EXPECTED` и `WEIGHT_PROPOSALS` из балла убираются (решение 1),
`expected_sum` остаётся справочной колонкой.

**Источники.** `deals()` — `crm_deal` ⋈ `partner_crm_companies` (все стадии,
год по `date_create`); `projects()` — `deal_projects` (год по `date_start`).
В `partner()` добавить `'deals' => …`, `'projects' => …` (для года —
отбор по году, для «всей истории» — всё). В `components()` части:
`specs`, `projects`, `conversion`, `deals`, `overdue` — каждая относительно
лучшего в выборке, как сейчас. Обновить шапку класса (история v16/v19/v20)
и `totals()`.

**Вёрстка.** `pub/analytics/partners.blade.php`: легенда весов (массив
`$weights`), колонки таблицы («КП/год» → «Сделки» и «Проекты»), текст в
`card-footer`. Попап `pub/analytics/boxes/partner.blade.php`: вкладки
«Сделки» и «Проекты» (`PartnerStatsService::deals()/projects()`,
`AnalyticsController::box_partner`). В подсказке — «сделки считаются по
сопоставлению партнёра с Битрикс24»; партнёр без сопоставления получает 0.

**Чек-лист.** Сумма весов 100, у лидера 100; у партнёра с сопоставлением
число сделок совпадает с вкладкой «Сделки Битрикс» за год; проекты
совпадают с вкладкой «Проекты»; попап по цифрам открывает новые вкладки;
сглаживание по годам не сломалось.

## Итерация 10 · patch-v31 — правки журнала изменений (14.09.2026)

Владелец нашёл на КП три бага журнала (patch v29), README — `patches/patch-v31/README.md`:
1. «Поправил только Кол-во, а в ленте суммы на равных» — поля, которые портал пересчитывает сам,
   помечены `'derived' => true` в `logFields()`, строка изменения получает `derived`; в ленте
   основные жирным, косвенные под ними курсивом со сдвигом. «Цена» оставлена основной.
2. «Переключил основной вариант — 50+ записей» — `Proposal::variants()` сортирует `is_main desc`,
   варианты сопоставлялись по позиции. Теперь `EntityLogDiff::pair()`: сначала по id строки,
   потом по позиции (позиция нужна между редакциями КП). Ссылки на пересоздаваемые строки ПО и
   работ КП (`logLinks()`) сравниваются по подписи из слепков. 58 строк → 2.
3. «`?at=` падает после удаления варианта» — связи `proposal_work`/`proposal_software` при
   гидрации берутся из слепка (`logLinks()`, `EntityLogSnapshot::link()`); плюс баг гидрации:
   `array_map(fn)` захватывал индекс копией.
Старые события пересчитаны `php artisan entity-log:rediff` (11 событий, 5 пересчитано, 116 строк →
37, повторный прогон — 0). Проверено в браузере на трёх КП из замечаний и на новой записи в
транзакции с откатом. На проде: `migrate`, `entity-log:rediff --dry-run`, `entity-log:rediff`.

## Итерация 11 · patch-v32 — журнал у проектов по сделкам (14.09.2026)

Владелец: «добавить логирование проектам в сделках битрикс24». README — `patches/patch-v32/README.md`.
- Корень `deal_project` (`DealProject`), части — `DealProjectDeal` (ключ — id сделки) и
  `DealProjectSpecification` (ключ — id спецификации). Поля проекта: партнёр, компания, даты,
  пилот, срок, комментарий, архив; технические `created_by`, `attached_*` — в игнор.
- Своей страницы у проекта нет: `logUrl()` — реестр сделок (`mode=projects|archive`, `q` = сделка),
  кнопка ленты — в попапе карточки проекта. Новый метод контракта `logStateView()`: у проекта
  `false`, лента не выводит ссылки «состояние на момент».
- Массовые `delete`/`update` в `DealProjectService` (открепление сделки, пересборка
  спецификаций) обёрнуты в `EntityLogService::around()` — без событий модели журнал их не видел.
- Проверено в транзакции с откатом на проекте #1: правка — 3 строки, архив — 2, открепление — 1.
  Локально выполнен `entity-log:baseline` для проектов.

## Итерация 12 · patch-v30 — правка виджетов по сетке размеров (14.09.2026)

Владелец: «Перенеси панель с библиотекой вниз и сделай по высоте минимум 30%. Отдели виджеты друг
от друга более явно. Каждый виджет прогони по сетке в 2 шага… 16x32, оцени в каком размере какую
информацию можно уместить и скорректируй поведение элементов виджета — поручи это задание
агентам. …запиши правило, что в будущем при изменениях нужно прогонять виджет по всей сетке».
- Библиотека — панель снизу во всю ширину области контента, высота от 30 % окна, тянется за
  верхний край (запоминается в браузере), карточки сеткой; у стола снизу отступ на её высоту.
- Блоки: отступ GridStack 6 → 8 px (зазор 16), рамка `gray-300`, заметная тень.
- Общий подгон `osmo-desktop-fit.js` (`.desk-fit`, `$rows_max`, `Widget::rowsMax()`): строки с
  запасом, лишние прячутся по реальной высоте блока.
- Стенд `patches/patch-v30/tools/widget_grid.php` + `widget_grid.js`: 128 размеров, безголовый Edge
  (старый `--headless --dump-dom`), матрица кодов X/C/H/F/G/T/e/s/f. Правило записано в
  `WIDGETS.md` («Прогон по сетке») и `CLAUDE.md`.
- 11 агентов по группам (common-1/2, personal, funnel-1/2, proposal-1/2, partner, finance,
  keys-admin, analytics): стили виджетов — `public/metronic/css/osmo-desktop-widgets/{группа}.css`,
  итоги — `patches/patch-v30/grid/{группа}.md`. После агентов: свести стили в один файл, обновить
  README v30.
- **Пауза по просьбе владельца (лимит).** Готовы 66 виджетов в 9 группах; из Common (16) готовы 5,
  частично 5, не начаты 6. Что осталось и общие предложения агентов — `patches/patch-v30/grid/TODO.md`.
  README v30 уже описывает нижнюю панель, подгон и стенд.

## Выкатка v21–v32 на прод — подготовка (15.09.2026)

Владелец: «Готовимся к переносу всех патчей и изменений на продакшн. Важно, на продакшене
работали, там есть новые данные». План — `patches/deploy-v21-v32/README.md`.
- Прод на `b0fb1af`, код на сервере не правили; 16 миграций и 9 SQL-файлов не выполнены.
- База прода проверена только чтением: дублей `consts` нет, разделы меню и права на месте,
  новых пользователей нет, 12 КП frozen/canceled → lost, `avgbitrix` доступна (37 партнёров
  сопоставятся), новых данных после 09.09 — 5 КП и 1 компания; миграции и SQL их не трогают.
- Инструменты: `deploy-v21-v32/backup_db.php` (копия базы до pull, скрипт в stdin по SSH),
  `run_sql.php` (`--dry-run` = выполнить и откатить; прогнан локально по всем 9 файлам без ошибок).
- Владелец: «делай всё». Сделано: коммит `d713cfd` (v27–v32 + deploy) запушен; резервная копия
  базы прода `/root/backup/avgmom-2026-09-15_00-18.sql.gz` (70 таблиц, 16 862 строки; mysqldump
  не пускал — копия через PDO портала) и её экземпляр в `W:\Backup\MySQL\avg.mom\2026-09-15`.
- **Выкачено 15.09.2026** (после смены режима разрешений): `down` → `git pull` (прод на `d713cfd`,
  дублей регистра нет) → `dump-autoload` → 16 миграций → ключи OSMOVIEW CP в `.env` → SQL v21–v30
  (dry-run совпал: 37 связей партнёров, 12 КП → lost, 4 пользователя без админа, 25 констант) →
  кэши → `deal-project:seed` (21 проект, 16 сделок без сопоставления партнёра) →
  `entity-log:baseline` (654 слепка) → `up`. Проверка через ядро от `www-root`: 15 страниц — 200 у
  админа и обычного пользователя, админ-панель обычному — 403, журнал КП — 200.
- Осталось руками: сопоставить партнёров Телеком Мастер, ГК «МТ-Интеграция», Trafcoo, Shanghai Bifu
  (и остальных из README v23), затем повторить `deal-project:seed`; галочки «Видит журнал изменений».
