# Патч v21 — API Алексея: КП OSMOVIEW CP и перенос в наши КП

Ставится поверх v1–v20. В архиве только новые и изменённые файлы (+ образцы
ответов API в `samples/`). ТЗ п.2, план — `patches/PLAN.md`, итерация 1.

## Что сделано

**1. Модуль `ExternalProposal` — КП из генератора Алексея**

Генератор OSMOVIEW CP (приложение на Google AI Studio / Cloud Run) отдаёт
список КП (`GET /api/cp/list`) и детальную запись (`GET /api/cp/detail/{id}`)
по ключу в заголовке `x-api-key`. Записи складываются в новую таблицу
`external_proposals`: `external_id` — doc-id из списка, `external_number` —
номер КП у Алексея (`AK528`), `payload` — полный ответ detail. В `proposals`
колонок не добавлено: связь с нашим КП живёт в `external_proposals.proposal_group`,
у `Proposal` появилась связь `external()`.

Клиент `OsmoviewCpClient` написан по документации. Сейчас с сервера оба
метода отвечают `302` на проверку cookie AI Studio — клиент превращает это
(и любой HTML вместо JSON) в понятную ошибку, страница показывает тост и
живёт дальше. Пока Алексей не откроет прямой доступ, данные заливаются
руками: попап «Импорт JSON» (вставить ответ `detail` или `list`, скопированный
из браузера, либо файл) и команда `php artisan external-proposal:import файл.json`.
Команда `php artisan external-proposal:sync` заработает без правок, как только
API станет доступен.

**2. Страница `/external-proposals` («КП OSMOVIEW CP», раздел «Работа»)**

Кнопки «Обновить из API» и «Импорт JSON», фильтр (поиск, перенесённые / нет),
таблица bootstrap-table: номер `AK…`, название, заказчик, камеры, дата,
валюта и тип лицензий (из payload), статус переноса (плашка → наше КП),
действия «Подробнее» (попап: ключевые поля, сценарии с найденными
соответствиями, работы, тексты, сырой JSON) и «Перенести».

**3. Перенос в наше КП**

Попап подтверждения: партнёр (select2, предзаполнен по `recipientCompany` ≈
`partners.name` без регистра, кавычек и форм собственности), компания (по
`customerName` ≈ `companies.name`, сначала среди компаний выбранного
партнёра; можно создать компанию у партнёра прямо из попапа), менеджер
(текущий пользователь), номер (наш следующий `инициалы + номер`, его `AK…`
уходит в `name_alt`), сопоставление сценариев (select2 по активному
справочнику) и оценка сумм по прайсу.

Соответствие сценариев: таблица `external_scenario_map` (засеяны известные
пары 3→3, 4→4, 25→28, 39→42, 36→39, 31→34, 23→26, 14→98, 28→115) → точное
совпадение названия → похожее (пересечение слов) → выбор пользователя; выбор
запоминается в карту. `scenarioId "0"` (кастомный) → служебный сценарий 97.

КП создаётся через `ProposalRepository::create()` из `Request`, собранного
в формате формы создания (`OsmoviewCpMapper::request()`, данные проверяются
правилами `ProposalRequest`) — расчёт совпадает с ручным вводом:
- один вариант: `isPerpetual` → `unlimited`, иначе `year` с `period_value = 1`
  (разные типы у позиций — по большинству, с предупреждением в попапе);
- платформа: `count = totalCameras`, цена `platformManualPrice` либо
  `platform_cost_per_year` по порогу, `discount = platformDiscount`;
- сценарии: `count = cameras`, цена `manualPrice` либо `scenarios.cost_rules`
  по порогу камер (`u` бессрочно, `y` за год), `discount = neuroDiscount`,
  `comment = comments` (+ «Дообучение нейросервиса» при `retrain`);
  название Алексея → `mnemonic_name`, если отличается от нашего;
