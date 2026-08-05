-- ============================================================================
--  Патч B: пункты меню
-- ============================================================================
--  Платёжный календарь — самостоятельная страница, ей нужен пункт меню.
--  Сквозная карточка сделки открывается из списка КП и из календаря
--  (иконка «цепочка»), отдельного пункта не требует.
--
--  Перед выполнением посмотрите, куда положить пункт:
--    SELECT id, parent_id, name, url, sort FROM menus ORDER BY parent_id, sort;
-- ============================================================================

-- Родитель: раздел, где живут договоры и платежи.
-- Если названия отличаются — поправьте LIKE или задайте @parent_id вручную.
SET @parent_id = (
    SELECT id FROM menus
    WHERE parent_id = 0
      AND (name LIKE '%оговор%' OR name LIKE '%латеж%' OR name LIKE '%инанс%')
    ORDER BY sort
    LIMIT 1
);

-- Если подходящего раздела нет — вешаем в корень
SET @parent_id = IFNULL(@parent_id, 0);

INSERT INTO menus (active, parent_id, protected, name, url, icon, sort, created_at, updated_at)
SELECT 1, @parent_id, 0,
       'Платёжный календарь',
       '/payment-calendar',
       'fa-light fa-calendar-dollar',
       500,
       NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM menus) m WHERE m.url = '/payment-calendar');


-- ============================================================================
--  Права доступа (по желанию)
-- ============================================================================

SET @access_group_id = (SELECT id FROM access_groups ORDER BY sort LIMIT 1);

INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT @access_group_id, 0,
       'Платёжный календарь',
       'payment_calendar_view',
       'Доступ к платёжному календарю: просрочка, прогноз поступлений, план и факт',
       600,
       'App\\Modules\\Pub\\PaymentCalendar\\Controllers\\PaymentCalendarController',
       'index',
       0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM accesses) a WHERE a.code = 'payment_calendar_view');

INSERT INTO accesses (access_group_id, protected, name, code, description, sort, class, method, admin_invert, created_at, updated_at)
SELECT @access_group_id, 0,
       'Сквозная карточка сделки',
       'deal_card_view',
       'Доступ к сквозной карточке: КП, сделка Битрикс24, договор, спецификации, платежи, лицензии',
       610,
       'App\\Modules\\Pub\\DealCard\\Controllers\\DealCardController',
       'index',
       0, NOW(), NOW()
WHERE @access_group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM accesses) a WHERE a.code = 'deal_card_view');
