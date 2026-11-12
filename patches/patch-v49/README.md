# patch v49 — правки владельца 12.11.2026 (5 пунктов)

Ставится поверх v48. Сценарии при создании спецификации, сумма спецификации и ложные «Расхождения»,
вкладки «Проекты» одной строкой на проект, продление лицензионного ключа по новой спецификации.

## Что поменялось

1. **Сценарии сохраняются при создании спецификации.** Раньше `ContractSpecificationRepository::create()` их
   игнорировал, записывал только `update()`. Запись вынесена в `syncScenarios()` и вызывается из обоих мест.
   В `store()` добавлена валидация `scenario` / `scenario_manual`. Сценарию из справочника теперь пишется `sort`.
   На проде без сценариев остались спецификации 112 «С.26_Владивосток (26/27)» и 116 «С.2 ОДК ГТ (пилот)»: их
   нужно добить руками через «Редактировать».
2. **Сумма спецификации = поле «Сумма», а если оно пустое — сумма плановых платежей.** Поле в формах отключено
   давно, у новых спецификаций оно NULL. Новый аксессор `ContractSpecification::amount_total` (и `amountTotal()`,
   `amountTotalSql()` для сырых запросов) используется везде, где раньше получался 0:
   - карточка компании (колонка суммы, символ валюты берётся у спецификации, а не всегда «₽»);
   - сводная по сделке, календарь платежей, скоринг и окно партнёра, реестр лицензий, оценка продления;
   - виджеты `proposal_card`, `specs_status`, `contracts_unsigned`, `companies_geo`;
   - попапы проекта.
3. **Сверка (`SpecReconcileService`):**
   - пустая сумма больше не считается расхождением («Сумма не заполнена, но есть платежи» убрано);
   - «платежи ≠ сумма» и «график пуст» проверяются только при явно заполненной сумме;
   - КП сравнивается с суммой **всех** неотменённых спецификаций, к которым оно прикреплено (связная группа).
     Пример: КП 799 341 ₽ ОДК ГТ = лицензии 140 541 + услуги 658 800 — теперь ок;
   - если группа не сходится, но сама спецификация со своими КП сходится, она в порядке. Так бывает, когда КП
     продления прикреплено ещё и к спецификации прошлого периода: ошибка показывается только у старой;
   - в подсказке при общем КП показывается «спецификации (N) = …».

   Итог на данных прода: расхождений было 38 из 79, стало 17.
4. **Вкладки «Проекты» и «Архив проектов»** (реестр `/bitrix/deal` и карточка партнёра): одна строка на проект,
   его сделки внутри (ID, название, стадия, сумма, попапы КП и проекта у каждой). Сумма проекта = сумма сделок по
   валютам, КП — без повторов, менеджеры/партнёры/страны — без повторов. Счётчики вкладок считают проекты.
   Фильтры и поиск применяются к сделкам: проект виден, если прошла хотя бы одна его сделка. Вкладка «Сделки
   Битрикс24», выгрузка Excel и виджеты реестра остаются посделочными.
   **Попап «Проект по сделке»**: если у компании сделки уже есть действующий проект, по умолчанию выбрано
   «Прикрепить к существующему» с этим проектом. Раньше по умолчанию стоял «Новый проект», так 21.10 появился
   дубль: проекты 23 и 24 (МТ-Интеграция, компания 968).
5. **Продление ключа.** Новая таблица `license_key_periods`: у ключа несколько периодов «спецификация + с + по».
   В попапе ключа вместо одной пары дат — список периодов и кнопка «Продлить / добавить спецификацию» (новый
   период начинается со дня после последнего «по», длится год). Поля `license_keys.contract_specification_id /
   active_from / active_to` всегда равны последнему периоду (`LicenseKey::syncFromPeriods()`), поэтому виджеты,
   отчёты и реестр лицензий работают без правок и видят продлённый срок. В строке спецификации на карточке компании
   ключ показывается с датами периода этой спецификации; у старой — «продлён до …» вместо красного срока; в блоке
   ключей — «продлён, N периода». Периоды пишутся в журнал изменений.

## Файлы

