-- ---------------------------------------------------------------------------
-- Патч v22: пункт меню «Реестр сделок Bitrix» в разделе «Работа» (parent_id = 10)
--
-- Пункт виден только тем, у кого есть доступ: меню фильтруется по связке
-- access_menu (см. MenuService::build → Gate::any). Реестр гейтится тем же
-- общим доступом general_access (accesses.id = 1), что и остальные пункты
-- меню — отдельного права патч не заводит.
--
-- Запрос идемпотентный: повторный прогон ничего не продублирует.
-- После выполнения сбросить кэш меню: php artisan cache:clear
-- ---------------------------------------------------------------------------

INSERT INTO `menus` (`active`, `parent_id`, `protected`, `name`, `url`, `icon`, `sort`, `created_at`, `updated_at`)
SELECT 1,
       10,
       0,
       'Реестр сделок Bitrix',
       '/bitrix/deal',
       'fa-light fa-table-list',
       COALESCE((SELECT MAX(`sort`) FROM (SELECT `sort`, `parent_id` FROM `menus`) AS `s` WHERE `s`.`parent_id` = 10), 0) + 10,
       NOW(),
       NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `url` FROM `menus`) AS `m` WHERE `m`.`url` = '/bitrix/deal'
);

INSERT INTO `access_menu` (`access_id`, `menu_id`)
SELECT 1, `menus`.`id`
FROM `menus`
WHERE `menus`.`url` = '/bitrix/deal'
  AND NOT EXISTS (
    SELECT 1
    FROM (SELECT `access_id`, `menu_id` FROM `access_menu`) AS `a`
    WHERE `a`.`access_id` = 1 AND `a`.`menu_id` = `menus`.`id`
);

-- если пункт уже был заведён в разделе «Bitrix» — переносим в «Работа»
UPDATE `menus`
SET `parent_id` = 10,
    `name` = 'Реестр сделок Bitrix',
    `sort` = COALESCE((SELECT MAX(`sort`) FROM (SELECT `sort`, `parent_id` FROM `menus`) AS `s` WHERE `s`.`parent_id` = 10), 0) + 10
WHERE `url` = '/bitrix/deal' AND `parent_id` <> 10;