- работы: `count = hours`, `cost = rate`; если сумма позиции не равна
  `hours × rate` (фиксированная цена, например обучение 50 000 за 4 часа) —
  переносится как `1 × cost` с пометкой в примечании; работы с нулевой ценой
  переносятся `1 × 0`; группа по префиксу id (`3.*` Разовые работы, `4.*`
  Поддержка платформы / обучение, `5.*` Гарантия и техподдержка, `AI.*`
  Дообучение нейросервисов; для `en` — английские группы); `includeTraining`
  добавляется отдельной работой, если среди работ нет `4.*`;
- НДС: `isVatIncluded` → ставка `nds_rate` (22) и флажок НДС у всех позиций,
  иначе 0; язык `en` → `lang = en`; дата = `createdAt`;
- валюта: `currency` из detail (нет — RUB). `create()` всегда ставит RUB,
  поэтому после создания у КП выставляются `currency_slug`, `currency_rate`
  и `currency_rate_cumulative = exchangeRate` (нет курса — последний из
  `currency_rates`); цены прайса делятся на курс, `manualPrice` идёт как есть;
- `partnerDiscount` → партнёрская скидка варианта на платформу и нейросервисы;
  `clientDiscount` не переносится (у нас нет общей скидки) — предупреждение;
- оборудование: `hardwareRequirements` + `serverCount` → строка «Сервер»
  (параметры списком, подписи «Процессор (CPU)», «Оперативная память (RAM)»…),
  `cameraTypes[]` → строка на каждый тип камеры (`specs` вида
  «Разрешение: 4 Мп, Частота кадров: 25 к/с» разбираются в список); если
  типов нет, берутся общие `cameraRequirements` на `totalCameras`. Пишется
  в блок «Вычислительные ресурсы и оборудование» каждого варианта (таблица
  `hardware`) после создания КП — `create()` его не заполняет;
- исходный запрос, описание, цели, результат, условия оплаты и сроки —
  в блок «Задачи» варианта и `proposals.task` (требования к железу и типы
  камер туда больше не дублируются — они в блоке «Оборудование»);
- валютные КП: генератор нередко оставляет в них рублёвые цены. Ставки
  работ пересчитываются по курсу, если они кратно выше `globalWorkRate`
  (AK361: USD, `globalWorkRate` 92, а в работах 7000), ручные цены
  (`manualPrice`, `platformManualPrice`) — если превышают 3000 в валюте
  (AK361: 57 600 за камеру). Каждый пересчёт выводится предупреждением
  в попапе переноса — менеджер должен сверить суммы.

Повторный перенос той же записи запрещён, но есть явная кнопка «Перенести
ещё раз как новое КП» — связь записи переключается на новое КП.

**4. Плашка «OSMOVIEW CP AK528»** в карточке КП (рядом с номером, клик
открывает попап «Подробнее») и в колонке номера списка КП (в выборку списка
добавлен `with('external')`, лишних запросов нет).

**5. Правки по замечаниям владельца (09.09.2026)**

- партнёра не нашли по адресату (`recipientCompany`), но нашли компанию —
  партнёр берётся у компании (`companies.partner_id`), в попапе метка
  «партнёр по компании-заказчику». Так закрывается AK807: адресата
  «Киевская площадь» среди партнёров нет, а компания есть — у АЛСОФТ-ЦЕНТР;
- поле «Создать компанию у партнёра» из попапа убрано вместе с методом
  `company()` и маршрутом `api.external_proposal.company`;
- сводка КП в попапе (название, дата, язык, валюта, НДС, вариант) оформлена
  таблицей вместо сетки.

**5.1. Правки после первого показа (09.09.2026, второй заход)**

- в списке `/external-proposals` убраны doc-id под номером и время изменения
  под датой; кнопка «Подробнее» (глаз) стоит последней, у перенесённой
  (зелёной) строки обе кнопки зелёные, а «Перенести» называется «Дублировать»
  с иконкой `fa-copy`;
- кнопка «Импорт JSON» убрана: импорт задуман строго по API. Попап и метод
  `api.external_proposal.import` оставлены в коде, чтобы вернуть кнопку одной
  строкой; пока доступ закрыт, файлы заливаются командой
  `php artisan external-proposal:import`;
- плашка «OSMOVIEW CP AK…» в списке и карточке КП заменена на иконку облачка
  справа от номера: номер КП Алексея и дата переноса — в `title`, клик
  открывает попап с данными внешнего КП;
