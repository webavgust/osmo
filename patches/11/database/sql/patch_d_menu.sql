-- ============================================================================
--  Патч D: монитор расхождений с Битрикс24 — пункт меню и право доступа
-- ============================================================================
--  Пункт ставится в раздел, где уже живёт дашборд Битрикса.
-- ============================================================================

SET @parent_id = (SELECT id FROM (SELECT id FROM menu WHERE code = 'bitrix' LIMIT 1) m);
SET @sort = (SELECT COALESCE(MAX(sort), 0) + 10 FROM (SELECT sort FROM menu WHERE parent_id = @parent_id) s);

INSERT INTO menu (parent_id, name, code, url, icon, sort, active, created_at, updated_at)
SELECT @parent_id,
       'Расхождения с Битрикс24',
       'crm_monitor',
       '/crm-monitor',
       'fa-light fa-scale-unbalanced',
       @sort,
       1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM menu) m WHERE m.code = 'crm_monitor');

-- ----------------------------------------------------------------------------
--  Право доступа
-- ----------------------------------------------------------------------------

SET @access_group_id = (SELECT id FROM access_groups ORDER BY sort LIMIT 1);

INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT @access_group_id, 0,
       'Монитор расхождений с Битрикс24',
       'crm_monitor_view',
       'Список КП, у которых сделка Битрикса не привязана либо не сходится по валюте и сумме',
       640,
       'App\\Modules\\Pub\\CrmMonitor\\Controllers\\CrmMonitorController',
       'index',
       0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM accesses) a WHERE a.code = 'crm_monitor_view');