- `database/migrations/2026_11_12_100000_create_license_key_periods_table.php` — таблица периодов, перенос 99 ключей.
- `app/Modules/Pub/ContractSpecification/` — `Models/ContractSpecification.php` (amount_total, license_keys через
  периоды), `Repository/ContractSpecificationRepository.php`, `Services/SpecReconcileService.php`,
  `Controllers/Api/ApiContractSpecificationController.php`.
- `app/Modules/Pub/LicenseKey/` — `Models/LicenseKey.php`, `Models/LicenseKeyPeriod.php` (новый),
  `Repository/LicenseKeyRepository.php`, `Controllers/LicenseKeyController.php`, `Controllers/Api/ApiLicenseKeyController.php`,
  `Services/LicenseRenewalService.php`; `resources/views/pub/license_key/boxes/edit.blade.php`, `_period_row.blade.php` (новый).
- `app/Modules/Bitrix/CrmDeal/Services/CrmDealRegistryService.php` (projectRows, counts);
  `resources/views/themes/metronic/bitrix/deal/_table.blade.php`, `_table_projects.blade.php` (новый), `index.blade.php`.
- `resources/views/themes/metronic/pub/deal_project/boxes/form.blade.php`, `company_form.blade.php`;
  `app/Modules/Pub/DealProject/Services/DealProjectService.php`.
- `resources/views/themes/metronic/pub/company/detail.blade.php`.
- `app/Modules/Pub/DealCard/Services/DealChainService.php`, `app/Modules/Pub/PaymentCalendar/Services/PaymentCalendarService.php`,
  `app/Modules/Pub/Analytics/Services/{PartnerScoringService,LicenseRegistryService}.php`,
  `app/Modules/Pub/Desktop/Widgets/{Proposal/ProposalCardWidget,Finance/SpecsStatusWidget,Finance/ContractsUnsignedWidget,Partner/CompaniesGeoWidget}.php`.
- `app/Modules/Pub/EntityLog/Services/EntityLogDiff.php`: если в старом снимке нет дочерней связи, она не
  показывается целиком как «добавлено».

Новых модулей нет, `config/modular.php` не менялся. Старая тема (`resources/views/pub/company/detail.blade.php`) не правилась.

## Что сделать руками при выкатке

1. Полная копия БД: `php patches/deploy-v21-v32/backup_db.php /root/backup`.
2. `git pull`, `runuser -u www-root -- php artisan migrate --force`, `runuser -u www-root -- php artisan view:clear`,
   `php artisan queue:restart`.
3. Проверить, что в `storage` и `bootstrap/cache` нет файлов root; число строк `license_key_periods` = число ключей.

## Решения владельца по данным (12.11.2026)

- Проекты 23 и 24 (МТ-Интеграция) — дубль, **объединены на проде 12.11**: сделки 369 и 457 перенесены в 23
  (через `DealProjectService::detachDeal/attachDeal`, с журналом), проект 24 удалён; копия БД avgmom-2026-11-12_12-17.
  У партнёра 839 теперь «Проекты (2)».
- КП продления, прикреплённое и к старым спецификациям (С.14_Березина + С.23, С.12_Павел Батов + С.13,
  С.13_Владивосток + С.22), — **не откреплять**, расхождение у старых остаётся.

## Чек-лист

- [ ] Новая спецификация со сценарием: сценарий виден сразу после создания.
- [ ] `/partners/detail/21`, `/companies/detail/990`: у «С.2 ОДК ГТ (пилот)» нет «Расхождения», сумма = платежи.
- [ ] `/companies/detail/37`: С.24–С.26 (26/27) с суммами 478 400 / 276 000 / 680 800 ₽, без «Расхождения».
- [ ] Вкладка «Проекты» у партнёра МТ-Интеграция: строка на проект, сделки внутри, счётчик = числу проектов.
- [ ] Попап «Проект» у сделки компании с действующим проектом: по умолчанию «Прикрепить к существующему».
- [ ] Ключ `…04c4c961…` (компания 37): «Продлить», выбрать С.24, 01.11.2026–31.10.2027, сохранить. В С.15 старый
      срок «продлён до 31.10.2027», в С.24 новый, в блоке ключей «продлён, 2 периода».
