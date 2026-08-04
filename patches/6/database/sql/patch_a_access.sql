-- ============================================================================
--  Патч A: пункты меню и права доступа
-- ============================================================================
--  Ничего обязательного здесь нет: статус КП и привязка сделки живут внутри
--  карточки КП, виджет лицензий — на дашборде. Отдельных страниц патч не
--  добавляет, поэтому новых пунктов меню не требуется.
--
--  Ниже — только права доступа, если вы хотите ограничить смену статуса
--  и привязку сделок. Проверьте id группы доступов перед выполнением.
-- ============================================================================

-- Группа доступов «Коммерческие предложения» (если её ещё нет)
-- SELECT id, name FROM access_groups ORDER BY sort;

SET @access_group_id = (SELECT id FROM access_groups WHERE name LIKE '%редложени%' LIMIT 1);

-- Право: смена статуса КП
INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT
    @access_group_id, 0,
    'Смена статуса КП',
    'proposal_status_change',
    'Позволяет менять статус коммерческого предложения (выиграно, проиграно, заморожено) и указывать причину',
    500,
    'App\\Modules\\Pub\\Proposal\\Models\\Proposal',
    'status',
    0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM accesses WHERE code = 'proposal_status_change');

-- Право: привязка КП к сделке Битрикса
INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT
    @access_group_id, 0,
    'Привязка КП к сделке',
    'proposal_deal_link',
    'Позволяет привязывать и отвязывать сделку Битрикс24 у коммерческого предложения',
    510,
    'App\\Modules\\Pub\\Proposal\\Models\\Proposal',
    'deal',
    0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM accesses WHERE code = 'proposal_deal_link');


-- ============================================================================
--  Проставить статус существующим КП
-- ============================================================================
--  Миграция ставит всем 'in_work'. Разумнее считать отправленными те КП,
--  у которых заполнена дата отправки, и выигранными — те, по которым уже
--  есть подписанный договор. Раскомментируйте, если согласны с логикой.
-- ============================================================================

-- UPDATE proposals SET status = 'sent'
--  WHERE sended_at IS NOT NULL AND sended_at <= CURDATE();

-- UPDATE proposals p
--   JOIN contracts c ON c.proposal_id = p.id AND c.cb_signed = 1
--    SET p.status = 'won', p.status_changed_at = NOW();
