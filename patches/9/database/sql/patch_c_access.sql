-- ============================================================================
--  Патч C: права доступа (по желанию)
-- ============================================================================
--  История цен и клонирование открываются из карточки КП и сквозной карточки,
--  отдельных пунктов меню не требуют.
-- ============================================================================

SET @access_group_id = (SELECT id FROM access_groups ORDER BY sort LIMIT 1);

INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT @access_group_id, 0,
       'История изменения цен КП',
       'proposal_price_history_view',
       'Доступ к истории цен по редакциям КП: суммы по блокам и сравнение позиций',
       620,
       'App\\Modules\\Pub\\ProposalTools\\Controllers\\ProposalToolsController',
       'price_history',
       0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM accesses) a WHERE a.code = 'proposal_price_history_view');

INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT @access_group_id, 0,
       'Клонирование КП',
       'proposal_clone',
       'Право создать новое КП копией существующего (перенос расчёта без статуса и договоров)',
       630,
       'App\\Modules\\Pub\\ProposalTools\\Controllers\\ProposalToolsController',
       'box_clone',
       0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM accesses) a WHERE a.code = 'proposal_clone');