- в колонке «Перенос» осталась только дата (имя пользователя — в `title`),
  колонки «Камеры» и «Перенос» выровнены по центру;
- `importJson()` понимает дамп из браузера вида
  `{"list": <ответ list>, "details": {doc-id: <ответ detail>}}`. Так данные
  забираются, пока API закрыт: открыть в браузере
  `<база>/api/cp/list?api_key=…`, в консоли собрать список и `detail` по
  каждому `id`, сохранить файл, залить
  `php artisan external-proposal:import дамп.json`. Вложения (`projectFiles`)
  и логотипы (`logoUrl`) в дамп можно не класть — при переносе они не нужны,
  а весят 900 КБ из 1,2 МБ.

**6. Попутный фикс `pub/proposal/edit.blade.php`** (не относится к API, но
всплыл на перенесённых КП): в блоке групп работ стояли короткие PHP-теги
`<? if (...): ?>`, а `short_open_tag` в PHP 8.2 выключен — условие не
выполнялось, `$work->group` читался у пустой строки работ, и вместо страницы
редактирования отдавался `dd()` из общего `catch` шаблона (страница без
стилей). Переписано на `@if(!empty($work?->group))`.

## Файлы

| Файл | Что изменилось |
|---|---|
| `app/Modules/Pub/ExternalProposal/Models/ExternalProposal.php` | модель, связи `proposal()`/`transferred_user()`, атрибуты валюты и типа лицензий |
| `app/Modules/Pub/ExternalProposal/Models/ExternalScenarioMap.php` | карта соответствий сценариев, `remember()` |
| `app/Modules/Pub/ExternalProposal/Services/OsmoviewCpClient.php` | клиент API: `list()`, `detail()`, понятные ошибки на 3xx/HTML/не-JSON |
| `app/Modules/Pub/ExternalProposal/Services/ExternalProposalService.php` | `sync()`, `fetchDetail()`, `importJson()`, `transfer()`, `rows()` |
| `app/Modules/Pub/ExternalProposal/Mappers/OsmoviewCpMapper.php` | `preview()`, `request()`, `task()`, `hardware()`, сопоставление партнёра (в т.ч. по компании) / компании / сценариев, цены по прайсу, пересчёт рублёвых цен в валютных КП |
| `app/Modules/Pub/ExternalProposal/Controllers/ExternalProposalController.php` | страница и попапы «Подробнее», «Импорт JSON», «Перенести» |
| `app/Modules/Pub/ExternalProposal/Controllers/Api/ApiExternalProposalController.php` | AJAX: таблица, sync, fetch, import, transfer |
| `app/Modules/Pub/ExternalProposal/Routes/web.php`, `Routes/api.php` | маршруты `external_proposal.*`, `api.external_proposal.*` |
| `app/Console/Commands/ExternalProposalImportCommand.php` | `external-proposal:import {file} {--id=}` |
| `app/Console/Commands/ExternalProposalSyncCommand.php` | `external-proposal:sync {--no-details}` |
| `database/migrations/2026_09_09_100000_create_external_proposals_table.php` | таблица `external_proposals` |
| `database/migrations/2026_09_09_100100_create_external_scenario_map_table.php` | таблица `external_scenario_map` + засев известных пар |
| `database/sql/patch_v21_menu.sql` | пункт меню «КП OSMOVIEW CP» (`parent_id = 10`) и его доступ `general_access` |
| `resources/views/themes/metronic/pub/external_proposal/index.blade.php` | страница со списком |
| `resources/views/themes/metronic/pub/external_proposal/boxes/detail.blade.php` | попап «Подробнее» |
| `resources/views/themes/metronic/pub/external_proposal/boxes/import.blade.php` | попап «Импорт JSON» |
| `resources/views/themes/metronic/pub/external_proposal/boxes/transfer.blade.php` | попап подтверждения переноса |
| `resources/views/themes/metronic/pub/proposal/detail.blade.php` | плашка «OSMOVIEW CP AK…» под номером КП |
| `resources/views/themes/metronic/pub/proposal/edit.blade.php` | короткие PHP-теги в группе работ → `@if` (страница редактирования падала в `dd()`) |
| `resources/views/components/proposal/table/main/number.blade.php` | плашка в колонке номера списка КП |
| `app/Modules/Pub/Proposal/Models/Proposal.php` | связь `external()` |
| `app/Modules/Pub/Proposal/Repositories/ProposalRepository.php` | `with('external')` в `getTable()` |
| `config/modular.php` | модуль `ExternalProposal` в секции Pub |
| `config/services.php` | секция `osmoview_cp` (`base_url`, `api_key`, `timeout`) |
| `.env.example` | `OSMOVIEW_CP_URL`, `OSMOVIEW_CP_KEY` |
| `samples/*.json`, `samples/cp_form_text.txt` | образцы ответов API и снимок формы генератора (в проект не копировать) |

