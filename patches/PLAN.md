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

Параллельно ведутся только независимые итерации (v21, v22, v23-сопоставление):
v23-вкладки опираются на таблицу реестра из v22, v24 — на v22 и v23,
v25 — на v24, v26 — на v23 и v24, поэтому идут после.

После завершения итерации: обновить эту таблицу (патч, коммит, дата),
дописать строку в `github.md` (раздел «Патчи»), README патча положить в
`patches/patch-vNN/README.md`.

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
