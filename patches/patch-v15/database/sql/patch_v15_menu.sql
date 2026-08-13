-- ============================================================================
--  Патч v15: аналитика — пункты меню и права доступа
-- ============================================================================
--  Пункты ставятся в раздел отчётов. Если раздела с кодом report нет,
--  они встанут в корень меню — оттуда их можно перенести руками.
-- ============================================================================

SET @parent_id = (SELECT id FROM (SELECT id FROM menu WHERE code = 'report' LIMIT 1) m);
SET @sort = (SELECT COALESCE(MAX(sort), 0) + 10 FROM (SELECT sort FROM menu WHERE parent_id <=> @parent_id) s);

INSERT INTO menu (parent_id, name, code, url, icon, sort, active, created_at, updated_at)
SELECT @parent_id,
       'Анализ скидок',
       'analytics_discounts',
       '/analytics/discounts',
       'fa-light fa-percent',
       @sort,
       1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM menu) m WHERE m.code = 'analytics_discounts');

INSERT INTO menu (parent_id, name, code, url, icon, sort, active, created_at, updated_at)
SELECT @parent_id,
       'Скоринг партнёров',
       'analytics_partners',
       '/analytics/partners',
       'fa-light fa-ranking-star',
       @sort + 10,
       1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM menu) m WHERE m.code = 'analytics_partners');

-- ----------------------------------------------------------------------------
--  Права доступа
-- ----------------------------------------------------------------------------

SET @access_group_id = (SELECT id FROM access_groups ORDER BY sort LIMIT 1);

INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT @access_group_id, 0,
       'Анализ скидок',
       'analytics_discounts_view',
       'Скидки заказчику и партнёру по всем КП, с отбором выбивающихся из нормы грейда',
       650,
       'App\\Modules\\Pub\\Analytics\\Controllers\\AnalyticsController',
       'discounts',
       0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM accesses) a WHERE a.code = 'analytics_discounts_view');

INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT @access_group_id, 0,
       'Скоринг партнёров',
       'analytics_partners_view',
       'Конверсия, объём, платёжная дисциплина и скидки партнёров одним списком',
       660,
       'App\\Modules\\Pub\\Analytics\\Controllers\\AnalyticsController',
       'partners',
       0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM accesses) a WHERE a.code = 'analytics_partners_view');
