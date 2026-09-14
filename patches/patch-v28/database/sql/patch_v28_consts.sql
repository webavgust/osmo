-- ---------------------------------------------------------------------------
-- Патч v28: константы портала в админ-панели (этап C)
--
-- Бизнес-настройки, которые раньше были зашиты PHP-константами в сервисах,
-- переезжают в таблицу consts и правятся в админ-панели (/admin/consts).
-- PHP-константы в коде остались значениями по умолчанию: если строки нет или
-- значение пустое, код работает ровно как до патча. Поэтому значения ниже —
-- те же, что в коде, и после выполнения ничего не меняется.
--
-- Технические перечни (режимы, статусы, подписи, колонки выгрузок, id прав,
-- алиасы валют, коды источников) остались в коде, ключи API — в .env.
--
-- Перед этим файлом должна пройти миграция
-- 2026_09_13_100200_add_note_to_consts_table (колонка note и уникальный
-- индекс на key). Запросы идемпотентные: новые константы добавляются только
-- если такого кода ещё нет, примечания существующих заполняются только пустые.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

-- 1. Скоринг партнёров (PartnerScoringService)

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скоринг: вес суммы подписанных спецификаций', 'scoring_weight_specs', '35',
       'Вес составляющей «Сумма подписанных спецификаций» в балле партнёра (Аналитика → Скоринг партнёров). Пять весов scoring_weight_* в сумме должны давать 100.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'scoring_weight_specs');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скоринг: вес количества проектов', 'scoring_weight_projects', '25',
       'Вес составляющей «Количество проектов» в балле партнёра. Пять весов scoring_weight_* в сумме должны давать 100.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'scoring_weight_projects');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скоринг: вес конверсии КП', 'scoring_weight_conversion', '25',
       'Вес составляющей «Конверсия решённых КП» (выиграно к выигранным и проигранным) в балле партнёра. Пять весов scoring_weight_* в сумме должны давать 100.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'scoring_weight_conversion');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скоринг: вес сделок Битрикс24', 'scoring_weight_deals', '10',
       'Вес составляющей «Кол-во сделок Битрикс24» в балле партнёра. Пять весов scoring_weight_* в сумме должны давать 100.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'scoring_weight_deals');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скоринг: вес платежей без просрочки', 'scoring_weight_overdue', '5',
       'Вес составляющей «Платежи без просрочки» в балле партнёра: чем меньше доля просроченных платежей, тем выше балл. Пять весов scoring_weight_* в сумме должны давать 100.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'scoring_weight_overdue');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скоринг: вклад лет в балл', 'scoring_year_weights', '{"0":75,"1":20,"2":5}',
       'Сглаживание балла партнёра по годам, JSON: смещение назад (0 — выбранный год, 1 — прошлый, 2 — позапрошлый) → вес в процентах, в сумме 100. Без ключа 0 берётся значение из кода.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'scoring_year_weights');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скоринг: лет на графике места', 'scoring_history_years', '5',
       'Сколько последних лет показывает график места партнёра в рейтинге (подсказка в скоринге партнёров), лет.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'scoring_history_years');

-- 2. Лицензии (LicenseRegistryService, LicenseRenewalService)

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Лицензии: горизонты истечения', 'license_horizons', '[30,60,90]',
       'Горизонты в днях, по которым раскладываются истекающие лицензии: реестр лицензий (Аналитика) и «Продление лицензий» на дашборде. JSON-массив чисел; наибольший — горизонт по умолчанию.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'license_horizons');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Лицензии: показывать истёкшие, дней', 'license_expired_tail_days', '30',
       'Сколько дней после истечения лицензия ещё показывается в «Продлении лицензий» на дашборде (истекла, но продление можно вернуть), дней.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'license_expired_tail_days');

-- 3. Анализ скидок (DiscountAnalysisService)

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скидки: превышение среднего по грейду, п.п.', 'discount_grade_alert_pp', '5',
       'Анализ скидок: КП выделяется, если совокупная скидка выше средней по грейду партнёра больше чем на столько процентных пунктов.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'discount_grade_alert_pp');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Скидки: потолок совокупной скидки, %', 'discount_hard_limit_p', '40',
       'Анализ скидок: совокупная скидка выше этого процента выделяется при любом грейде партнёра, %.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'discount_hard_limit_p');

-- 4. Платёжный календарь (PaymentCalendarService)

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Платежи: «скоро», дней', 'payment_soon_days', '30',
       'Платёжный календарь: неоплаченный плановый платёж, до даты которого осталось не больше стольких дней, получает состояние «Скоро», дней.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'payment_soon_days');

-- 5. Сверки (SpecReconcileService, CrmMismatchService)

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Сверка спецификаций: допуск расхождения', 'spec_reconcile_tolerance', '1.0',
       'Сверка спецификации с графиком платежей и прикреплёнными КП: расхождение до этой суммы считается округлением, а не ошибкой. В единицах валюты спецификации.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'spec_reconcile_tolerance');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Сверка спецификаций: доля грубого расхождения', 'spec_reconcile_hard_share', '0.05',
       'Сверка спецификаций: расхождение платежей больше этой доли от суммы спецификации считается грубым. Доля: 0.05 = 5%.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'spec_reconcile_hard_share');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Монитор CRM: допуск расхождения сумм', 'crm_amount_tolerance', '1',
       'Монитор расхождений с Битрикс24: допустимая разница между суммой сделок и последним вариантом КП, в единицах валюты КП.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'crm_amount_tolerance');