## Руками

1. Скопировать файлы патча поверх проекта (`config/modular.php` уже
   с модулем `ExternalProposal`; если конфиг правился локально — дописать
   `'ExternalProposal'` в `modules.Pub`).
2. В `.env` добавить (ключ из документации Алексея):
   ```
   OSMOVIEW_CP_URL=https://ais-pre-c4wikvkthlyf6i4hcrukra-708753304536.europe-west2.run.app
   OSMOVIEW_CP_KEY=<ключ из документации Алексея, см. .env локальной копии>
   ```
3. `composer dump-autoload && php artisan optimize:clear`.
4. `php artisan migrate` — две таблицы, карта сценариев засеется сама.
5. Выполнить `database/sql/patch_v21_menu.sql`, затем ещё раз
   `php artisan optimize:clear` (дерево меню кэшируется по пользователю).
6. Пока API закрыт — залить образцы: `php artisan external-proposal:import
   patches/patch-v21/samples/list.json`, затем каждый `detail_*.json`
   (doc-id берётся из имени файла).

## Чек-лист проверки

- [ ] В меню «Работа» появился пункт «КП OSMOVIEW CP», страница открывается.
- [ ] «Обновить из API» показывает тост с текстом ошибки (302 / проверка
      cookie), страница не падает; после открытия доступа — список и detail
      подтягиваются.
- [ ] «Импорт JSON»: вставленный ответ `detail` создаёт/обновляет запись,
      в строке появились валюта, тип лицензий, число сценариев и работ.
- [ ] «Подробнее»: вкладки «Сценарии» и «Работы» показывают позиции и
      найденные соответствия, «JSON» — сырой ответ.
- [ ] «Перенести» AK528: партнёр «МТ Интеграция» и компания «ВДНХ» подставлены,
      8 сценариев сопоставлены по карте, 9-й (`Аналитика велодорожек`, id 2)
      выбирается руками; после переноса КП открывается: 9 сценариев с нужным
      числом камер, платформа 593 × 45 000 −30 %, 4 работы (400 и 900 ч по
      8 000, обучение 1 × 50 000, гарантия 1 × 0), НДС 22 %, суммы не нулевые.
- [ ] «Перенести» AK388: вариант годовой, валюта USD с курсом 86.59, сценарий
      39 → 42, НДС 0, язык en.
- [ ] В карточке созданного КП рядом с номером плашка «OSMOVIEW CP AK528»,
      в списке КП такая же плашка под номером.
- [ ] Повторный перенос той же записи даёт ошибку; кнопка «Перенести ещё раз
      как новое КП» создаёт новое КП.
- [ ] Выбранное в попапе соответствие сценария попало в `external_scenario_map`
      и при следующем переносе подставляется само.

## Что попросить у Алексея

Оба метода API с сервера отвечают `302` на `/__cookie_check.html` — обвязка
AI Studio пускает только браузер. Нужен **прямой URL сервиса Cloud Run без
проверки cookie AI Studio** (публичный ingress или отдельный хост для API) —
клиент уже написан по документации (`x-api-key`), после смены
`OSMOVIEW_CP_URL` в `.env` «Обновить из API» и `external-proposal:sync`
заработают без правок кода. Заодно уточнить: в ответе detail нет doc-id из
списка (только номер `AK…`) — если добавит его в detail, ручной импорт будет
привязываться к строке списка надёжнее.
