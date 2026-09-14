-- ---------------------------------------------------------------------------
-- Патч v27: правки меню по просьбе владельца (13.09.2026)
--
-- 1. «Расхождения с Битрикс24» (/crm-monitor) переезжает из раздела «Bitrix»
--    в раздел «Настройки» (parent_id = 1), последним пунктом.
-- 2. Звёзды в «Отчётах»: у «Китая» заливка убрана (контурная, как у соседних
--    пунктов), у «Скоринга партнёров» — залитая звезда цвета warning.
-- 3. «КП OSMOVIEW CP» (/external-proposals) переименован во «Внешние КП».
-- 4. «Отчёты» (parent_id = 15) разбиты на подгруппы горизонтальными разделителями:
--    прочие отчёты → [—] «Договоры и оплаты» + «Платёжный календарь»
--    → [—] «Ключи» + «Реестр лицензий». Разделитель — пункт с именем «---» без url
--    (шаблон components/sidebar/menu-item рисует его линией); ему, как и остальным
--    пунктам, нужна связка access_menu с общим доступом (access_id = 1).
-- 5. Раздел «Bitrix» (id 21) расформирован и выключен (active = 0, не удалён):
--    «Воронка продаж» → «Работа», «Синхронизация» → «Настройки» под именем
--    «Синхронизация Битрикс24», «Расхождения с Битрикс24» → «Отчёты» (после «Скоринга
--    партнёров»). «Реестр сделок Bitrix» → «Реестр сделок Битрикс24».
--    Пункт 1 выше (перенос «Расхождений» в «Настройки») этим перекрывается.
--
-- Пункты ищутся по url, запрос идемпотентный: повторный прогон ничего не меняет.
-- После выполнения сбросить кэш меню: php artisan cache:clear
-- ---------------------------------------------------------------------------

UPDATE `menus`
SET `parent_id` = 1,
    `sort` = COALESCE((SELECT MAX(`sort`) FROM (SELECT `sort`, `parent_id`, `url` FROM `menus`) AS `s` WHERE `s`.`parent_id` = 1 AND COALESCE(`s`.`url`, '') <> '/crm-monitor'), 0) + 100,
    `updated_at` = NOW()
WHERE `url` = '/crm-monitor' AND `parent_id` <> 1;

UPDATE `menus`
SET `icon` = 'fa-light fa-star',
    `updated_at` = NOW()
WHERE `url` = '/report/china' AND `icon` <> 'fa-light fa-star';

UPDATE `menus`
SET `icon` = 'fa-solid fa-star text-warning',
    `updated_at` = NOW()
WHERE `url` = '/analytics/partners' AND `icon` <> 'fa-solid fa-star text-warning';

UPDATE `menus`
SET `name` = 'Внешние КП',
    `updated_at` = NOW()
WHERE `url` = '/external-proposals' AND `name` <> 'Внешние КП';

-- 4. Подгруппы «Отчётов»: порядок пунктов
UPDATE `menus`
SET `sort` = CASE `url`
        WHEN '/report/scenarios' THEN 100
        WHEN '/report/specs' THEN 300
        WHEN '/report/scenarios_specs' THEN 400
        WHEN '/report/china' THEN 600
        WHEN '/analytics/discounts' THEN 800
        WHEN '/analytics/partners' THEN 810
        WHEN '/report/payments' THEN 1100
        WHEN '/payment-calendar' THEN 1200
        WHEN '/report/license_keys' THEN 1400
        WHEN '/analytics/licenses' THEN 1500
    END,
    `updated_at` = NOW()
WHERE `parent_id` = 15
  AND `url` IN ('/report/scenarios', '/report/specs', '/report/scenarios_specs', '/report/china',
                '/analytics/discounts', '/analytics/partners', '/report/payments', '/payment-calendar',
                '/report/license_keys', '/analytics/licenses');

-- разделители перед «Договорами и оплатами» (1000) и перед «Ключами» (1300)
INSERT INTO `menus` (`active`, `parent_id`, `protected`, `name`, `url`, `icon`, `sort`, `created_at`, `updated_at`)
SELECT 1, 15, 0, '---', NULL, NULL, `s`.`sort`, NOW(), NOW()
FROM (SELECT 1000 AS `sort` UNION ALL SELECT 1300) AS `s`
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `parent_id`, `name`, `sort` FROM `menus`) AS `m`
    WHERE `m`.`parent_id` = 15 AND `m`.`name` = '---' AND `m`.`sort` = `s`.`sort`
);

INSERT INTO `access_menu` (`access_id`, `menu_id`)
SELECT 1, `menus`.`id`
FROM `menus`
WHERE `menus`.`parent_id` = 15 AND `menus`.`name` = '---'
  AND NOT EXISTS (
    SELECT 1 FROM (SELECT `access_id`, `menu_id` FROM `access_menu`) AS `a`
    WHERE `a`.`access_id` = 1 AND `a`.`menu_id` = `menus`.`id`
);

-- 5. Раздел «Bitrix» расформирован
UPDATE `menus` SET `parent_id` = 10, `sort` = 130, `updated_at` = NOW()
WHERE `url` = '/bitrix/dashboard' AND (`parent_id` <> 10 OR `sort` <> 130);

UPDATE `menus` SET `parent_id` = 1, `sort` = 200, `name` = 'Синхронизация Битрикс24', `updated_at` = NOW()
WHERE `url` = '/bitrix/sync' AND (`parent_id` <> 1 OR `sort` <> 200 OR `name` <> 'Синхронизация Битрикс24');

UPDATE `menus` SET `parent_id` = 15, `sort` = 820, `updated_at` = NOW()
WHERE `url` = '/crm-monitor' AND (`parent_id` <> 15 OR `sort` <> 820);

UPDATE `menus` SET `name` = 'Реестр сделок Битрикс24', `updated_at` = NOW()
WHERE `url` = '/bitrix/deal' AND `name` <> 'Реестр сделок Битрикс24';

-- сам раздел: выключаем, а не удаляем — пустой заголовок иначе остался бы в меню
UPDATE `menus` SET `active` = 0, `updated_at` = NOW()
WHERE `parent_id` = 0 AND `url` IS NULL AND `name` = 'Bitrix' AND `active` = 1;