-- 6. Битрикс24 (CrmDealRegistryService, PartnerCrmCompanyService, CrmDealRepository, DealProjectService)

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Битрикс24: реестр сделок с даты', 'bitrix_deals_since', '2025-01-01',
       'Реестр сделок Битрикс24 (список, счётчики вкладок, заказчики в фильтре) показывает сделки, созданные с этой даты. Формат ГГГГ-ММ-ДД.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'bitrix_deals_since');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Партнёры: сделки Битрикс24 с даты', 'partner_deals_from', '2025-01-01',
       'Карточка партнёра, блок «Битрикс24»: число сделок сопоставленных компаний считается с этой даты создания сделки. Формат ГГГГ-ММ-ДД.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'partner_deals_from');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Битрикс24: ссылка на сделку', 'bitrix_deal_url', 'https://osmoview.bitrix24.ru/crm/deal/details/%s/',
       'Шаблон ссылки на карточку сделки в Битрикс24 (реестр сделок, попапы, сквозная карточка); %s заменяется на id сделки. Без %s берётся значение из кода.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'bitrix_deal_url');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Битрикс24: поле «Конечный заказчик»', 'bitrix_uf_customer', 'uf_crm_1717755645',
       'Код пользовательского поля сделки «Конечный заказчик» (колонка crm_deal_uf): реестр сделок, поиск сделок для КП, проекты. Только латиница, цифры и _.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'bitrix_uf_customer');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Битрикс24: поле «Страна» компании', 'bitrix_uf_country', 'uf_crm_1719404976291',
       'Код пользовательского поля компании «Страна» (колонка crm_company_uf): фильтр по стране в реестре сделок и на дашборде Битрикс. Только латиница, цифры и _.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'bitrix_uf_country');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'Битрикс24: поле «Плановый квартал»', 'bitrix_uf_quarter', 'uf_crm_1722255711522',
       'Код пользовательского поля сделки «Плановый квартал исполнения» (колонка crm_deal_uf): реестр и поиск сделок, дата начала проекта при автосоздании. Только латиница, цифры и _.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'bitrix_uf_quarter');

-- 7. OSMOVIEW CP (ExternalProposalService, OsmoviewCpMapper)

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'OSMOVIEW CP: подробностей за синхронизацию', 'osmoview_detail_batch', '30',
       'Сколько КП OSMOVIEW CP за одну синхронизацию дозагружаются подробно (detail), чтобы запрос не упёрся в таймаут, штук.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'osmoview_detail_batch');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'OSMOVIEW CP: сценарий кастомных позиций', 'osmoview_scenario_custom_id', '97',
       'Id служебного сценария портала, в который при переносе КП OSMOVIEW CP попадают кастомные позиции (scenarioId «0»); цену у них указывают вручную.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'osmoview_scenario_custom_id');

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'OSMOVIEW CP: порог рублёвой ручной цены', 'osmoview_manual_rub_threshold', '3000',
       'Перенос КП OSMOVIEW CP: ручная цена в валютном КП выше этого числа считается рублёвой и пересчитывается по курсу с предупреждением.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'osmoview_manual_rub_threshold');

-- 8. Привязка КП к сделкам (ProposalDealService)

INSERT INTO `consts` (`name`, `key`, `value`, `note`, `system`)
SELECT 'КП и сделки: строк в поиске', 'proposal_deal_search_limit', '50',
       'Сколько строк отдаёт поиск сделок Битрикс24 для привязки к КП и поиск КП для привязки к сделке.', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `consts` WHERE `key` = 'proposal_deal_search_limit');

-- 9. Примечания к существующим константам (только если пустые)

UPDATE `consts`
SET `note` = 'Множитель цены бессрочной лицензии к годовой: у нейросервиса без своей бессрочной цены она равна годовой, умноженной на это число (3 — в три раза дороже). Читается в справочнике нейросервисов, сценариях и при сохранении КП (rate_unlimited).'
WHERE `key` = 'neuroservice_unlimited_multiplier' AND (`note` IS NULL OR `note` = '');

UPDATE `consts`
SET `note` = 'Ставка за 1 час работы, руб. Сейчас кодом портала не читается — оставлена как справочная.'
WHERE `key` = 'work_hour_rate' AND (`note` IS NULL OR `note` = '');

UPDATE `consts`
SET `note` = 'Прайс платформы, JSON: ключ — порог по числу камер, u — бессрочно, y — за год, p — за месяц, руб. Читается в форме КП, в ценах сценариев и при переносе КП OSMOVIEW CP.'
WHERE `key` = 'platform_cost_per_year' AND (`note` IS NULL OR `note` = '');

UPDATE `consts`
SET `note` = 'Ставка НДС по умолчанию, %: подставляется в форму нового КП и в КП, перенесённые из OSMOVIEW CP с включённым НДС.'
WHERE `key` = 'nds_rate' AND (`note` IS NULL OR `note` = '');

UPDATE `consts`
SET `note` = 'Время последнего обновления таблиц зеркала Битрикс24, JSON: таблица → время (UTC). Пишет синхронизация, показывает страница «Синхронизация с Битрикс24».'
WHERE `key` = 'bitrix_update_timestamps' AND (`note` IS NULL OR `note` = '');
