-- patch v21: пункт меню «КП OSMOVIEW CP» в разделе «Работа» (parent_id = 10, рядом с /proposals).
-- Таблица menus (id, active, parent_id, protected, name, url, icon, sort), пункт показывается,
-- только если к нему привязан хотя бы один доступ (access_menu) — привязываем general_access (id 1),
-- как у остальных пунктов раздела. После выполнения: php artisan optimize:clear (дерево меню кэшируется).

INSERT INTO menus (active, parent_id, protected, name, url, icon, sort, created_at, updated_at)
SELECT 1, 10, 0, 'КП OSMOVIEW CP', '/external-proposals', 'fa-light fa-cloud-arrow-down',
       COALESCE(MAX(sort), 0) + 10, NOW(), NOW()
FROM menus
WHERE parent_id = 10
  AND NOT EXISTS (SELECT 1 FROM menus m WHERE m.url = '/external-proposals');

INSERT INTO access_menu (access_id, menu_id)
SELECT a.id, m.id
FROM menus m
JOIN accesses a ON a.code = 'general_access'
WHERE m.url = '/external-proposals'
  AND NOT EXISTS (SELECT 1 FROM access_menu am WHERE am.menu_id = m.id AND am.access_id = a.id);
