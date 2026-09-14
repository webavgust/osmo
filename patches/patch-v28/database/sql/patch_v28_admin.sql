-- ---------------------------------------------------------------------------
-- Патч v28: признак админа только у Анны, остальные — обычные пользователи
--
-- Раньше «админом» был каждый: у всех семи пользователей стояло право
-- super_user (accesses.id = 6), а User::isAdmin() = is_admin || super_user,
-- и любое право для админа выдавалось автоматически (admin_invert = 0).
-- Сняв super_user, пользователь потерял бы всё — в access_user у него
-- ничего, кроме super_user, нет. Поэтому обычным пользователям явно
-- выдаются права публичной части:
--   general_access         — пункты меню «Работа», «Отчёты», «Справочники»;
--   payment_calendar_view  — платёжный календарь;
--   deal_card_view         — сквозная карточка сделки.
-- Не выдаются (остаются у админа): access_create, access_view, menu_control,
-- menu_view (раздел «Настройки»), desktop_ann, super_user.
--
-- is_admin = 1 теперь означает доступ в админ-панель (Gate admin_panel).
--
-- Запросы идемпотентные. После выполнения обязательно: php artisan cache:clear
-- (права кэшируются навсегда в can_do_{user_id}, меню — в menu_tree_*).
-- ---------------------------------------------------------------------------

-- 0. Gate «general_access» был привязан к AccessPolicy::access_view — то есть на деле
--    проверял «Просмотр доступов». Пока все были админами, это не проявлялось; у обычного
--    пользователя меню (оно фильтруется этим Gate) оказалось бы пустым. Правильный метод
--    давно есть: AccessGroupPolicy::general_access → can_do('general_access').
UPDATE `accesses`
SET `class` = 'App\\Modules\\Pub\\AccessGroup\\Policies\\AccessGroupPolicy',
    `method` = 'general_access'
WHERE `code` = 'general_access'
  AND (`class` <> 'App\\Modules\\Pub\\AccessGroup\\Policies\\AccessGroupPolicy' OR `method` <> 'general_access');

-- 1. Признак админа: только Анна
UPDATE `users` SET `is_admin` = 1 WHERE `email` = 'avg.anna@yandex.ru' AND `is_admin` <> 1;
UPDATE `users` SET `is_admin` = 0 WHERE `email` <> 'avg.anna@yandex.ru' AND `is_admin` <> 0;

-- 2. Права публичной части обычным пользователям
INSERT INTO `access_user` (`access_id`, `user_id`, `mode`)
SELECT `a`.`id`, `u`.`id`, 1
FROM `users` AS `u`
JOIN `accesses` AS `a` ON `a`.`code` IN ('general_access', 'payment_calendar_view', 'deal_card_view')
WHERE `u`.`is_admin` = 0
  AND NOT EXISTS (
    SELECT 1 FROM (SELECT `access_id`, `user_id` FROM `access_user`) AS `x`
    WHERE `x`.`access_id` = `a`.`id` AND `x`.`user_id` = `u`.`id`
);

-- 3. Суперпользователь: только у админа
DELETE `au` FROM `access_user` AS `au`
JOIN `accesses` AS `a` ON `a`.`id` = `au`.`access_id` AND `a`.`code` = 'super_user'
JOIN `users` AS `u` ON `u`.`id` = `au`.`user_id` AND `u`.`is_admin` = 0;
