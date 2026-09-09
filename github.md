repo: webavgust/osmo
branch: master
path: resources/views, app/Http, app/Providers, config

secondary_repo: webavgust/metronic
secondary_branch: master
secondary_path: html/metronic_html_v8.2.1_demo48/demo48

## Патчи v12–v20
- v12: select2 в календаре (секция js вместо scripts), строгий фильтр статуса, значок расхождения факта и плана,
  исходные суммы под пересчитанными в истории цен
- v13: список КП — статус, сделка и сводная разнесены на три колонки; детальная — кнопки одной высоты, меню «…» разгружено
- v14: читаемые цвета плашек (secondary → dark), компания к номеру и дате, карточка компании без блока DEPRICATED
- v15 (патч D): новый модуль Analytics — /analytics/discounts и /analytics/partners, вписан в config/modular.php
- v16: КП прикрепляются к спецификациям рамочных договоров (contract_specification_proposals), автостатус «Выиграно»,
  минус статусы sent/negotiation, выгрузка КП в Excel, пересобранный скоринг партнёров, реестр лицензий,
  сверка суммы спецификации с платежами и КП
- v19: скоринг сглажен по трём годам (75/20/5), веса 35/30/25/5/5, ожидаемые платежи «от года и дальше»
- v20: выигранными считаются КП, прикреплённые к спецификации (status_effective), компания в попапе КП,
  select2 в попапах (dropdownParent/фокус/z-index), блок компаний на детальной странице партнёра

## Last sync
date: 2026-09-09
tree: fc17c8a (master, синхронна с origin/master)

### Проверено при синхронизации
- Работа перенесена из claude.ai/design в Claude Code, рабочая копия W:\SOURCE\avg.mom
- Патчи v19 и v20 уже влиты в master: в PartnerScoringService веса 35/30/25/5/5
  и YEAR_WEIGHTS 75/20/5, в Analytics-сервисах есть status_effective
- Поверх v20 руками поправлена вёрстка (commit fc17c8a): в pub/partner/detail
  кнопка «добавить договор» переехала в шапку таблицы, отступы карточек p-1,
  число спецификаций выводится цифрой. Бандл patch-v20 этих правок не содержит
- Каталога patch-v19 в рабочей копии нет: код v19 присутствует, бандл патча отсутствует
- patches/patch-v20 и !patches не закоммичены (untracked)
- /public в .gitignore: public/metronic/js/osmo-metronic.js в git не попадает,
  правка select2 из v20 живёт только на диске и в бандле патча
- Не проверено: применён ли к базе database/sql/patch_v20_proposal_won.sql

## Sync history
date: 2026-08-30T13:32:00Z
tree: 5ba055b45bdc

### Updated in this project
- Патч v20: в скоринге выигранными стали КП, прикреплённые к спецификации, плюс SQL-почин статусов
- Патч v20: колонка «Компания» в попапе по цифре КП (закладки «КП» и «Объём»)
- Патч v20: select2 в попапах — обёртка .select2() в osmo-metronic.js
- Патч v20: блок «Компании» в левой колонке /partners/detail

### Проверено при синхронизации
- master содержит патч v16 (ProposalStatus, SpecProposalService, contract_specification_proposals),
  патчи v17–v19 в master ещё не залиты — в мастере скоринг со весами 50/25/15/10 без сглаживания
- поэтому патч v20 собран поверх локальных версий v19, а не поверх master
- box() из resources/js/app.js: попапы вставляются в #box и показываются как modal — отсюда правка select2
- попапы спецификаций (add/edit) уже передают dropdownParent — обёртка их не ломает
- Partner::companies() есть в master — блок компаний строится на ней

## Sync history
date: 2026-08-14T09:20:00Z
tree: 7859ec71b224

### Updated in this project
- Патч v16: связь КП ↔ спецификация (компания + блок по типу рамочного договора), попап на companies/detail
- Патч v16: скоринг партнёров переведён на сумму подписанных спецификаций (50/25/15/10) с нормировкой на лидера
- Патч v16: реестр лицензий /analytics/licenses и сверка суммы спецификации с платежами
- Патч v16: выгрузка КП в xlsx через phpspreadsheet, два шаблона (внутренний и со скидкой клиента)

### Проверено при синхронизации
- ContractSpecification: связи, репозиторий и попап конфигурации прочитаны с master, новая привязка сделана по их образцу
- ProposalStatus, ProposalVariant* и Contract/ContractType прочитаны с master — формулы скидок и типы договоров взяты оттуда
- phpoffice/phpspreadsheet ^1.28 уже в composer.json проекта — новых зависимостей патч не требует
- Спецификация не имеет собственной даты, поэтому «КП → договор» считается до даты прикрепления КП
  (для старых привязок — до даты рамочного договора)

## Sync history
date: 2026-08-07T08:40:00Z
tree: 8125f5b69dd3

