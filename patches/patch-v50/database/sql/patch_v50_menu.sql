-- ---------------------------------------------------------------------------
-- Патч v50: пункт меню «Движение сделок» (запрос владельца 16.11.2026)
--
-- Отчёт /bitrix/movement (маршрут bitrix.movement) — в разделе
-- «Работа» (parent_id = 10) сразу после «Воронки продаж» (sort 130): sort = 135.
-- Доступ — как у воронки: access_menu с access_id = 1.
--
-- Пункт ищется по url, запрос идемпотентный: повторный прогон ничего не меняет.
-- После выполнения сбросить кэш меню: php artisan cache:clear
-- ---------------------------------------------------------------------------

INSERT INTO `menus` (`active`, `parent_id`, `protected`, `name`, `url`, `icon`, `sort`, `created_at`, `updated_at`)
SELECT 1, 10, 0, 'Движение сделок', '/bitrix/movement', 'fa-light fa-arrow-right-arrow-left', 135, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `url` FROM `menus`) AS `m`
    WHERE `m`.`url` = '/bitrix/movement'
);

INSERT INTO `access_menu` (`access_id`, `menu_id`)
SELECT 1, `menus`.`id`
FROM `menus`
WHERE `menus`.`url` = '/bitrix/movement'
  AND NOT EXISTS (
    SELECT 1 FROM (SELECT `access_id`, `menu_id` FROM `access_menu`) AS `a`
    WHERE `a`.`access_id` = 1 AND `a`.`menu_id` = `menus`.`id`
);
