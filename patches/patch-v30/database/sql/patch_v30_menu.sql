-- ---------------------------------------------------------------------------
-- Патч v30: пункт меню «Рабочий стол» (запрос владельца 14.09.2026)
--
-- Новая главная страница /desktop (маршрут desktop.index) — первым пунктом
-- раздела «Работа» (parent_id = 10): sort = -100, меньше, чем у «КП» (0).
-- Пункту нужна связка access_menu с общим доступом (access_id = 1).
-- Страница свёрстана только в теме Metronic; в старой теме /desktop сам
-- перенаправляет на воронку продаж.
--
-- Пункт ищется по url, запрос идемпотентный: повторный прогон ничего не меняет.
-- После выполнения сбросить кэш меню: php artisan cache:clear
-- ---------------------------------------------------------------------------

INSERT INTO `menus` (`active`, `parent_id`, `protected`, `name`, `url`, `icon`, `sort`, `created_at`, `updated_at`)
SELECT 1, 10, 0, 'Рабочий стол', '/desktop', 'fa-light fa-grid-2', -100, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `url` FROM `menus`) AS `m`
    WHERE `m`.`url` = '/desktop'
);

INSERT INTO `access_menu` (`access_id`, `menu_id`)
SELECT 1, `menus`.`id`
FROM `menus`
WHERE `menus`.`url` = '/desktop'
  AND NOT EXISTS (
    SELECT 1 FROM (SELECT `access_id`, `menu_id` FROM `access_menu`) AS `a`
    WHERE `a`.`access_id` = 1 AND `a`.`menu_id` = `menus`.`id`
);