### Проверено при синхронизации
- Все файлы патчей v9 и v10 присутствуют в master (PaymentCalendar, DealCard, ProposalTools, компоненты proposal/*)
- config/modular.php содержит ProposalTools, DealCard, PaymentCalendar в секции Pub
- Маршруты proposal.box_status / proposal.box_deal на месте
- Наблюдение: components/proposal/hardware_table.blade.php и log_table.blade.php названы через
  подчёркивание, а detail.blade.php зовёт их как hardware-table / log-table

### Патч v11
- Платёжный календарь: multiple-фильтры (select2), снят фильтр архивных договоров (он и ломал просрочку),
  курс 1:1 вместо null при отсутствии курса, SOON_DAYS = 30, статус спецификации по умолчанию «в процессе»
- История цен: приведение к рублям только когда валюты редакций различаются
- Карточка КП: вернулись статус и привязка к сделке, в меню «…» добавлена «Сводная информация»

## Sync history
date: 2026-08-06T14:55:00Z
commit: (не зафиксирован — читались файлы по ветке master)

### Updated in this project
- Патч v9: правки платёжного календаря (месяцы, кликабельные суммы, отменённые спецификации, архивные договоры)
- Патч v9: привязка КП к нескольким сделкам Битрикса (таблица proposal_crm_deals), договор и спецификации зелёные только при полном подписании
- Патч v9 = патч C: история изменения цен по редакциям КП и клонирование КП (модуль ProposalTools)
- Ранее: слой тем через ViewFinder, левое меню, перевод страниц на Metronic 8.2.1, Font Awesome Pro

## Screen map
| Экран / файл патча | Источник в репозиториях |
|---|---|
| themes/metronic/layouts/layout_short | osmo: resources/views/layouts/layout_short.blade.php; metronic: demo48/index.html (head, бандлы) |
| themes/metronic/layouts/layout | osmo: resources/views/layouts/layout.blade.php; metronic: demo48/index.html (app-root/app-page/app-wrapper) |
| themes/metronic/layouts/header | osmo: resources/views/layouts/header.blade.php; metronic: demo48/index.html (app-header-primary/secondary) |
| themes/metronic/layouts/breadcrumbs | osmo: resources/views/layouts/breadcrumbs.blade.php; metronic: demo48/index.html (app-toolbar) |
| themes/metronic/layouts/sidebar + components/sidebar/menu* | osmo: resources/views/layouts/sidebar.blade.php, components/sidebar/*; стили — public/metronic/css/osmo-sidebar.css |
| themes/metronic/bitrix/dashboard/index | osmo: resources/views/bitrix/dashboard/index.blade.php |
| themes/metronic/pub/proposal/index | osmo: resources/views/pub/proposal/index.blade.php |
| themes/metronic/pub/proposal/create, edit | osmo: resources/views/pub/proposal/create.blade.php, edit.blade.php (механическая адаптация) |
| themes/metronic/pub/neuroservice/* | osmo: resources/views/pub/neuroservice/* |
| themes/metronic/pub/neuroservice_group/* | osmo: resources/views/pub/neuroservice_group/* |
| themes/metronic/components/breadcrumb* | osmo: resources/views/components/breadcrumb*.blade.php |
| themes/metronic/components/ui/badge/* | osmo: resources/views/components/ui/badge/* |
| themes/metronic/auth | osmo: resources/views/auth.blade.php |
| public/metronic/js/osmo-metronic.js | osmo: resources/js/app.js (box/sidebar/ajax/toastr) |
| public/metronic/css/osmo-compat.css | osmo: resources/css/app.css |
| app/Http/Kernel.php (reference) | osmo: app/Http/Kernel.php |
| patch-v9: PaymentCalendar (service/controller/view) | osmo: payments, contract_specifications, contracts, proposals; ContractSpecificationStatus (enum canceled) |
| patch-v9: proposal_crm_deals + ProposalDealService/ApiProposalStatusController/ProposalListFilterService | osmo: app/Modules/Pub/Proposal/** (master), app/Modules/Bitrix/CrmDeal |
| patch-v9: DealCard (chain) | osmo: contracts, contract_specifications, payments, license_keys |
| patch-v9: ProposalTools (история цен, клонирование) | osmo: ProposalRepository::convert(), proposal_variants* миграции |
| patch-v16: contract_specification_proposals + SpecProposalService | osmo: ContractSpecification/**, Contract/ContractType, ProposalVariant* (блоки КП) |
| patch-v16: SpecReconcileService (сверка сумм) | osmo: contract_specifications.amount, payments.amount_plan, proposal_variants.cost_total |
| patch-v16: ProposalExcelService (xlsx, два шаблона) | osmo: pub/proposal/boxes/generate_pdf.blade.php, pub/proposal/detail.blade.php (формулы скидок) |
| patch-v16: PartnerScoringService / PartnerStatsService | osmo: proposals, contracts, contract_specifications, payments, partners (PartnerGrade) |
| patch-v16: LicenseRegistryService | osmo: license_keys, contract_specifications, contracts, partners |
| patch-v16: попап КП у спецификации | osmo: pub/contract_specification/boxes/project_configuration.blade.php (образец) |
| patch-v20: select2 в попапах | osmo: resources/js/app.js (box()), resources/views/components/box/box-static-extralarge.blade.php |
| patch-v20: блок компаний партнёра | osmo: Partner::companies(), pub/partner/detail.blade.php (card-table) |
| patch-v20: выигранные КП в скоринге | osmo: SpecProposalService::win(), contract_specification_proposals, proposals.status |
